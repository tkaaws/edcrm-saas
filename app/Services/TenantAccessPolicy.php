<?php

namespace App\Services;

use App\Models\TenantModel;

/**
 * TenantAccessPolicy
 *
 * Decides whether the current tenant status allows access to an operation.
 * Used by SuspensionFilter to enforce status-based restrictions.
 *
 * Phase 1A: checks tenant.status (active / suspended / cancelled / draft).
 * Phase 1B: extend check() to also validate subscription state (trial / grace / expired).
 *
 * Keeping this logic in a service (not inline in the filter) means:
 * - SuspensionFilter stays thin and never changes as billing rules grow
 * - TenantAccessPolicy can be unit-tested independently
 * - Phase 1B billing checks are added in one place
 */
class TenantAccessPolicy
{
    // Result constants returned by check()
    const ALLOW        = 'allow';
    const DENY_BLOCKED = 'deny_blocked';     // draft or cancelled — redirect to login
    const DENY_WRITE   = 'deny_write';       // suspended + write operation + non-admin
    const WARN_READ    = 'warn_read';        // suspended + read operation + non-admin

    protected TenantModel $tenantModel;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
    }

    /**
     * Evaluate access for the current request context.
     *
     * @param int    $tenantId   Tenant ID from session
     * @param string $roleCode   User role code from session
     * @param string $httpMethod HTTP method of the incoming request
     *
     * @return string  One of the ALLOW / DENY_* / WARN_* constants
     */
    public function check(int $tenantId, string $roleCode, string $httpMethod): string
    {
        $tenant = $this->tenantModel->find($tenantId);

        if (! $tenant) {
            // Tenant not found — treat as blocked
            return self::DENY_BLOCKED;
        }

        return match ($tenant->status) {
            'active'             => self::ALLOW,
            'suspended'          => $this->evaluateSuspended($roleCode, $httpMethod),
            'draft', 'cancelled' => self::DENY_BLOCKED,
            default              => self::ALLOW,
        };
    }

    /**
     * Get the tenant status message for DENY_BLOCKED cases.
     * Used to generate the appropriate redirect message.
     */
    public function blockedMessage(int $tenantId): string
    {
        $tenant = $this->tenantModel->find($tenantId);

        return match ($tenant?->status) {
            'draft'     => 'This account has not been activated yet.',
            'cancelled' => 'This account has been cancelled. Please contact support to retrieve your data.',
            default     => 'Access to this account is currently restricted.',
        };
    }

    // ---------------------------------------------------------------
    // Phase 1B extension point
    // ---------------------------------------------------------------
    //
    // When subscription state machine is added in Phase 1B, add a method here:
    //
    //   public function checkSubscription(int $tenantId, string $roleCode, string $httpMethod): string
    //
    // SuspensionFilter will call both check() and checkSubscription() and take the most restrictive result.
    // No changes to the filter file itself will be required.

    // ---------------------------------------------------------------

    protected function evaluateSuspended(string $roleCode, string $httpMethod): string
    {
        // Owners and tenant admins retain full access during suspension
        if (in_array($roleCode, ['tenant_owner', 'tenant_admin'], true)) {
            return self::ALLOW;
        }

        // Write operations are blocked for operational users
        $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        if (in_array(strtoupper($httpMethod), $writeMethods, true)) {
            return self::DENY_WRITE;
        }

        // Read access allowed with warning banner
        return self::WARN_READ;
    }
}
