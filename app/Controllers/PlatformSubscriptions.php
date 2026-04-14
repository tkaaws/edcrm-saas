<?php

namespace App\Controllers;

use App\Models\PlanModel;
use App\Models\TenantModel;
use App\Models\SubscriptionModel;

class PlatformSubscriptions extends BaseController
{
    protected SubscriptionModel $subscriptionModel;
    protected TenantModel $tenantModel;
    protected PlanModel $planModel;

    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
        $this->tenantModel       = new TenantModel();
        $this->planModel         = new PlanModel();
    }

    // ------------------------------------------------------------------
    // INDEX — all subscriptions across all tenants
    // ------------------------------------------------------------------

    public function index(): string
    {
        $db = db_connect();

        $rows = $db->query("
            SELECT s.*, t.name AS tenant_name, t.slug AS tenant_slug,
                   p.name AS plan_name, p.code AS plan_code
            FROM subscriptions s
            JOIN tenants t ON t.id = s.tenant_id
            JOIN plans p   ON p.id = s.plan_id
            ORDER BY s.id DESC
        ")->getResult();

        $plans   = $this->planModel->getAllActivePlans();
        $tenants = $this->tenantModel->where('status', 'active')
                                     ->orderBy('name', 'ASC')
                                     ->findAll();

        return view('platform/subscriptions/index', $this->buildShellViewData([
            'title'         => 'Subscriptions',
            'pageTitle'     => 'Tenant Subscriptions',
            'activeNav'     => 'subscriptions',
            'subscriptions' => $rows,
            'plans'         => $plans,
            'tenants'       => $tenants,
        ]));
    }

    // ------------------------------------------------------------------
    // SHOW — subscription detail + events log
    // ------------------------------------------------------------------

    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $db           = db_connect();
        $subscription = $this->subscriptionModel->find($id);

        if (! $subscription) {
            return redirect()->to('/platform/subscriptions')->with('error', 'Subscription not found.');
        }

        $tenant = $this->tenantModel->find($subscription->tenant_id);
        $plan   = $this->planModel->find($subscription->plan_id);

        $events = $db->table('billing_events')
                     ->where('subscription_id', $id)
                     ->orderBy('id', 'DESC')
                     ->get()->getResult();

        $overrides = $db->table('subscription_feature_overrides')
                        ->where('subscription_id', $id)
                        ->get()->getResult();

        $addOns = $db->table('subscription_add_ons')
                     ->where('subscription_id', $id)
                     ->get()->getResult();

        $plans        = $this->planModel->getAllActivePlans();
        $allFeatures  = $db->table('feature_catalog')->orderBy('category')->orderBy('code')->get()->getResult();

        // Effective status from the state machine (may differ from stored if time has passed)
        $effectiveStatus = service('subscriptionPolicy')->getStatus((int) $subscription->tenant_id);

        return view('platform/subscriptions/show', $this->buildShellViewData([
            'title'           => 'Subscription #' . $id,
            'pageTitle'       => 'Subscription — ' . esc($tenant?->name ?? 'Unknown'),
            'activeNav'       => 'subscriptions',
            'subscription'    => $subscription,
            'tenant'          => $tenant,
            'plan'            => $plan,
            'plans'           => $plans,
            'events'          => $events,
            'overrides'       => $overrides,
            'addOns'          => $addOns,
            'allFeatures'     => $allFeatures,
            'effectiveStatus' => $effectiveStatus,
        ]));
    }

    // ------------------------------------------------------------------
    // ATTACH — create a new subscription for a tenant
    // ------------------------------------------------------------------

    public function attach(): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId     = (int) $this->request->getPost('tenant_id');
        $planId       = (int) $this->request->getPost('plan_id');
        $billingCycle = $this->request->getPost('billing_cycle');
        $trialDays    = (int) ($this->request->getPost('trial_days') ?: 14);

        if (! $tenantId || ! $planId) {
            return redirect()->back()->with('error', 'Tenant and plan are required.');
        }

        if (! in_array($billingCycle, ['monthly', 'yearly'])) {
            $billingCycle = 'monthly';
        }

        $subscriptionPolicy = service('subscriptionPolicy');

        // If tenant already has an active subscription, cancel it first
        $existing = $this->subscriptionModel->getActiveForTenant($tenantId);
        if ($existing) {
            $subscriptionPolicy->transitionTo(
                (int) $existing->id,
                'cancelled',
                (int) session()->get('user_id'),
                'Superseded by new subscription from platform admin'
            );
        }

        // Create new trial subscription
        $subscription = $subscriptionPolicy->createTrialSubscription($tenantId, $planId, $trialDays);

        // Update billing cycle on the new subscription
        db_connect()->table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update(['billing_cycle' => $billingCycle, 'updated_at' => date('Y-m-d H:i:s')]);

        return redirect()->to("/platform/subscriptions/{$subscription->id}")
                         ->with('message', "Subscription created — {$trialDays}-day trial started.");
    }

    // ------------------------------------------------------------------
    // UPDATE STATUS — manual status transition
    // ------------------------------------------------------------------

    public function updateStatus(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $subscription = $this->subscriptionModel->find($id);
        if (! $subscription) {
            return redirect()->to('/platform/subscriptions')->with('error', 'Subscription not found.');
        }

        $newStatus = $this->request->getPost('status');
        $validStatuses = ['trial', 'active', 'grace', 'suspended', 'cancelled', 'expired'];

        if (! in_array($newStatus, $validStatuses)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }

        $note = trim((string) $this->request->getPost('note'));

        service('subscriptionPolicy')->transitionTo(
            $id,
            $newStatus,
            (int) session()->get('user_id'),
            $note ?: "Manual status change to {$newStatus} by platform admin"
        );

        return redirect()->to("/platform/subscriptions/{$id}")
                         ->with('message', "Status changed to {$newStatus}.");
    }

    // ------------------------------------------------------------------
    // SET OVERRIDE — per-subscription feature/limit override
    // ------------------------------------------------------------------

    public function setOverride(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $subscription = $this->subscriptionModel->find($id);
        if (! $subscription) {
            return redirect()->to('/platform/subscriptions')->with('error', 'Subscription not found.');
        }

        $featureCode = trim((string) $this->request->getPost('feature_code'));
        $isEnabled   = $this->request->getPost('is_enabled');
        $limitValue  = $this->request->getPost('limit_value');

        if ($featureCode === '') {
            return redirect()->back()->with('error', 'Feature code is required.');
        }

        $db  = db_connect();
        $row = $db->table('subscription_feature_overrides')
                  ->where('subscription_id', $id)
                  ->where('feature_code', $featureCode)
                  ->get()->getRow();

        $data = [
            'is_enabled'  => $isEnabled !== null ? (int) (bool) $isEnabled : null,
            'limit_value' => $limitValue !== null && $limitValue !== '' ? (int) $limitValue : null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if ($row) {
            $db->table('subscription_feature_overrides')
               ->where('id', $row->id)
               ->update($data);
        } else {
            $db->table('subscription_feature_overrides')->insert(array_merge($data, [
                'subscription_id' => $id,
                'feature_code'    => $featureCode,
                'created_at'      => date('Y-m-d H:i:s'),
            ]));
        }

        // Flush feature gate cache so changes take effect immediately
        service('featureGate')->flushCache((int) $subscription->tenant_id);

        return redirect()->to("/platform/subscriptions/{$id}")
                         ->with('message', "Override set for {$featureCode}.");
    }
}
