<?php

namespace App\Services;

use App\Models\PlanFeatureModel;
use App\Models\SubscriptionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * FeatureGateService
 *
 * Answers: is a specific module feature enabled for this tenant?
 *
 * Resolution order (highest priority first):
 *   1. subscription_feature_overrides (platform admin can force-on or force-off)
 *   2. plan_features for the tenant's current plan
 *   3. false (no plan / no subscription = no access)
 *
 * crm_core is always implicitly enabled for any active subscription.
 */
class FeatureGateService
{
    const ALWAYS_ON = 'crm_core'; // crm_core is on for any valid subscription

    protected PlanFeatureModel $planFeatureModel;
    protected SubscriptionModel $subscriptionModel;
    protected BaseConnection $db;

    // Per-request cache: [tenantId => [featureCode => bool]]
    protected array $cache = [];

    public function __construct()
    {
        $this->planFeatureModel  = new PlanFeatureModel();
        $this->subscriptionModel = new SubscriptionModel();
        $this->db                = db_connect();
    }

    /**
     * Check if a module feature is enabled for a tenant.
     *
     * @param int    $tenantId
     * @param string $featureCode  e.g. 'admissions', 'placement'
     */
    public function isEnabled(int $tenantId, string $featureCode): bool
    {
        // crm_core is always on for any existing subscription
        if ($featureCode === self::ALWAYS_ON) {
            return $this->hasAnySubscription($tenantId);
        }

        $features = $this->resolveFeatures($tenantId);

        return $features[$featureCode] ?? false;
    }

    /**
     * Get all enabled module codes for a tenant.
     *
     * @return string[]
     */
    public function getEnabledModules(int $tenantId): array
    {
        $features = $this->resolveFeatures($tenantId);

        return array_keys(array_filter($features, fn($v) => $v === true));
    }

    /**
     * Check if a tenant has any subscription (for crm_core guard).
     */
    public function hasAnySubscription(int $tenantId): bool
    {
        return $this->subscriptionModel->getActiveForTenant($tenantId) !== null;
    }

    // ---------------------------------------------------------------
    // INTERNAL
    // ---------------------------------------------------------------

    /**
     * Resolve the full feature map for a tenant.
     * Returns [featureCode => bool], with overrides applied on top of plan defaults.
     * Results are cached for the duration of the request.
     */
    protected function resolveFeatures(int $tenantId): array
    {
        if (isset($this->cache[$tenantId])) {
            return $this->cache[$tenantId];
        }

        $subscription = $this->subscriptionModel->getActiveForTenant($tenantId);

        if (! $subscription) {
            $this->cache[$tenantId] = [];
            return [];
        }

        // Step 1: plan defaults
        $planFeatures = $this->planFeatureModel->getForPlan((int) $subscription->plan_id);

        // Step 2: apply subscription-level overrides (platform admin can customise per tenant)
        $overrides = $this->getOverrides((int) $subscription->id);

        foreach ($overrides as $code => $override) {
            if ($override->is_enabled !== null) {
                $planFeatures[$code] = (bool) $override->is_enabled;
            }
        }

        $this->cache[$tenantId] = $planFeatures;

        return $planFeatures;
    }

    /**
     * Fetch subscription_feature_overrides for a subscription.
     * Returns [featureCode => override object]
     */
    protected function getOverrides(int $subscriptionId): array
    {
        $rows = $this->db->table('subscription_feature_overrides')
                         ->where('subscription_id', $subscriptionId)
                         ->whereNotNull('is_enabled')
                         ->get()
                         ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->feature_code] = $row;
        }
        return $result;
    }

    /**
     * Flush the per-request cache (useful after plan/override changes in same request).
     */
    public function flushCache(?int $tenantId = null): void
    {
        if ($tenantId !== null) {
            unset($this->cache[$tenantId]);
        } else {
            $this->cache = [];
        }
    }
}
