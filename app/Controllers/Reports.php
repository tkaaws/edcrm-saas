<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\UserAccessScopeService;
use CodeIgniter\Exceptions\PageNotFoundException;

class Reports extends BaseController
{
    protected UserModel $userModel;
    protected UserAccessScopeService $accessScope;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->accessScope = service('userAccessScope');
    }

    public function index()
    {
        $permissions = service('permissions');
        $canViewSelf = $permissions->has('reports.activity_self') || $permissions->has('reports.view');
        $canViewTeam = $permissions->has('reports.activity_team') || $permissions->has('reports.advanced');

        if (! $canViewSelf && ! $canViewTeam) {
            throw PageNotFoundException::forPageNotFound();
        }

        $scope = (string) ($this->request->getGet('scope') ?: ($canViewSelf ? 'self' : 'team'));
        if (! in_array($scope, ['self', 'team'], true)) {
            $scope = $canViewSelf ? 'self' : 'team';
        }
        if ($scope === 'team' && ! $canViewTeam) {
            $scope = 'self';
        }

        $today = date('Y-m-d');
        $fromDate = (string) ($this->request->getGet('from') ?: $today);
        $toDate = (string) ($this->request->getGet('to') ?: $today);
        $selectedUserId = (int) ($this->request->getGet('user_id') ?: 0);

        $allowedUserIds = $scope === 'team'
            ? $this->getTeamScopeUserIds()
            : [(int) session()->get('user_id')];

        if ($allowedUserIds === []) {
            $allowedUserIds = [(int) session()->get('user_id')];
        }

        if ($scope === 'self') {
            $selectedUserId = (int) session()->get('user_id');
        } elseif ($selectedUserId > 0 && ! in_array($selectedUserId, $allowedUserIds, true)) {
            $selectedUserId = 0;
        }

        $userOptions = $scope === 'team' ? $this->getUserOptions($allowedUserIds) : [];
        $activities = $this->getActivities($allowedUserIds, $fromDate, $toDate, $selectedUserId ?: null);
        $summary = $this->summarizeActivities($activities);

        return view('reports/index', $this->buildShellViewData([
            'pageTitle'   => 'Activity Reports',
            'activeNav'   => 'reports',
            'scope'       => $scope,
            'fromDate'    => $fromDate,
            'toDate'      => $toDate,
            'selectedUserId' => $selectedUserId,
            'userOptions' => $userOptions,
            'canViewSelf' => $canViewSelf,
            'canViewTeam' => $canViewTeam,
            'summary'     => $summary,
            'activities'  => $activities,
        ]));
    }

    /**
     * @param list<int> $allowedUserIds
     * @return array<int, object>
     */
    protected function getActivities(array $allowedUserIds, string $fromDate, string $toDate, ?int $selectedUserId = null): array
    {
        $builder = db_connect()->table('audit_logs logs')
            ->select([
                'logs.*',
                "TRIM(CONCAT(COALESCE(actor.first_name, ''), ' ', COALESCE(actor.last_name, ''))) AS actor_name",
            ])
            ->join('users actor', 'actor.id = logs.user_id', 'left')
            ->where('logs.tenant_id', (int) session()->get('tenant_id'))
            ->where('logs.user_id IS NOT NULL', null, false)
            ->where('DATE(logs.created_at) >=', $fromDate)
            ->where('DATE(logs.created_at) <=', $toDate);

        if ($selectedUserId !== null && $selectedUserId > 0) {
            $builder->where('logs.user_id', $selectedUserId);
        } else {
            $builder->whereIn('logs.user_id', $allowedUserIds);
        }

        $rows = $builder->orderBy('logs.created_at', 'DESC')->get()->getResult();

        foreach ($rows as $row) {
            $row->actor_display = trim((string) ($row->actor_name ?? '')) ?: 'System';
            $row->module_label = $this->mapEntityTypeToModule((string) $row->entity_type);
            $row->display_title = $this->buildActivityTitle($row);
            $row->changes = $this->buildActivityChanges($row);
        }

        return $rows;
    }

    protected function summarizeActivities(array $activities): array
    {
        $summary = [
            'total'        => count($activities),
            'enquiries'    => 0,
            'followups'    => 0,
            'people'       => 0,
            'settings'     => 0,
        ];

        foreach ($activities as $activity) {
            $entityType = (string) ($activity->entity_type ?? '');
            if (in_array($entityType, ['enquiry', 'enquiry_assignment', 'enquiry_status'], true)) {
                $summary['enquiries']++;
            } elseif ($entityType === 'enquiry_followup') {
                $summary['followups']++;
            } elseif (in_array($entityType, ['user', 'user_branch', 'user_hierarchy'], true)) {
                $summary['people']++;
            } elseif (in_array($entityType, ['tenant_setting', 'branch_setting', 'tenant_policy', 'tenant_master_data', 'tenant_branch', 'college'], true)) {
                $summary['settings']++;
            }
        }

        return $summary;
    }

    /**
     * @return list<int>
     */
    protected function getTeamScopeUserIds(): array
    {
        $actor = $this->accessScope->getActor();
        if (! $actor) {
            return [];
        }

        return match ($this->accessScope->getActorAccessBehavior()) {
            'tenant' => $this->userModel->withoutTenantScope()
                ->where('tenant_id', (int) $actor->tenant_id)
                ->where('is_active', 1)
                ->findColumn('id') ?: [],
            'branch' => $this->getBranchScopeUserIds((int) $actor->id),
            default => $this->accessScope->getHierarchyScopeUserIdsForActor(true),
        };
    }

    /**
     * @return list<int>
     */
    protected function getBranchScopeUserIds(int $actorUserId): array
    {
        $branchIds = $this->accessScope->getAssignedBranchIdsForActor();
        if ($branchIds === []) {
            return [$actorUserId];
        }

        $rows = db_connect()->table('user_branches')
            ->select('DISTINCT user_id')
            ->whereIn('branch_id', $branchIds)
            ->get()
            ->getResult();

        $ids = array_map(static fn(object $row): int => (int) $row->user_id, $rows);
        if (! in_array($actorUserId, $ids, true)) {
            $ids[] = $actorUserId;
        }

        return $ids;
    }

    /**
     * @param list<int> $allowedUserIds
     * @return array<int, object>
     */
    protected function getUserOptions(array $allowedUserIds): array
    {
        if ($allowedUserIds === []) {
            return [];
        }

        return $this->userModel->withoutTenantScope()
            ->whereIn('id', $allowedUserIds)
            ->where('is_active', 1)
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->findAll();
    }

    protected function mapEntityTypeToModule(string $entityType): string
    {
        return match ($entityType) {
            'enquiry', 'enquiry_followup', 'enquiry_assignment', 'enquiry_status' => 'Enquiries',
            'user', 'user_branch', 'user_hierarchy' => 'Users',
            'tenant_branch' => 'Branches',
            'college' => 'Colleges',
            'tenant_setting', 'branch_setting', 'tenant_policy', 'tenant_master_data' => 'Settings',
            default => 'Activity',
        };
    }

    protected function buildActivityTitle(object $row): string
    {
        $entityType = (string) ($row->entity_type ?? '');
        $action = (string) ($row->action ?? '');
        $summary = trim((string) ($row->summary ?? ''));

        return match ($entityType) {
            'enquiry_followup' => match ($action) {
                'created' => 'Follow-up added',
                'updated' => 'Follow-up updated',
                'deleted' => 'Follow-up deleted',
                default => 'Follow-up activity',
            },
            'enquiry_assignment' => 'Enquiry reassigned',
            'enquiry_status' => 'Enquiry status changed',
            'user' => $action === 'created' ? 'User created' : 'User updated',
            'tenant_branch' => $action === 'created' ? 'Branch created' : 'Branch updated',
            'college' => match ($action) {
                'created' => 'College created',
                'updated' => 'College updated',
                'deleted' => 'College removed',
                default => 'College activity',
            },
            'user_branch' => match ($action) {
                'created' => 'User branch assigned',
                'updated' => 'User branch updated',
                'deleted' => 'User branch removed',
                default => 'User branch activity',
            },
            'user_hierarchy' => match ($action) {
                'created' => 'Reporting line created',
                'updated' => 'Reporting line updated',
                'deleted' => 'Reporting line removed',
                default => 'Reporting line activity',
            },
            'tenant_setting' => $action === 'created' ? 'Company setting created' : 'Company setting updated',
            'branch_setting' => $action === 'created' ? 'Branch setting created' : 'Branch setting updated',
            'tenant_policy' => $action === 'created' ? 'Policy override created' : 'Policy override updated',
            'tenant_master_data' => $action === 'created' ? 'Lookup override created' : 'Lookup override updated',
            default => $summary ?: 'Activity updated',
        };
    }

    protected function buildActivityChanges(object $row): array
    {
        $oldValues = $this->decodeAuditJson($row->old_values ?? null);
        $newValues = $this->decodeAuditJson($row->new_values ?? null);
        $fieldNames = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
        $changes = [];

        foreach ($fieldNames as $field) {
            if ($this->shouldHideAuditField((string) $row->entity_type, (string) $field)) {
                continue;
            }

            $changes[] = (object) [
                'field' => $this->labelAuditField((string) $field),
                'old_value' => $this->formatAuditValue((string) $field, $oldValues[$field] ?? null),
                'new_value' => $this->formatAuditValue((string) $field, $newValues[$field] ?? null),
            ];
        }

        return $changes;
    }

    protected function decodeAuditJson(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function shouldHideAuditField(string $entityType, string $field): bool
    {
        $hidden = ['updated_at', 'created_at', 'tenant_id', 'updated_by', 'created_by'];
        if (in_array($field, $hidden, true)) {
            return true;
        }

        if ($entityType === 'enquiry' && in_array($field, ['last_followup_at', 'next_followup_at'], true)) {
            return true;
        }

        if ($entityType === 'user' && $field === 'must_reset_password') {
            return true;
        }

        return false;
    }

    protected function labelAuditField(string $field): string
    {
        return match ($field) {
            'role_id' => 'Role',
            'employee_code' => 'Employee code',
            'mobile_number' => 'Mobile number',
            'whatsapp_number' => 'WhatsApp number',
            'branch_id' => 'Branch',
            'owner_user_id' => 'Assigned to',
            'source_id' => 'Enquiry source',
            'college_id' => 'College',
            'qualification_id' => 'Lead stage',
            'primary_course_id' => 'Course',
            'closed_reason_id' => 'Close reason',
            'lifecycle_status' => 'Status',
            'communication_type_id' => 'Communication mode',
            'followup_outcome_id' => 'Follow-up outcome',
            'manager_user_id' => 'Reporting manager',
            'acting_manager_user_id' => 'Acting manager',
            'state_code' => 'State / region',
            'currency_code' => 'Currency',
            'country_code' => 'Country',
            'is_primary' => 'Primary branch',
            'override_value' => 'Override value',
            'master_data_value_id' => 'Lookup value',
            'is_visible' => 'Visible',
            'sort_order_override' => 'Sort order',
            'label_override' => 'Label override',
            default => ucwords(str_replace('_', ' ', $field)),
        };
    }

    protected function formatAuditValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return match ($field) {
            'role_id' => $this->resolveRoleLabel((int) $value),
            'source_id', 'qualification_id', 'primary_course_id', 'closed_reason_id', 'communication_type_id', 'followup_outcome_id', 'master_data_value_id' => $this->resolveMasterValueLabel((int) $value),
            'college_id' => $this->resolveCollegeLabel((int) $value),
            'branch_id', 'from_branch_id', 'to_branch_id' => $this->resolveBranchLabel((int) $value),
            'owner_user_id', 'closed_by', 'from_user_id', 'to_user_id', 'user_id', 'manager_user_id', 'acting_manager_user_id' => $this->resolveUserLabel((int) $value),
            'lifecycle_status', 'from_status', 'to_status', 'status', 'lock_mode' => ucfirst((string) $value),
            'is_primary', 'is_visible', 'is_active', 'is_system_generated' => (int) $value === 1 ? 'Yes' : 'No',
            'last_followup_at', 'next_followup_at', 'closed_at', 'admitted_at', 'assigned_on' => date('d M Y h:i A', strtotime((string) $value)),
            default => (string) $value,
        };
    }

    protected function resolveRoleLabel(int $id): string
    {
        if ($id < 1) {
            return '-';
        }

        $row = db_connect()->table('user_roles')->select('name')->where('id', $id)->get()->getRow();
        return $row->name ?? '-';
    }

    protected function resolveMasterValueLabel(int $id): string
    {
        if ($id < 1) {
            return '-';
        }

        $row = db_connect()->table('master_data_values')->select('label')->where('id', $id)->get()->getRow();
        return $row->label ?? '-';
    }

    protected function resolveCollegeLabel(int $id): string
    {
        if ($id < 1) {
            return '-';
        }

        $row = db_connect()->table('colleges')->select('name')->where('id', $id)->get()->getRow();
        return $row->name ?? '-';
    }

    protected function resolveBranchLabel(int $id): string
    {
        if ($id < 1) {
            return '-';
        }

        $row = db_connect()->table('tenant_branches')->select('name')->where('id', $id)->get()->getRow();
        return $row->name ?? '-';
    }

    protected function resolveUserLabel(int $id): string
    {
        if ($id < 1) {
            return '-';
        }

        $row = db_connect()->table('users')
            ->select("TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) AS name")
            ->where('id', $id)
            ->get()
            ->getRow();

        return trim((string) ($row->name ?? '')) ?: '-';
    }
}
