<?php

namespace App\Services;

use DateTimeImmutable;

class AdmissionQueueService
{
    protected \CodeIgniter\Database\BaseConnection $db;
    protected UserAccessScopeService $userAccessScope;

    public function __construct()
    {
        $this->db = db_connect();
        $this->userAccessScope = service('userAccessScope');
    }

    public function getRows(int $tenantId, string $queue, ?int $branchContextId = null): array
    {
        $rows = $this->getBaseRows($tenantId);
        $rows = array_values(array_filter($rows, fn(object $row): bool => $this->canViewRow($tenantId, $row, $branchContextId)));

        foreach ($rows as $row) {
            $this->decorateRow($row);
        }

        return array_values(array_filter($rows, fn(object $row): bool => $this->matchesQueue($row, $queue)));
    }

    public function findVisibleById(int $tenantId, int $admissionId, ?int $branchContextId = null): ?object
    {
        $row = $this->db->table('admissions a')
            ->select($this->baseSelect())
            ->join('tenant_branches b', 'b.id = a.branch_id', 'left')
            ->join('users assigned_user', 'assigned_user.id = a.assigned_user_id', 'left')
            ->join('users created_user', 'created_user.id = a.created_by', 'left')
            ->join('users updated_user', 'updated_user.id = a.updated_by', 'left')
            ->join('master_data_values course', 'course.id = a.course_id', 'left')
            ->join('colleges college', 'college.id = a.college_id', 'left')
            ->join('admission_fee_snapshots snapshot', 'snapshot.admission_id = a.id', 'left')
            ->where('a.tenant_id', $tenantId)
            ->where('a.id', $admissionId)
            ->get()
            ->getRow();

        if (! $row || ! $this->canViewRow($tenantId, $row, $branchContextId)) {
            return null;
        }

        $this->decorateRow($row);
        return $row;
    }

    /**
     * @return array<int, object>
     */
    protected function getBaseRows(int $tenantId): array
    {
        return $this->db->table('admissions a')
            ->select($this->baseSelect())
            ->join('tenant_branches b', 'b.id = a.branch_id', 'left')
            ->join('users assigned_user', 'assigned_user.id = a.assigned_user_id', 'left')
            ->join('users created_user', 'created_user.id = a.created_by', 'left')
            ->join('users updated_user', 'updated_user.id = a.updated_by', 'left')
            ->join('master_data_values course', 'course.id = a.course_id', 'left')
            ->join('colleges college', 'college.id = a.college_id', 'left')
            ->join('admission_fee_snapshots snapshot', 'snapshot.admission_id = a.id', 'left')
            ->where('a.tenant_id', $tenantId)
            ->orderBy('a.created_at', 'DESC')
            ->get()
            ->getResult();
    }

    protected function baseSelect(): string
    {
        return implode(', ', [
            'a.*',
            'b.name AS branch_name',
            'course.label AS course_label',
            'college.name AS college_name',
            "TRIM(CONCAT(COALESCE(assigned_user.first_name, ''), ' ', COALESCE(assigned_user.last_name, ''))) AS assigned_user_name",
            "TRIM(CONCAT(COALESCE(created_user.first_name, ''), ' ', COALESCE(created_user.last_name, ''))) AS created_by_name",
            "TRIM(CONCAT(COALESCE(updated_user.first_name, ''), ' ', COALESCE(updated_user.last_name, ''))) AS updated_by_name",
            'snapshot.fee_plan_label',
            'snapshot.fee_structure_id',
            'snapshot.gross_amount',
            'snapshot.discount_amount',
            'snapshot.net_amount',
            'snapshot.paid_amount',
            'snapshot.balance_amount',
        ]);
    }

    protected function canViewRow(int $tenantId, object $row, ?int $branchContextId = null): bool
    {
        $actor = $this->userAccessScope->getActor();
        if (! $actor || (int) $actor->tenant_id !== $tenantId) {
            return false;
        }

        $behavior = $this->userAccessScope->getActorAccessBehavior();
        if ($behavior === 'tenant') {
            return true;
        }

        $branchIds = $this->userAccessScope->getAssignedBranchIdsForActor();
        if ($behavior === 'branch') {
            return in_array((int) ($row->branch_id ?? 0), $branchIds, true);
        }

        $userIds = $this->userAccessScope->getHierarchyScopeUserIdsForActor(true);
        return in_array((int) ($row->assigned_user_id ?? 0), $userIds, true)
            || in_array((int) ($row->branch_id ?? 0), $branchIds, true);
    }

    protected function decorateRow(object $row): void
    {
        $row->branch_display = $row->branch_name ?: '-';
        $row->course_display = $row->course_label ?: '-';
        $row->assigned_user_display = trim((string) ($row->assigned_user_name ?? '')) ?: 'Unassigned';
        $row->created_by_display = trim((string) ($row->created_by_name ?? '')) ?: '-';
        $row->updated_by_display = trim((string) ($row->updated_by_name ?? '')) ?: '-';
        $row->net_amount = (float) ($row->net_amount ?? 0);
        $row->paid_amount = (float) ($row->paid_amount ?? 0);
        $row->balance_amount = (float) ($row->balance_amount ?? 0);
        $row->batch_pending = empty($row->current_batch_id);
    }

    protected function matchesQueue(object $row, string $queue): bool
    {
        return match ($queue) {
            'pending_fees' => $row->status === 'active' && (float) $row->balance_amount > 0,
            'today_followup' => $row->status === 'active' && $this->isDueToday($row->next_followup_at),
            'missed_followup' => $row->status === 'active' && $this->isMissed($row->next_followup_at),
            'batch_pending' => $row->status === 'active' && $row->batch_pending,
            'on_hold' => $row->status === 'on_hold',
            'cancelled' => $row->status === 'cancelled',
            default => in_array($row->status, ['active', 'completed'], true),
        };
    }

    protected function isDueToday(?string $datetime): bool
    {
        if (! $datetime) {
            return false;
        }

        return (new DateTimeImmutable($datetime))->format('Y-m-d') === (new DateTimeImmutable('today'))->format('Y-m-d');
    }

    protected function isMissed(?string $datetime): bool
    {
        if (! $datetime) {
            return false;
        }

        return (new DateTimeImmutable($datetime))->format('Y-m-d') < (new DateTimeImmutable('today'))->format('Y-m-d');
    }
}
