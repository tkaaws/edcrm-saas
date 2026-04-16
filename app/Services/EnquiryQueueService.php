<?php

namespace App\Services;

use DateTimeImmutable;

class EnquiryQueueService
{
    protected \CodeIgniter\Database\BaseConnection $db;
    protected SettingsResolverService $settingsResolver;
    protected UserAccessScopeService $userAccessScope;

    public function __construct()
    {
        $this->db = db_connect();
        $this->settingsResolver = service('settingsResolver');
        $this->userAccessScope = service('userAccessScope');
    }

    public function getRows(int $tenantId, string $queue, ?int $branchContextId = null): array
    {
        $expiryDays = (int) ($this->settingsResolver->getEffectiveSetting($tenantId, $branchContextId, 'enquiry.lifecycle.expiry_days') ?: 30);
        $rows = $this->getBaseRows($tenantId);
        $rows = array_values(array_filter($rows, fn(object $row): bool => $this->canViewRow($tenantId, $row, $branchContextId)));

        foreach ($rows as $row) {
            $this->decorateRow($row, $expiryDays);
        }

        return array_values(array_filter($rows, fn(object $row): bool => $this->matchesQueue($row, $queue)));
    }

    public function getVisibleRows(int $tenantId, ?int $branchContextId = null): array
    {
        $expiryDays = (int) ($this->settingsResolver->getEffectiveSetting($tenantId, $branchContextId, 'enquiry.lifecycle.expiry_days') ?: 30);
        $rows = $this->getBaseRows($tenantId);
        $rows = array_values(array_filter($rows, fn(object $row): bool => $this->canViewRow($tenantId, $row, $branchContextId)));

        foreach ($rows as $row) {
            $this->decorateRow($row, $expiryDays);
        }

        return $rows;
    }

    public function findVisibleById(int $tenantId, int $enquiryId, ?int $branchContextId = null): ?object
    {
        $expiryDays = (int) ($this->settingsResolver->getEffectiveSetting($tenantId, $branchContextId, 'enquiry.lifecycle.expiry_days') ?: 30);
        $row = $this->db->table('enquiries e')
            ->select($this->baseSelect())
            ->join('tenant_branches b', 'b.id = e.branch_id', 'left')
            ->join('users owner_user', 'owner_user.id = e.owner_user_id', 'left')
            ->join('users created_user', 'created_user.id = e.created_by', 'left')
            ->join('users updated_user', 'updated_user.id = e.updated_by', 'left')
            ->join('users closed_user', 'closed_user.id = e.closed_by', 'left')
            ->join('master_data_values source', 'source.id = e.source_id', 'left')
            ->join('master_data_values course', 'course.id = e.primary_course_id', 'left')
            ->join('master_data_values qualification', 'qualification.id = e.qualification_id', 'left')
            ->join('master_data_values close_reason', 'close_reason.id = e.closed_reason_id', 'left')
            ->join('colleges college', 'college.id = e.college_id', 'left')
            ->where('e.tenant_id', $tenantId)
            ->where('e.id', $enquiryId)
            ->get()
            ->getRow();

        if (! $row || ! $this->canViewRow($tenantId, $row, $branchContextId)) {
            return null;
        }

        $this->decorateRow($row, $expiryDays);

        return $row;
    }

    /**
     * @return array<int, object>
     */
    protected function getBaseRows(int $tenantId): array
    {
        return $this->db->table('enquiries e')
            ->select($this->baseSelect())
            ->join('tenant_branches b', 'b.id = e.branch_id', 'left')
            ->join('users owner_user', 'owner_user.id = e.owner_user_id', 'left')
            ->join('users created_user', 'created_user.id = e.created_by', 'left')
            ->join('users updated_user', 'updated_user.id = e.updated_by', 'left')
            ->join('users closed_user', 'closed_user.id = e.closed_by', 'left')
            ->join('master_data_values source', 'source.id = e.source_id', 'left')
            ->join('master_data_values course', 'course.id = e.primary_course_id', 'left')
            ->join('master_data_values qualification', 'qualification.id = e.qualification_id', 'left')
            ->join('master_data_values close_reason', 'close_reason.id = e.closed_reason_id', 'left')
            ->join('colleges college', 'college.id = e.college_id', 'left')
            ->where('e.tenant_id', $tenantId)
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResult();
    }

    protected function baseSelect(): string
    {
        return implode(', ', [
            'e.*',
            'b.name AS branch_name',
            'source.label AS source_label',
            'course.label AS course_label',
            'qualification.label AS qualification_label',
            'close_reason.label AS close_reason_label',
            'college.name AS college_name',
            'college.city_name AS college_city_name',
            'college.state_name AS college_state_name',
            "TRIM(CONCAT(COALESCE(owner_user.first_name, ''), ' ', COALESCE(owner_user.last_name, ''))) AS owner_name",
            "TRIM(CONCAT(COALESCE(created_user.first_name, ''), ' ', COALESCE(created_user.last_name, ''))) AS created_by_name",
            "TRIM(CONCAT(COALESCE(updated_user.first_name, ''), ' ', COALESCE(updated_user.last_name, ''))) AS updated_by_name",
            "TRIM(CONCAT(COALESCE(closed_user.first_name, ''), ' ', COALESCE(closed_user.last_name, ''))) AS closed_by_name",
        ]);
    }

    protected function canViewRow(int $tenantId, object $row, ?int $branchContextId = null): bool
    {
        $actor = $this->userAccessScope->getActor();
        if (! $actor || (int) $actor->tenant_id !== $tenantId) {
            return false;
        }

        $actorId = (int) $actor->id;
        $visibilityMode = (string) ($this->settingsResolver->getEffectiveSetting($tenantId, $branchContextId, 'enquiry.visibility.mode') ?: 'assigned_branches');
        $behavior = $this->userAccessScope->getActorAccessBehavior();

        if ($visibilityMode === 'self') {
            return (int) ($row->owner_user_id ?? 0) === $actorId;
        }

        if ($behavior === 'tenant') {
            return true;
        }

        if ($behavior === 'branch') {
            $branchIds = $this->userAccessScope->getAssignedBranchIdsForActor();
            return in_array((int) ($row->branch_id ?? 0), $branchIds, true);
        }

        $userIds = $this->userAccessScope->getHierarchyScopeUserIdsForActor(true);
        return in_array((int) ($row->owner_user_id ?? 0), $userIds, true);
    }

    protected function decorateRow(object $row, int $expiryDays): void
    {
        $row->owner_display = trim((string) ($row->owner_name ?? '')) ?: 'Unassigned';
        $row->created_by_display = trim((string) ($row->created_by_name ?? '')) ?: '-';
        $row->updated_by_display = trim((string) ($row->updated_by_name ?? '')) ?: '-';
        $row->closed_by_display = trim((string) ($row->closed_by_name ?? '')) ?: '-';
        $row->source_display = $row->source_label ?: '-';
        $row->course_display = $row->course_label ?: '-';
        $row->branch_display = $row->branch_name ?: '-';
        $row->is_fresh = $row->last_followup_at === null;
        $row->expired_on = $this->getExpiredOn($row, $expiryDays);
        $row->is_expired = $this->isExpired($row, $expiryDays);
        $row->queue_status = $this->resolveQueueStatus($row);
        $row->overdue_by = $this->getOverdueByDays($row);
    }

    protected function matchesQueue(object $row, string $queue): bool
    {
        return match ($queue) {
            'today' => in_array($row->lifecycle_status, ['new', 'active'], true) && ! $row->is_expired && $this->isDueToday($row),
            'missed' => in_array($row->lifecycle_status, ['new', 'active'], true) && ! $row->is_expired && $this->isMissed($row),
            'fresh' => in_array($row->lifecycle_status, ['new', 'active'], true) && ! $row->is_expired && $row->is_fresh,
            'expired' => in_array($row->lifecycle_status, ['new', 'active'], true) && $row->is_expired,
            'closed' => $row->lifecycle_status === 'closed',
            default => in_array($row->lifecycle_status, ['new', 'active'], true) && ! $row->is_expired,
        };
    }

    protected function resolveQueueStatus(object $row): string
    {
        if ($row->lifecycle_status === 'closed') {
            return 'Closed';
        }

        if ($row->lifecycle_status === 'admitted') {
            return 'Admitted';
        }

        if ($row->is_expired) {
            return 'Expired';
        }

        if ($this->isMissed($row)) {
            return 'Missed';
        }

        if ($this->isDueToday($row)) {
            return 'Today';
        }

        if ($row->is_fresh) {
            return 'Fresh';
        }

        return ucfirst((string) $row->lifecycle_status);
    }

    protected function isExpired(object $row, int $expiryDays): bool
    {
        if (! in_array($row->lifecycle_status, ['new', 'active'], true)) {
            return false;
        }

        $expiredOn = $this->getExpiredOn($row, $expiryDays);
        if ($expiredOn === null) {
            return false;
        }

        return (new DateTimeImmutable($expiredOn)) < new DateTimeImmutable('today');
    }

    protected function getExpiredOn(object $row, int $expiryDays): ?string
    {
        $baseDate = $row->last_followup_at ?: $row->created_at;
        if (! $baseDate) {
            return null;
        }

        return (new DateTimeImmutable($baseDate))
            ->modify('+' . $expiryDays . ' days')
            ->format('Y-m-d');
    }

    protected function isDueToday(object $row): bool
    {
        if (! $row->next_followup_at) {
            return false;
        }

        return (new DateTimeImmutable($row->next_followup_at))->format('Y-m-d') === (new DateTimeImmutable('today'))->format('Y-m-d');
    }

    protected function isMissed(object $row): bool
    {
        if (! $row->next_followup_at) {
            return false;
        }

        return (new DateTimeImmutable($row->next_followup_at))->format('Y-m-d') < (new DateTimeImmutable('today'))->format('Y-m-d');
    }

    protected function getOverdueByDays(object $row): ?int
    {
        if (! $row->next_followup_at || ! $this->isMissed($row)) {
            return null;
        }

        $due = new DateTimeImmutable((new DateTimeImmutable($row->next_followup_at))->format('Y-m-d'));
        $today = new DateTimeImmutable('today');
        return (int) $today->diff($due)->format('%a');
    }
}
