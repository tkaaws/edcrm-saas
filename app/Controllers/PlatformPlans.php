<?php

namespace App\Controllers;

use App\Models\PlanModel;

class PlatformPlans extends BaseController
{
    protected PlanModel $planModel;

    public function __construct()
    {
        $this->planModel = new PlanModel();
    }

    // ------------------------------------------------------------------
    // INDEX
    // ------------------------------------------------------------------

    public function index(): string
    {
        $plans = $this->planModel->orderBy('sort_order', 'ASC')->findAll();

        return view('platform/plans/index', $this->buildShellViewData([
            'title'     => 'Plans',
            'pageTitle' => 'Billing Plans',
            'activeNav' => 'plans',
            'plans'     => $plans,
        ]));
    }

    // ------------------------------------------------------------------
    // SHOW — plan detail with pricing, features, limits
    // ------------------------------------------------------------------

    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $plan = $this->planModel->getPlanDetail($id);

        if (! $plan) {
            return redirect()->to('/platform/plans')->with('error', 'Plan not found.');
        }

        $db           = db_connect();
        $allFeatures  = $db->table('feature_catalog')->where('category', 'module')->orderBy('code')->get()->getResult();
        $allLimits    = $db->table('feature_catalog')->where('category', 'limit')->orderBy('code')->get()->getResult();

        // Index plan features and limits by code for easy lookup in the view
        $featureMap = [];
        foreach ($plan->features as $f) {
            $featureMap[$f->feature_code] = (bool) $f->is_enabled;
        }

        $limitMap = [];
        foreach ($plan->limits as $l) {
            $limitMap[$l->limit_code] = (int) $l->limit_value;
        }

        return view('platform/plans/show', $this->buildShellViewData([
            'title'       => esc($plan->name) . ' — Plan',
            'pageTitle'   => esc($plan->name),
            'activeNav'   => 'plans',
            'plan'        => $plan,
            'allFeatures' => $allFeatures,
            'allLimits'   => $allLimits,
            'featureMap'  => $featureMap,
            'limitMap'    => $limitMap,
        ]));
    }

    // ------------------------------------------------------------------
    // UPDATE FEATURE — toggle module on/off for a plan
    // ------------------------------------------------------------------

    public function updateFeature(int $planId): \CodeIgniter\HTTP\RedirectResponse
    {
        $plan = $this->planModel->find($planId);
        if (! $plan) {
            return redirect()->to('/platform/plans')->with('error', 'Plan not found.');
        }

        $featureCode = trim((string) $this->request->getPost('feature_code'));
        $isEnabled   = (int) (bool) $this->request->getPost('is_enabled');

        if ($featureCode === '') {
            return redirect()->back()->with('error', 'Invalid feature code.');
        }

        $db  = db_connect();
        $row = $db->table('plan_features')
                  ->where('plan_id', $planId)
                  ->where('feature_code', $featureCode)
                  ->get()->getRow();

        if ($row) {
            $db->table('plan_features')
               ->where('plan_id', $planId)
               ->where('feature_code', $featureCode)
               ->update(['is_enabled' => $isEnabled, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            $db->table('plan_features')->insert([
                'plan_id'      => $planId,
                'feature_code' => $featureCode,
                'is_enabled'   => $isEnabled,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to("/platform/plans/{$planId}")
                         ->with('message', 'Feature updated.');
    }

    // ------------------------------------------------------------------
    // UPDATE LIMIT — set max_users or max_branches for a plan
    // ------------------------------------------------------------------

    public function updateLimit(int $planId): \CodeIgniter\HTTP\RedirectResponse
    {
        $plan = $this->planModel->find($planId);
        if (! $plan) {
            return redirect()->to('/platform/plans')->with('error', 'Plan not found.');
        }

        $limitCode  = trim((string) $this->request->getPost('limit_code'));
        $limitValue = (int) $this->request->getPost('limit_value');

        if ($limitCode === '') {
            return redirect()->back()->with('error', 'Invalid limit code.');
        }

        $db  = db_connect();
        $row = $db->table('plan_limits')
                  ->where('plan_id', $planId)
                  ->where('limit_code', $limitCode)
                  ->get()->getRow();

        if ($row) {
            $db->table('plan_limits')
               ->where('plan_id', $planId)
               ->where('limit_code', $limitCode)
               ->update(['limit_value' => $limitValue, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            $db->table('plan_limits')->insert([
                'plan_id'     => $planId,
                'limit_code'  => $limitCode,
                'limit_value' => $limitValue,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to("/platform/plans/{$planId}")
                         ->with('message', 'Limit updated.');
    }

    // ------------------------------------------------------------------
    // UPDATE PRICE — set monthly or yearly price for a plan
    // ------------------------------------------------------------------

    public function updatePrice(int $planId): \CodeIgniter\HTTP\RedirectResponse
    {
        $plan = $this->planModel->find($planId);
        if (! $plan) {
            return redirect()->to('/platform/plans')->with('error', 'Plan not found.');
        }

        $billingCycle = $this->request->getPost('billing_cycle');
        $priceRupees  = (float) $this->request->getPost('price_amount');
        $pricePaise   = (int) round($priceRupees * 100);

        if (! in_array($billingCycle, ['monthly', 'yearly'])) {
            return redirect()->back()->with('error', 'Invalid billing cycle.');
        }

        $db  = db_connect();
        $row = $db->table('plan_prices')
                  ->where('plan_id', $planId)
                  ->where('billing_cycle', $billingCycle)
                  ->where('currency_code', 'INR')
                  ->get()->getRow();

        $periodMonths = $billingCycle === 'yearly' ? 12 : 1;

        if ($row) {
            $db->table('plan_prices')
               ->where('id', $row->id)
               ->update([
                   'price_amount'          => $pricePaise,
                   'billing_period_months' => $periodMonths,
                   'updated_at'            => date('Y-m-d H:i:s'),
               ]);
        } else {
            $db->table('plan_prices')->insert([
                'plan_id'               => $planId,
                'billing_cycle'         => $billingCycle,
                'currency_code'         => 'INR',
                'price_amount'          => $pricePaise,
                'billing_period_months' => $periodMonths,
                'status'                => 'active',
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to("/platform/plans/{$planId}")
                         ->with('message', 'Price updated.');
    }
}
