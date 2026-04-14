<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * DemoDataSeeder
 *
 * Creates one complete demo tenant with:
 * - 1 tenant (Demo Institute)
 * - 1 branch (HQ)
 * - all system roles under that tenant
 * - role-privilege mappings for each system role
 * - 1 tenant_owner user (demo login)
 * - tenant_settings row
 * - user assigned to HQ branch as primary
 *
 * Demo login: demo@edcrm.in / Demo@1234
 */
class DemoDataSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // -------------------------------------------------------
        // Guard: skip if demo tenant already exists
        // -------------------------------------------------------
        $existing = $this->db->table('tenants')->where('slug', 'demo-institute')->countAllResults();
        if ($existing > 0) {
            echo "DemoDataSeeder: demo tenant already exists, skipping.\n";
            return;
        }

        // -------------------------------------------------------
        // 1. Tenant
        // -------------------------------------------------------
        $this->db->table('tenants')->insert([
            'name'                  => 'Demo Institute',
            'slug'                  => 'demo-institute',
            'status'                => 'active',
            'legal_name'            => 'Demo Institute Pvt Ltd',
            'owner_name'            => 'Demo Owner',
            'owner_email'           => 'demo@edcrm.in',
            'owner_phone'           => '+910000000000',
            'default_timezone'      => 'Asia/Kolkata',
            'default_currency_code' => 'INR',
            'country_code'          => 'IN',
            'locale_code'           => 'en',
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);
        $tenantId = $this->db->insertID();
        echo "DemoDataSeeder: created tenant id={$tenantId}\n";

        // -------------------------------------------------------
        // 2. Branch
        // -------------------------------------------------------
        $this->db->table('tenant_branches')->insert([
            'tenant_id'  => $tenantId,
            'name'       => 'HQ',
            'code'       => 'HQ',
            'type'       => 'main',
            'country_code' => 'IN',
            'state_code'   => 'MH',
            'city'         => 'Pune',
            'timezone'     => null,
            'currency_code' => null,
            'status'       => 'active',
            'created_by'   => null,
            'updated_by'   => null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        $branchId = $this->db->insertID();
        echo "DemoDataSeeder: created branch id={$branchId}\n";

        // -------------------------------------------------------
        // 3. System roles
        // -------------------------------------------------------
        $roleDefs = [
            ['code' => 'platform_admin', 'name' => 'Platform Admin', 'is_system' => 1],
            ['code' => 'tenant_owner',   'name' => 'Tenant Owner',   'is_system' => 1],
            ['code' => 'tenant_admin',   'name' => 'Tenant Admin',   'is_system' => 1],
            ['code' => 'branch_manager', 'name' => 'Branch Manager', 'is_system' => 1],
            ['code' => 'counsellor',     'name' => 'Counsellor',     'is_system' => 1],
            ['code' => 'accounts',       'name' => 'Accounts',       'is_system' => 1],
            ['code' => 'operations',     'name' => 'Operations',     'is_system' => 1],
            ['code' => 'placement',      'name' => 'Placement',      'is_system' => 1],
            ['code' => 'faculty',        'name' => 'Faculty',        'is_system' => 1],
            ['code' => 'support_agent',  'name' => 'Support Agent',  'is_system' => 1],
        ];

        $roleIds = [];
        foreach ($roleDefs as $role) {
            $this->db->table('user_roles')->insert([
                'tenant_id'  => $tenantId,
                'name'       => $role['name'],
                'code'       => $role['code'],
                'is_system'  => $role['is_system'],
                'status'     => 'active',
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $roleIds[$role['code']] = $this->db->insertID();
        }
        echo "DemoDataSeeder: created " . count($roleIds) . " system roles.\n";

        // -------------------------------------------------------
        // 4. Role-privilege mappings
        // -------------------------------------------------------
        $allPrivileges = $this->db->table('privileges')->get()->getResultArray();
        $privMap = array_column($allPrivileges, 'id', 'code');

        if (empty($privMap)) {
            echo "DemoDataSeeder: no privileges found — run PrivilegesSeeder first.\n";
            return;
        }

        $roleMappings = [

            // platform_admin: no tenant privileges (platform-only access)
            'platform_admin' => [],

            // tenant_owner: all privileges
            'tenant_owner' => array_keys($privMap),

            // tenant_admin: all except billing.manage
            'tenant_admin' => array_values(array_filter(
                array_keys($privMap),
                fn($c) => $c !== 'billing.manage'
            )),

            // branch_manager: branch ops, enquiries, admissions, fees, reports
            'branch_manager' => [
                'users.view',
                'branches.view',
                'roles.view',
                'settings.view',
                'enquiries.view', 'enquiries.create', 'enquiries.edit',
                'enquiries.assign', 'enquiries.bulk_assign', 'enquiries.export',
                'followups.view', 'followups.create', 'followups.edit',
                'admissions.view', 'admissions.create', 'admissions.edit', 'admissions.approve',
                'fees.view', 'fees.create', 'fees.edit', 'fees.receipts',
                'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.close',
                'students.view', 'students.edit', 'students.attendance', 'students.export',
                'batches.view',
                'reports.view', 'reports.advanced', 'reports.export',
                'whatsapp.view', 'whatsapp.send',
            ],

            // counsellor: enquiries, followups, admissions view
            'counsellor' => [
                'enquiries.view', 'enquiries.create', 'enquiries.edit',
                'enquiries.assign',
                'followups.view', 'followups.create', 'followups.edit',
                'admissions.view', 'admissions.create',
                'students.view',
                'reports.view',
                'whatsapp.send',
            ],

            // accounts: fees, payments, receipts
            'accounts' => [
                'admissions.view',
                'fees.view', 'fees.create', 'fees.edit', 'fees.receipts',
                'fees.discount', 'fees.structure',
                'students.view',
                'reports.view', 'reports.export',
            ],

            // operations: students, batches, attendance
            'operations' => [
                'admissions.view',
                'students.view', 'students.edit', 'students.attendance', 'students.export',
                'batches.view', 'batches.create', 'batches.edit',
                'tickets.view', 'tickets.create', 'tickets.edit',
                'reports.view',
            ],

            // placement: placement module
            'placement' => [
                'students.view',
                'placement.view', 'placement.manage',
                'placement.jobs', 'placement.interviews',
                'placement.mock', 'placement.college',
                'reports.view',
                'whatsapp.send',
            ],

            // faculty: batches view, attendance
            'faculty' => [
                'batches.view',
                'students.view', 'students.attendance',
            ],

            // support_agent: tickets only
            'support_agent' => [
                'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.close',
                'students.view',
            ],
        ];

        $rpRows = [];
        foreach ($roleMappings as $roleCode => $privCodes) {
            $roleId = $roleIds[$roleCode] ?? null;
            if (! $roleId) continue;
            foreach ($privCodes as $privCode) {
                $privId = $privMap[$privCode] ?? null;
                if (! $privId) continue;
                $rpRows[] = [
                    'role_id'      => $roleId,
                    'privilege_id' => $privId,
                    'created_at'   => $now,
                ];
            }
        }
        $this->db->table('role_privileges')->insertBatch($rpRows);
        echo "DemoDataSeeder: inserted " . count($rpRows) . " role-privilege mappings.\n";

        // -------------------------------------------------------
        // 5. Platform admin user (demo login)
        // -------------------------------------------------------
        $platformAdminRoleId = $roleIds['platform_admin'];
        $this->db->table('users')->insert([
            'tenant_id'           => $tenantId,
            'role_id'             => $platformAdminRoleId,
            'employee_code'       => 'EMP-001',
            'username'            => 'demo_admin',
            'email'               => 'demo@edcrm.in',
            'first_name'          => 'Platform',
            'last_name'           => 'Admin',
            'mobile_number'       => '+910000000000',
            'whatsapp_number'     => null,
            'department'          => 'Platform',
            'designation'         => 'Platform Admin',
            'password_hash'       => password_hash('Demo@1234', PASSWORD_BCRYPT),
            'is_active'           => 1,
            'must_reset_password' => 0,
            'last_login_at'       => null,
            'last_login_ip'       => null,
            'created_by'          => null,
            'updated_by'          => null,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
        $userId = $this->db->insertID();
        echo "DemoDataSeeder: created demo owner user id={$userId}\n";

        // -------------------------------------------------------
        // 6. Assign owner to HQ branch as primary
        // -------------------------------------------------------
        $this->db->table('user_branches')->insert([
            'user_id'    => $userId,
            'branch_id'  => $branchId,
            'is_primary' => 1,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        echo "DemoDataSeeder: assigned owner to HQ branch.\n";

        // -------------------------------------------------------
        // 7. Tenant settings
        // -------------------------------------------------------
        $this->db->table('tenant_settings')->insert([
            'tenant_id'               => $tenantId,
            'branding_name'           => 'Demo Institute',
            'logo_path'               => null,
            'favicon_path'            => null,
            'default_timezone'        => 'Asia/Kolkata',
            'default_currency_code'   => 'INR',
            'locale_code'             => 'en',
            'branch_visibility_mode'  => 'own',
            'enquiry_visibility_mode' => 'own',
            'admission_visibility_mode' => 'own',
            'created_at'              => $now,
            'updated_at'              => $now,
        ]);
        echo "DemoDataSeeder: created tenant_settings.\n";

        echo "DemoDataSeeder: complete.\n";
        echo "---\n";
        echo "Demo login  →  demo@edcrm.in  /  Demo@1234\n";
    }
}
