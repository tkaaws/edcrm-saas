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
 * Phase 1B: also checks subscription state (trial / grace / suspended / expired).
 *
 * Resolution: tenant status check runs first; subscription check runs on top
 * if the tenant is 'active'. The most restrictive result wins.
 *
 * Keeping this logic in a service (not inline in the filter) means:
 * - SuspensionFilter stays thin and never changes as billing rules grow
 * - TenantAccessPolicy can be tested independently
 */
class TenantAccessPolicy
{
    // Result constants returned by check()
    const ALLOW        = 'allow';
    const DENY_BLOCKED = 'deny_blocked';   // no access — redirect to login/billing
    const DENY_WRITE   = 'deny_write';     // write blocked, reads allowed
    const WARN_READ    = 'warn_read';      // access allowed with warning banner

    // Context hint passed to SuspensionFilter for the warning banner message
    const CONTEXT_SUSPENDED = 'suspended';
    const CONTEXT_GRACE     = 'grace';

    protected TenantModel $tenantModel;

    /** Lazily resolved; can be replaced in tests via replaceSubscriptionPolicy(). */
    protected ?SubscriptionPolicyService $subscriptionPolicyInstance = null;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
    }

    /**
     * Lazy-load the subscription policy service.
     * Stored as a property so tests can inject a mock without touching the
     * global service container.
     */
    protected function getSubscriptionPolicy(): SubscriptionPolicyService
    {
        if ($this->subscriptionPolicyInstance === null) {
            $this->subscriptionPolicyInstance = service('subscriptionPolicy');
        }

        return $this->subscriptionPolicyInstance;
    }

    /**
     * Evaluate access for the current request context.
     *
     * Checks tenant account status first, then subscription state.
     * Returns the most restrictive result.
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
            return self::DENY_BLOCKED;
        }

        // --- Step 1: tenant account status ---
        $tenantResult = match ($tenant->status) {
            'active'             => self::ALLOW,
            'suspended'          => $this->evaluateSuspended($roleCode, $httpMethod),
            'draft', 'cancelled' => self::DENY_BLOCKED,
            default              => self::ALLOW,
        };

        // If the tenant account itself is already blocked/denied — no need to check subscription
        if ($tenantResult === self::DENY_BLOCKED) {
            return self::DENY_BLOCKED;
        }

        // --- Step 2: subscription state ---
        $subscriptionResult = $this->checkSubscription($tenantId, $roleCode, $httpMethod);

        // Return the more restrictive of the two checks
        return $this->mostRestrictive($tenantResult, $subscriptionResult);
    }

    /**
     * Evaluate subscription state for the tenant.
     * Called from check() after tenant status passes.
     */
    public function checkSubscription(int $tenantId, string $roleCode, string $httpMethod): string
    {
        $subscriptionPolicy = $this->getSubscriptionPolicy();
        $status             = $subscriptionPolicy->getStatus($tenantId);

        return match ($status) {
            'trial',
            'active'    => self::ALLOW,

            'grace'     => self::WARN_READ,    // access allowed + warning banner shown

            'suspended' => $this->evaluateSuspended($roleCode, $httpMethod),

            'cancelled' => self::ALLOW,         // cancelled but within paid term — still active

            'expired'   => self::DENY_BLOCKED,

            'none'      => self::ALLOW,          // no subscription record — treat as unmanaged/allow
                                                 // platform admin and legacy tenants have no subscription

            default     => self::ALLOW,
        };
    }

    /**
     * Get the human-readable message for a blocked tenant.
     */
    public function blockedMessage(int $tenantId): string
    {
        $tenant = $this->tenantModel->find($tenantId);

        // Check subscription state first for more specific messaging
        $subscriptionPolicy = $this->getSubscriptionPolicy();
        $subStatus          = $subscriptionPolicy->getStatus($tenantId);

        if ($subStatus === 'expired') {
            return 'Your subscription has expired. Please renew to continue using the platform.';
        }

        return match ($tenant?->status) {
            'draft'     => 'This account has not been activated yet.',
            'cancelled' => 'This account has been cancelled. Please contact support.',
            default     => 'Access to this account is currently restricted.',
        };
    }

    /**
     * Get the subscription warning context for banner rendering.
     * Called by SuspensionFilter when result is WARN_READ.
     */
    public function getWarningContext(int $tenantId): string
    {
        $subscriptionPolicy = $this->getSubscriptionPolicy();
        $status             = $subscriptionPolicy->getStatus($tenantId);

        if ($status === 'grace') {
            return self::CONTEXT_GRACE;
        }

        return self::CONTEXT_SUSPENDED;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function evaluateSuspended(string $roleCode, string $httpMethod): string
    {
        // Owners and tenant admins retain full access during suspension/grace
        if (in_array($roleCode, ['tenant_owner', 'tenant_admin'], true)) {
            return self::ALLOW;
        }

        // Write operations blocked for operational users
        if (in_array(strtoupper($httpMethod), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return self::DENY_WRITE;
        }

        return self::WARN_READ;
    }

    /**
     * Return the more restrictive of two policy results.
     * Severity order: DENY_BLOCKED > DENY_WRITE > WARN_READ > ALLOW
     */
    protected function mostRestrictive(string $a, string $b): string
    {
        $severity = [
            self::DENY_BLOCKED => 3,
            self::DENY_WRITE   => 2,
            self::WARN_READ    => 1,
            self::ALLOW        => 0,
        ];

        return ($severity[$a] ?? 0) >= ($severity[$b] ?? 0) ? $a : $b;
    }
}
