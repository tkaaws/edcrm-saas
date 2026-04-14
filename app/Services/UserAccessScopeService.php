<?php

namespace App\Services;

use App\Models\UserHierarchyModel;
use App\Models\UserModel;

class UserAccessScopeService
{
    protected const DATA_SCOPE_RANK = [
        'self'   => 1,
        'team'   => 2,
        'branch' => 3,
        'tenant' => 4,
        'custom' => 5,
    ];

    protected const MANAGE_SCOPE_RANK = [
        'none'      => 0,
        'self_only' => 1,
        'team'      => 2,
        'branch'    => 3,
        'tenant'    => 4,
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

    /**
     * @return list<string>
     */
    public function getAllowedDataScopes(): array
    {
        if ($this->isPlatformAdmin()) {
            return array_keys(self::DATA_SCOPE_RANK);
        }

        $actor = $this->getActor();
        if (! $actor) {
            return [];
        }

        $effectiveScope = $this->getEffectiveDataScope($actor);
        $maxRank = self::DATA_SCOPE_RANK[$effectiveScope] ?? 1;

        return array_values(array_filter(
            array_keys(self::DATA_SCOPE_RANK),
            static fn(string $scope): bool => (self::DATA_SCOPE_RANK[$scope] ?? 0) <= $maxRank
        ));
    }

    /**
     * @return list<string>
     */
    public function getAllowedManageScopes(): array
    {
        if ($this->isPlatformAdmin()) {
            return array_keys(self::MANAGE_SCOPE_RANK);
        }

        $actor = $this->getActor();
        if (! $actor) {
            return [];
        }

        $effectiveScope = $this->getEffectiveManageScope($actor);
        $maxRank = self::MANAGE_SCOPE_RANK[$effectiveScope] ?? 0;

        return array_values(array_filter(
            array_keys(self::MANAGE_SCOPE_RANK),
            static fn(string $scope): bool => (self::MANAGE_SCOPE_RANK[$scope] ?? 0) <= $maxRank
        ));
    }

    public function canAssignScopes(string $dataScope, string $manageScope): bool
    {
        return in_array($dataScope, $this->getAllowedDataScopes(), true)
            && in_array($manageScope, $this->getAllowedManageScopes(), true);
    }

    public function canManageTargetUser(object $targetUser): bool
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

        $manageScope = $this->getEffectiveManageScope($actor);

        return match ($manageScope) {
            'tenant' => true,
            'branch' => $this->sharesPrimaryBranch((int) $actor->id, (int) $targetUser->id),
            'team' => $this->isDirectReport((int) $targetUser->id, (int) $actor->id),
            'self_only' => (int) $actor->id === (int) $targetUser->id,
            default => false,
        };
    }

    /**
     * @param list<int> $branchIds
     */
    public function canAssignBranches(array $branchIds): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        $actor = $this->getActor();
        if (! $actor) {
            return false;
        }

        $manageScope = $this->getEffectiveManageScope($actor);
        if ($manageScope === 'tenant') {
            return true;
        }

        $actorPrimaryBranch = $this->userModel->getPrimaryBranch((int) $actor->id);
        if (! $actorPrimaryBranch) {
            return false;
        }

        if (! in_array($manageScope, ['branch', 'team'], true)) {
            return false;
        }

        foreach ($branchIds as $branchId) {
            if ((int) $branchId !== (int) $actorPrimaryBranch->id) {
                return false;
            }
        }

        return true;
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

        $manageScope = $this->getEffectiveManageScope($actor);
        return match ($manageScope) {
            'tenant' => true,
            'branch', 'team' => $this->sharesPrimaryBranch((int) $actor->id, $managerUserId),
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

        $manageScope = $this->getEffectiveManageScope($actor);
        if ($manageScope === 'tenant') {
            return $branches;
        }

        if (! in_array($manageScope, ['branch', 'team'], true)) {
            return [];
        }

        $actorPrimaryBranch = $this->userModel->getPrimaryBranch((int) $actor->id);
        if (! $actorPrimaryBranch) {
            return [];
        }

        return array_values(array_filter(
            $branches,
            static fn(object $branch): bool => (int) $branch->id === (int) $actorPrimaryBranch->id
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

    protected function sharesPrimaryBranch(int $actorUserId, int $targetUserId): bool
    {
        $actorBranch = $this->userModel->getPrimaryBranch($actorUserId);
        $targetBranch = $this->userModel->getPrimaryBranch($targetUserId);

        if (! $actorBranch || ! $targetBranch) {
            return false;
        }

        return (int) $actorBranch->id === (int) $targetBranch->id;
    }

    protected function isDirectReport(int $userId, int $managerUserId): bool
    {
        $hierarchy = $this->userHierarchyModel->withoutTenantScope()
                                              ->where('user_id', $userId)
                                              ->first();

        return $hierarchy && (int) $hierarchy->manager_user_id === $managerUserId;
    }

    protected function getEffectiveDataScope(object $user): string
    {
        $stored = (string) ($user->data_scope ?? '');
        if ($stored !== '' && ! ($stored === 'self' && $this->shouldUseRoleFallback($user))) {
            return $stored;
        }

        return match ($this->getRoleCodeForUser($user)) {
            'tenant_owner', 'tenant_admin' => 'tenant',
            'branch_manager' => 'branch',
            'operations', 'accounts', 'placement', 'faculty', 'support_agent' => 'branch',
            default => $stored !== '' ? $stored : 'self',
        };
    }

    protected function getEffectiveManageScope(object $user): string
    {
        $stored = (string) ($user->manage_scope ?? '');
        if ($stored !== '' && ! ($stored === 'none' && $this->shouldUseRoleFallback($user))) {
            return $stored;
        }

        return match ($this->getRoleCodeForUser($user)) {
            'tenant_owner', 'tenant_admin' => 'tenant',
            'branch_manager' => 'branch',
            default => $stored !== '' ? $stored : 'none',
        };
    }

    protected function shouldUseRoleFallback(object $user): bool
    {
        return ! isset($user->data_scope, $user->manage_scope)
            || ((string) ($user->data_scope ?? 'self') === 'self' && (string) ($user->manage_scope ?? 'none') === 'none');
    }

    protected function getRoleCodeForUser(object $user): string
    {
        if (isset($user->role_code) && $user->role_code !== null) {
            return (string) $user->role_code;
        }

        if ((int) ($user->role_id ?? 0) < 1) {
            return '';
        }

        $row = db_connect()->table('user_roles')
            ->select('code')
            ->where('id', (int) $user->role_id)
            ->get()
            ->getRow();

        return (string) ($row->code ?? '');
    }
}
