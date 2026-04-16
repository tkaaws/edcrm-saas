<?php

namespace App\Services;

use App\Models\UserHierarchyModel;
use App\Models\UserModel;

class UserAccessScopeService
{
    protected const ACCESS_BEHAVIOR_RANK = [
        'hierarchy' => 1,
        'branch'    => 2,
        'tenant'    => 3,
    ];

    protected UserModel $userModel;
    protected UserHierarchyModel $userHierarchyModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->userHierarchyModel = new UserHierarchyModel();
    }

    public function isPlatformAdmin(): bool
    {
        return session()->get('user_role_code') === 'platform_admin';
    }

    public function getActor(): ?object
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return null;
        }

        return $this->userModel->withoutTenantScope()->find((int) $userId);
    }

    public function getActorAccessBehavior(): string
    {
        if ($this->isPlatformAdmin()) {
            return 'tenant';
        }

        $actor = $this->getActor();
        if (! $actor) {
            return 'hierarchy';
        }

        return $this->getRoleAccessBehaviorForUser($actor);
    }

    public function canGrantRoleBehavior(string $behavior): bool
    {
        if (! isset(self::ACCESS_BEHAVIOR_RANK[$behavior])) {
            return false;
        }

        if ($this->isPlatformAdmin()) {
            return true;
        }

        $actorBehavior = $this->getActorAccessBehavior();

        return (self::ACCESS_BEHAVIOR_RANK[$behavior] ?? 0) <= (self::ACCESS_BEHAVIOR_RANK[$actorBehavior] ?? 0);
    }

    public function canViewTargetUser(object $targetUser): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        $actor = $this->getActor();
        if (! $actor) {
            return false;
        }

        if ((int) $actor->tenant_id !== (int) $targetUser->tenant_id) {
            return false;
        }

        return match ($this->getActorAccessBehavior()) {
            'tenant' => true,
            'branch' => $this->sharesAnyAssignedBranch((int) $actor->id, (int) $targetUser->id),
            'hierarchy' => (int) $actor->id === (int) $targetUser->id || $this->isInDownline((int) $targetUser->id, (int) $actor->id),
            default => false,
        };
    }

    public function canManageTargetUser(object $targetUser): bool
    {
        if (! $this->hasUserManagementPrivileges()) {
            return false;
        }

        return $this->canViewTargetUser($targetUser);
    }

    /**
     * @param list<int> $branchIds
     */
    public function canAssignBranches(array $branchIds): bool
    {
        if ($branchIds === []) {
            return false;
        }

        if ($this->isPlatformAdmin()) {
            return true;
        }

        $actor = $this->getActor();
        if (! $actor) {
            return false;
        }

        if ($this->getActorAccessBehavior() === 'tenant') {
            return true;
        }

        $allowedBranchIds = $this->getAssignedBranchIds((int) $actor->id);
        if ($allowedBranchIds === []) {
            return false;
        }

        foreach ($branchIds as $branchId) {
            if (! in_array((int) $branchId, $allowedBranchIds, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<int>
     */
    public function getAssignedBranchIdsForActor(): array
    {
        $actor = $this->getActor();
        if (! $actor) {
            return [];
        }

        return $this->getAssignedBranchIds((int) $actor->id);
    }

    /**
     * @return list<int>
     */
    public function getHierarchyScopeUserIdsForActor(bool $includeActor = true): array
    {
        $actor = $this->getActor();
        if (! $actor) {
            return [];
        }

        $actorId = (int) $actor->id;
        $ids = $includeActor ? [$actorId] : [];
        $queue = [$actorId];
        $seen = [$actorId => true];

        while ($queue !== []) {
            $currentManagerId = array_shift($queue);
            $rows = $this->userHierarchyModel->withoutTenantScope()
                ->where('manager_user_id', $currentManagerId)
                ->findAll();

            foreach ($rows as $row) {
                $userId = (int) $row->user_id;
                if (isset($seen[$userId])) {
                    continue;
                }

                $seen[$userId] = true;
                $ids[] = $userId;
                $queue[] = $userId;
            }
        }

        return $ids;
    }

    public function canAssignManager(?int $managerUserId): bool
    {
        if ($managerUserId === null || $managerUserId < 1) {
            return true;
        }

        if ($this->isPlatformAdmin()) {
            return true;
        }

        $manager = $this->userModel->findForTenant($managerUserId);
        if (! $manager) {
            return false;
        }

        $actor = $this->getActor();
        if (! $actor) {
            return false;
        }

        return match ($this->getActorAccessBehavior()) {
            'tenant' => true,
            'branch' => $this->sharesAnyAssignedBranch((int) $actor->id, $managerUserId),
            'hierarchy' => $managerUserId === (int) $actor->id || $this->isInDownline($managerUserId, (int) $actor->id),
            default => false,
        };
    }

    /**
     * @param array<int, object> $branches
     * @return array<int, object>
     */
    public function filterAssignableBranches(array $branches): array
    {
        if ($this->isPlatformAdmin()) {
            return $branches;
        }

        $actor = $this->getActor();
        if (! $actor) {
            return [];
        }

        if ($this->getActorAccessBehavior() === 'tenant') {
            return $branches;
        }

        $assignedBranchIds = $this->getAssignedBranchIds((int) $actor->id);

        return array_values(array_filter(
            $branches,
            static fn(object $branch): bool => in_array((int) $branch->id, $assignedBranchIds, true)
        ));
    }

    /**
     * @return array<int, object>
     */
    public function getAllowedManagerOptions(int $tenantId, ?int $ignoreUserId = null): array
    {
        $users = $this->userModel->getManagerOptions($tenantId, $ignoreUserId);

        if ($this->isPlatformAdmin()) {
            return $users;
        }

        return array_values(array_filter(
            $users,
            fn(object $user): bool => $this->canAssignManager((int) $user->id)
        ));
    }

    public function hasUserManagementPrivileges(): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        $codes = session()->get('user_privilege_codes') ?? [];
        foreach (['users.create', 'users.edit', 'users.status'] as $code) {
            if (in_array($code, $codes, true)) {
                return true;
            }
        }

        return false;
    }

    protected function getRoleAccessBehaviorForUser(object $user): string
    {
        return $this->getRoleAccessBehaviorByRoleId((int) ($user->role_id ?? 0), isset($user->role_code) ? (string) $user->role_code : null);
    }

    protected function getRoleAccessBehaviorByRoleId(int $roleId, ?string $roleCode = null): string
    {
        if ($roleId < 1 && $roleCode === null) {
            return 'hierarchy';
        }

        $builder = db_connect()->table('user_roles')->select('access_behavior, code');
        if ($roleId > 0) {
            $builder->where('id', $roleId);
        } else {
            $builder->where('code', $roleCode);
        }

        $row = $builder->get()->getRow();
        $behavior = (string) ($row->access_behavior ?? '');
        if (isset(self::ACCESS_BEHAVIOR_RANK[$behavior])) {
            return $behavior;
        }

        return match ((string) ($row->code ?? $roleCode ?? '')) {
            'tenant_owner', 'tenant_admin' => 'tenant',
            'branch_manager', 'operations', 'accounts', 'placement', 'support_agent' => 'branch',
            default => 'hierarchy',
        };
    }

    /**
     * @return list<int>
     */
    protected function getAssignedBranchIds(int $userId): array
    {
        $rows = $this->userModel->getBranches($userId);
        return array_values(array_map(static fn(array $branch): int => (int) $branch['id'], $rows));
    }

    protected function sharesAnyAssignedBranch(int $actorUserId, int $targetUserId): bool
    {
        $actorBranchIds = $this->getAssignedBranchIds($actorUserId);
        $targetBranchIds = $this->getAssignedBranchIds($targetUserId);

        if ($actorBranchIds === [] || $targetBranchIds === []) {
            return false;
        }

        foreach ($targetBranchIds as $branchId) {
            if (in_array($branchId, $actorBranchIds, true)) {
                return true;
            }
        }

        return false;
    }

    protected function isInDownline(int $userId, int $managerUserId): bool
    {
        $guard = 0;
        $currentUserId = $userId;

        while ($guard < 25) {
            $hierarchy = $this->userHierarchyModel->withoutTenantScope()
                ->where('user_id', $currentUserId)
                ->first();

            if (! $hierarchy || $hierarchy->manager_user_id === null) {
                return false;
            }

            if ((int) $hierarchy->manager_user_id === $managerUserId) {
                return true;
            }

            $currentUserId = (int) $hierarchy->manager_user_id;
            $guard++;
        }

        return false;
    }
}
