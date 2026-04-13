<?php

namespace App\Services;

use App\Models\UserModel;

/**
 * CurrentUserContext
 *
 * Single source of truth for who the current user is,
 * what tenant and branch they belong to, and what they can do.
 *
 * Use this in controllers and views instead of querying
 * user/tenant/branch individually.
 *
 * Usage:
 *   $ctx = new CurrentUserContext();
 *   $ctx->user()      → current user object
 *   $ctx->tenant()    → current tenant object
 *   $ctx->branch()    → current active branch object
 *   $ctx->can('enquiries.create')  → bool
 *   $ctx->timezone()  → resolved timezone string
 *   $ctx->currency()  → resolved currency code
 */
class CurrentUserContext
{
    protected UserModel $userModel;
    protected TenantResolver $tenantResolver;
    protected BranchContextResolver $branchResolver;
    protected PermissionService $permissionService;

    protected ?object $user   = null;
    protected ?object $tenant = null;
    protected ?object $branch = null;

    public function __construct()
    {
        $this->userModel         = new UserModel();
        $this->tenantResolver    = new TenantResolver();
        $this->branchResolver    = new BranchContextResolver();
        $this->permissionService = new PermissionService();
    }

    /**
     * Current authenticated user.
     */
    public function user(): ?object
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $userId = session()->get('user_id');
        if (! $userId) {
            return null;
        }

        $this->user = $this->userModel->find((int) $userId);
        return $this->user;
    }

    /**
     * Current tenant.
     */
    public function tenant(): ?object
    {
        if ($this->tenant !== null) {
            return $this->tenant;
        }

        $this->tenant = $this->tenantResolver->tryResolve();
        return $this->tenant;
    }

    /**
     * Current active branch.
     */
    public function branch(): ?object
    {
        if ($this->branch !== null) {
            return $this->branch;
        }

        $this->branch = $this->branchResolver->tryResolve();
        return $this->branch;
    }

    /**
     * All branches available to the current user.
     */
    public function availableBranches(): array
    {
        $user = $this->user();
        if (! $user) return [];
        return $this->userModel->getBranches($user->id);
    }

    /**
     * Check if current user has a privilege.
     */
    public function can(string $privilege): bool
    {
        return $this->permissionService->has($privilege);
    }

    /**
     * Check if current user has all given privileges.
     */
    public function canAll(array $privileges): bool
    {
        return $this->permissionService->hasAll($privileges);
    }

    /**
     * Check if current user has any of the given privileges.
     */
    public function canAny(array $privileges): bool
    {
        return $this->permissionService->hasAny($privileges);
    }

    /**
     * Check if current user has access to a module at all.
     */
    public function canAccessModule(string $module): bool
    {
        return $this->permissionService->hasModule($module);
    }

    /**
     * Resolved effective timezone for current context.
     */
    public function timezone(): string
    {
        return $this->branchResolver->resolveTimezone(
            $this->branch(),
            $this->tenant()
        );
    }

    /**
     * Resolved effective currency for current context.
     */
    public function currency(): string
    {
        return $this->branchResolver->resolveCurrency(
            $this->branch(),
            $this->tenant()
        );
    }

    /**
     * Check if a user is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return session()->get('user_id') !== null;
    }

    /**
     * Check if current user's role code matches.
     */
    public function hasRole(string $roleCode): bool
    {
        return session()->get('user_role_code') === $roleCode;
    }

    /**
     * Check if current user is tenant owner.
     */
    public function isTenantOwner(): bool
    {
        return $this->hasRole('tenant_owner');
    }

    /**
     * Check if current user is tenant admin or owner.
     */
    public function isTenantAdmin(): bool
    {
        return in_array(
            session()->get('user_role_code'),
            ['tenant_owner', 'tenant_admin'],
            true
        );
    }

    /**
     * Full context snapshot — useful for passing to views.
     */
    public function toArray(): array
    {
        return [
            'user'      => $this->user(),
            'tenant'    => $this->tenant(),
            'branch'    => $this->branch(),
            'timezone'  => $this->timezone(),
            'currency'  => $this->currency(),
            'branches'  => $this->availableBranches(),
        ];
    }
}
