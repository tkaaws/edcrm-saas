<?php

namespace App\Services;

use App\Models\TenantModel;
use RuntimeException;

/**
 * TenantResolver
 *
 * Resolves the current tenant from session.
 * This is the single authoritative source for "which tenant is active."
 *
 * In v1, tenant identity comes from the session (set at login).
 * Future: can be extended to resolve from subdomain or slug.
 */
class TenantResolver
{
    protected TenantModel $tenantModel;
    protected ?object $resolvedTenant = null;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
    }

    /**
     * Resolve and return the current tenant.
     * Throws if no tenant in session or tenant not found.
     * Result is cached for the request lifecycle.
     */
    public function resolve(): object
    {
        if ($this->resolvedTenant !== null) {
            return $this->resolvedTenant;
        }

        $tenantId = session()->get('tenant_id');

        if (! $tenantId) {
            throw new RuntimeException('No tenant in session. User must be logged in.');
        }

        $tenant = $this->tenantModel->find((int) $tenantId);

        if (! $tenant) {
            throw new RuntimeException("Tenant [{$tenantId}] not found.");
        }

        $this->resolvedTenant = $tenant;
        return $this->resolvedTenant;
    }

    /**
     * Return current tenant without throwing.
     * Returns null if not authenticated or tenant not found.
     */
    public function tryResolve(): ?object
    {
        try {
            return $this->resolve();
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * Return current tenant_id from session without DB query.
     */
    public function getTenantId(): ?int
    {
        $id = session()->get('tenant_id');
        return $id ? (int) $id : null;
    }

    /**
     * Store tenant identity in session after login.
     * Called by AuthService on successful login.
     */
    public function setSession(int $tenantId): void
    {
        session()->set('tenant_id', $tenantId);
        $this->resolvedTenant = null; // reset cache
    }

    /**
     * Clear tenant from session on logout.
     */
    public function clearSession(): void
    {
        session()->remove('tenant_id');
        $this->resolvedTenant = null;
    }
}
