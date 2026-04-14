<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * BillingCatalogSeeder
 *
 * Seeds:
 * - feature_catalog  (modules + limit codes)
 * - plans            (starter, basic, growth, scale, enterprise)
 * - plan_prices      (monthly + yearly in INR for each plan)
 * - plan_features    (which modules each plan includes)
 * - plan_limits      (max_users, max_branches per plan)
 */
class BillingCatalogSeeder extends Seeder
{
    public function run()
    {
        // Guard: skip if catalog has already been seeded
        if ($this->db->table('feature_catalog')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        // ----------------------------------------------------------------
        // 1. Feature catalog
        // ----------------------------------------------------------------

        $features = [
            // Modules
            ['code' => 'crm_core',         'name' => 'CRM Core',            'description' => 'Enquiry capture, followup, branch and user management', 'category' => 'module', 'is_metered' => 0],
            ['code' => 'admissions',        'name' => 'Admissions',          'description' => 'Admission conversion, fee management, installments',     'category' => 'module', 'is_metered' => 0],
            ['code' => 'service_tickets',   'name' => 'Service Tickets',     'description' => 'Student support ticket management',                      'category' => 'module', 'is_metered' => 0],
            ['code' => 'placement',         'name' => 'Placement',           'description' => 'Placement drives, company connects, hiring workflow',     'category' => 'module', 'is_metered' => 0],
            ['code' => 'batch_management',  'name' => 'Batch Management',    'description' => 'Batch scheduling, attendance, sessions',                 'category' => 'module', 'is_metered' => 0],
            ['code' => 'whatsapp',          'name' => 'WhatsApp Integration','description' => 'WhatsApp messaging and notification integration',        'category' => 'module', 'is_metered' => 0],
            ['code' => 'advanced_reports',  'name' => 'Advanced Reports',    'description' => 'Revenue, placement, ranking, and analytics reports',     'category' => 'module', 'is_metered' => 0],
            ['code' => 'student_portal',    'name' => 'Student Portal',      'description' => 'Self-service student login and profile portal',          'category' => 'module', 'is_metered' => 0],
            // Capacity limits
            ['code' => 'max_users',         'name' => 'Max Active Users',    'description' => 'Maximum number of active staff/admin users',             'category' => 'limit',  'is_metered' => 0],
            ['code' => 'max_branches',      'name' => 'Max Branches',        'description' => 'Maximum number of branches per tenant',                  'category' => 'limit',  'is_metered' => 0],
        ];

        foreach ($features as $f) {
            $this->db->table('feature_catalog')->insert(array_merge($f, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }


        // ----------------------------------------------------------------
        // 2. Plans
        // ----------------------------------------------------------------
        // price_amount stored in paise (INR × 100)
        // Modules included per plan:
        //   starter  : crm_core only
        //   basic    : crm_core, admissions
        //   growth   : crm_core, admissions, service_tickets, batch_management
        //   scale    : crm_core, admissions, service_tickets, batch_management, placement, whatsapp, advanced_reports
        //   enterprise: all modules
        // ----------------------------------------------------------------

        $plans = [
            [
                'code'        => 'starter',
                'name'        => 'Starter',
                'description' => 'For small institutes just getting started',
                'status'      => 'active',
                'is_public'   => 1,
                'sort_order'  => 1,
                'monthly_price' => 99900,   // ₹999/month
                'yearly_price'  => 999900,  // ₹9999/year
                'max_users'   => 3,
                'max_branches'=> 1,
                'modules'     => ['crm_core'],
            ],
            [
                'code'        => 'basic',
                'name'        => 'Basic',
                'description' => 'For growing institutes with admissions workflow',
                'status'      => 'active',
                'is_public'   => 1,
                'sort_order'  => 2,
                'monthly_price' => 199900,  // ₹1999/month
                'yearly_price'  => 1999900, // ₹19999/year
                'max_users'   => 10,
                'max_branches'=> 3,
                'modules'     => ['crm_core', 'admissions'],
            ],
            [
                'code'        => 'growth',
                'name'        => 'Growth',
                'description' => 'Multi-branch institutes with full operations',
                'status'      => 'active',
                'is_public'   => 1,
                'sort_order'  => 3,
                'monthly_price' => 399900,  // ₹3999/month
                'yearly_price'  => 3999900, // ₹39999/year
                'max_users'   => 25,
                'max_branches'=> 10,
                'modules'     => ['crm_core', 'admissions', 'service_tickets', 'batch_management'],
            ],
            [
                'code'        => 'scale',
                'name'        => 'Scale',
                'description' => 'Large institutes with placement and advanced reporting',
                'status'      => 'active',
                'is_public'   => 1,
                'sort_order'  => 4,
                'monthly_price' => 799900,  // ₹7999/month
                'yearly_price'  => 7999900, // ₹79999/year
                'max_users'   => 50,
                'max_branches'=> -1, // unlimited
                'modules'     => ['crm_core', 'admissions', 'service_tickets', 'batch_management', 'placement', 'whatsapp', 'advanced_reports'],
            ],
            [
                'code'        => 'enterprise',
                'name'        => 'Enterprise',
                'description' => 'Custom contracts — all modules, unlimited users and branches',
                'status'      => 'active',
                'is_public'   => 0, // not shown on public pricing — assigned manually
                'sort_order'  => 5,
                'monthly_price' => 0,  // custom pricing — set per contract
                'yearly_price'  => 0,
                'max_users'   => -1, // unlimited
                'max_branches'=> -1, // unlimited
                'modules'     => ['crm_core', 'admissions', 'service_tickets', 'batch_management', 'placement', 'whatsapp', 'advanced_reports', 'student_portal'],
            ],
        ];

        $allModuleCodes = array_column($features, 'code');
        $allModuleCodes = array_filter($allModuleCodes, function($code) use ($features) {
            foreach ($features as $f) {
                if ($f['code'] === $code && $f['category'] === 'module') return true;
            }
            return false;
        });

        foreach ($plans as $plan) {
            $this->db->table('plans')->insert([
                'code'        => $plan['code'],
                'name'        => $plan['name'],
                'description' => $plan['description'],
                'status'      => $plan['status'],
                'is_public'   => $plan['is_public'],
                'sort_order'  => $plan['sort_order'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $planId = $this->db->insertID();

            // Plan prices
            $this->db->table('plan_prices')->insert([
                'plan_id'               => $planId,
                'billing_cycle'         => 'monthly',
                'currency_code'         => 'INR',
                'price_amount'          => $plan['monthly_price'],
                'billing_period_months' => 1,
                'status'                => 'active',
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            $this->db->table('plan_prices')->insert([
                'plan_id'               => $planId,
                'billing_cycle'         => 'yearly',
                'currency_code'         => 'INR',
                'price_amount'          => $plan['yearly_price'],
                'billing_period_months' => 12,
                'status'                => 'active',
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            // Plan features — all module codes, enabled only if in plan's modules list
            foreach ($allModuleCodes as $code) {
                $this->db->table('plan_features')->insert([
                    'plan_id'      => $planId,
                    'feature_code' => $code,
                    'is_enabled'   => in_array($code, $plan['modules']) ? 1 : 0,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }

            // Plan limits
            $this->db->table('plan_limits')->insert([
                'plan_id'     => $planId,
                'limit_code'  => 'max_users',
                'limit_value' => $plan['max_users'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $this->db->table('plan_limits')->insert([
                'plan_id'     => $planId,
                'limit_code'  => 'max_branches',
                'limit_value' => $plan['max_branches'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

    }
}
