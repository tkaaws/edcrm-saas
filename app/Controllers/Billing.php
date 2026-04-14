<?php

namespace App\Controllers;

use App\Models\PlanModel;

class Billing extends BaseController
{
    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');

        $subscriptionPolicy = service('subscriptionPolicy');
        $featureGate        = service('featureGate');
        $usageLimit         = service('usageLimit');

        $subscription    = $subscriptionPolicy->getActiveSubscription($tenantId);
        $effectiveStatus = $subscriptionPolicy->getStatus($tenantId);

        $plan      = null;
        $planModel = new PlanModel();

        if ($subscription) {
            $plan = $planModel->getPlanDetail((int) $subscription->plan_id);
        }

        // Enabled modules visible to tenant
        $enabledModules = $featureGate->getEnabledModules($tenantId);

        // Capacity summaries
        $usersSummary    = $usageLimit->getSummary($tenantId, 'max_users');
        $branchesSummary = $usageLimit->getSummary($tenantId, 'max_branches');

        // All catalog modules for display (so we can show what's locked too)
        $db             = db_connect();
        $allModules     = $db->table('feature_catalog')
                             ->where('category', 'module')
                             ->orderBy('code')
                             ->get()->getResult();

        return view('billing/index', $this->buildShellViewData([
            'title'           => 'Billing',
            'pageTitle'       => 'Your Subscription',
            'activeNav'       => 'billing',
            'subscription'    => $subscription,
            'effectiveStatus' => $effectiveStatus,
            'plan'            => $plan,
            'enabledModules'  => $enabledModules,
            'allModules'      => $allModules,
            'usersSummary'    => $usersSummary,
            'branchesSummary' => $branchesSummary,
        ]));
    }
}
