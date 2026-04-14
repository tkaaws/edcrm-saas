<?php

namespace App\Services;

use App\Models\PlanLimitModel;
use App\Models\SubscriptionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * UsageLimitService
 *
 * Answers: what is the effective limit for a tenant, and are they over it?
 *
 * Limit resolution order (highest priority first):
 *   1. subscription_feature_overrides with limit_value set (platform admin override)
 *   2. subscription_add_ons that increase the limit (additive)
 *   3. plan_limits for the tenant's current plan
 *   4. 0 (no plan = no access)
 *
 * -1 = unlimited (never blocked).
 *
 * Supported limit codes (v1):
 *   max_users    — active users for the tenant
 *   max_branches — branches for the tenant
 */
class UsageLimitService
{
    protected PlanLimitModel $planLimitModel;
    protected SubscriptionModel $subscriptionModel;
    protected BaseConnection $db;

    // Per-request cache: [tenantId => [limitCode => effectiveLimit]]
    protected array $limitCache = [];

    public function __construct()
    {
        $this->planLimitModel    = new PlanLimitModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->db                = db_connect();
    }

    // ---------------------------------------------------------------
    // LIMIT RESOLUTION
    // ---------------------------------------------------------------

    /**
     * Get the effective limit for a tenant and limit code.
     * Returns -1 for unlimited, 0 if no plan/subscription found.
     */
    public function getLimit(int $tenantId, string $limitCode): int
    {
        if (isset($this->limitCache[$tenantId][$limitCode])) {
            return $this->limitCache[$tenantId][$limitCode];
        }

        $subscription = $this->subscriptionModel->getActiveForTenant($tenantId);

        if (! $subscription) {
            return 0;
        }

        // Step 1: plan base limit
        $planLimits = $this->planLimitModel->getForPlan((int) $subscription->plan_id);
        $base       = $planLimits[$limitCode] ?? 0;

        // Step 2: check for a hard override from subscription_feature_overrides
        $override = $this->db->table('subscription_feature_overrides')
                             ->where('subscription_id', $subscription->id)
                             ->where('feature_code', $limitCode)
                             ->where('limit_value IS NOT NULL', null, false)
                             ->get()
                             ->getRow();

        if ($override) {
            $effective = (int) $override->limit_value;
            $this->limitCache[$tenantId][$limitCode] = $effective;
            return $effective;
        }

        // Step 3: add-on increases (additive, ignore if base is already -1/unlimited)
        if ($base !== -1) {
            $addOns = $this->db->table('subscription_add_ons')
                               ->where('subscription_id', $subscription->id)
                               ->where('code', $limitCode)
                               ->where('status', 'active')
                               ->get()
                               ->getResult();

            foreach ($addOns as $addOn) {
                $base += (int) $addOn->quantity;
            }
        }

        $this->limitCache[$tenantId][$limitCode] = $base;

        return $base;
    }

    // ---------------------------------------------------------------
    // USAGE COUNTING
    // ---------------------------------------------------------------

    /**
     * Get the current usage value for a tenant and limit code.
     */
    public function getCurrentUsage(int $tenantId, string $limitCode): int
    {
        return match ($limitCode) {
            'max_users'    => $this->countActiveUsers($tenantId),
            'max_branches' => $this->countBranches($tenantId),
            default        => 0,
        };
    }

    /**
     * Check if a tenant is at or over their limit for a limit code.
     * Returns false (not over) if the limit is -1 (unlimited) or 0 (unconfigured).
     */
    public function isOverLimit(int $tenantId, string $limitCode): bool
    {
        $limit = $this->getLimit($tenantId, $limitCode);

        if ($limit <= 0) {
            // -1 = unlimited, 0 = not configured — not blocked
            return false;
        }

        return $this->getCurrentUsage($tenantId, $limitCode) >= $limit;
    }

    /**
     * Check if adding one more unit would exceed the limit.
     * Use this before creating a new user or branch.
     */
    public function wouldExceedLimit(int $tenantId, string $limitCode): bool
    {
        $limit = $this->getLimit($tenantId, $limitCode);

        if ($limit <= 0) {
            return false; // unlimited
        }

        return $this->getCurrentUsage($tenantId, $limitCode) >= $limit;
    }

    /**
     * Get a usage summary for display in billing UI.
     *
     * @return array{limit: int, current: int, available: int|string, over_limit: bool}
     */
    public function getSummary(int $tenantId, string $limitCode): array
    {
        $limit   = $this->getLimit($tenantId, $limitCode);
        $current = $this->getCurrentUsage($tenantId, $limitCode);

        return [
            'limit'      => $limit,
            'current'    => $current,
            'available'  => $limit === -1 ? 'unlimited' : max(0, $limit - $current),
            'over_limit' => $limit > 0 && $current >= $limit,
        ];
    }

    // ---------------------------------------------------------------
    // USAGE COUNTERS
    // ---------------------------------------------------------------

    protected function countActiveUsers(int $tenantId): int
    {
        return (int) $this->db->table('users')
                              ->where('tenant_id', $tenantId)
                              ->where('is_active', 1)
                              ->countAllResults();
    }

    protected function countBranches(int $tenantId): int
    {
        return (int) $this->db->table('tenant_branches')
                              ->where('tenant_id', $tenantId)
                              ->countAllResults();
    }

    /**
     * Flush the per-request limit cache.
     */
    public function flushCache(?int $tenantId = null): void
    {
        if ($tenantId !== null) {
            unset($this->limitCache[$tenantId]);
        } else {
            $this->limitCache = [];
        }
    }
}
