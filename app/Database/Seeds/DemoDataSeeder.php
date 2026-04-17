<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * DemoDataSeeder
 *
 * Creates one complete demo setup with:
 * - 1 platform admin user (tenantless)
 * - 1 tenant (Demo Institute)
 * - 1 branch (HQ)
 * - 1 global platform_admin role
 * - all tenant system roles under the demo tenant
 * - role-privilege mappings for each tenant system role
 * - 1 tenant_owner user for the demo tenant
 * - tenant_settings row
 * - tenant owner assigned to HQ branch as primary
 *
 * Platform login: platform@edcrm.in / Demo@1234
 * Tenant login: owner@demo.edcrm.in / Demo@1234
 */
class DemoDataSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $existing = $this->db->table('tenants')->where('slug', 'demo-institute')->countAllResults();
        if ($existing > 0) {
            return;
        }

        $this->db->table('tenants')->insert([
            'name'                  => 'Demo Institute',
            'slug'                  => 'demo-institute',
            'status'                => 'active',
            'legal_name'            => 'Demo Institute Pvt Ltd',
            'owner_name'            => 'Demo Owner',
            'owner_email'           => 'owner@demo.edcrm.in',
            'owner_phone'           => '+910000000001',
            'default_timezone'      => 'Asia/Kolkata',
            'default_currency_code' => 'INR',
            'country_code'          => 'IN',
            'locale_code'           => 'en',
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);
        $tenantId = $this->db->insertID();

        $this->db->table('tenant_branches')->insert([
            'tenant_id'    => $tenantId,
            'name'         => 'HQ',
            'code'         => 'HQ',
            'type'         => 'main',
            'country_code' => 'IN',
            'state_code'   => 'MH',
            'city'         => 'Pune',
            'timezone'     => null,
            'currency_code'=> null,
            'status'       => 'active',
            'created_by'   => null,
            'updated_by'   => null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        $branchId = $this->db->insertID();

        $this->db->table('colleges')->insert([
            'tenant_id'   => $tenantId,
            'name'        => 'Test College',
            'city_name'   => 'Pune',
            'state_name'  => 'Maharashtra',
            'status'      => 'active',
            'created_by'  => null,
            'updated_by'  => null,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        $existingPlatformRole = $this->db->table('user_roles')
            ->where('tenant_id', null)
            ->where('code', 'platform_admin')
            ->get()
            ->getRow();

        if ($existingPlatformRole) {
            $platformRoleId = (int) $existingPlatformRole->id;
        } else {
            $this->db->table('user_roles')->insert([
                'tenant_id'        => null,
                'name'             => 'Platform Admin',
                'code'             => 'platform_admin',
                'access_behavior'  => 'tenant',
                'is_system'        => 1,
                'status'           => 'active',
                'created_by'       => null,
                'updated_by'       => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
            $platformRoleId = (int) $this->db->insertID();
        }

        $roleDefs = [
            ['code' => 'tenant_owner',   'name' => 'Tenant Owner',   'is_system' => 1, 'access_behavior' => 'tenant'],
            ['code' => 'tenant_admin',   'name' => 'Tenant Admin',   'is_system' => 1, 'access_behavior' => 'tenant'],
            ['code' => 'branch_manager', 'name' => 'Branch Manager', 'is_system' => 1, 'access_behavior' => 'branch'],
            ['code' => 'counsellor',     'name' => 'Counsellor',     'is_system' => 1, 'access_behavior' => 'hierarchy'],
            ['code' => 'accounts',       'name' => 'Accounts',       'is_system' => 1, 'access_behavior' => 'branch'],
            ['code' => 'operations',     'name' => 'Operations',     'is_system' => 1, 'access_behavior' => 'branch'],
            ['code' => 'placement',      'name' => 'Placement',      'is_system' => 1, 'access_behavior' => 'branch'],
            ['code' => 'faculty',        'name' => 'Faculty',        'is_system' => 1, 'access_behavior' => 'hierarchy'],
            ['code' => 'support_agent',  'name' => 'Support Agent',  'is_system' => 1, 'access_behavior' => 'branch'],
        ];

        $roleIds = [];
        foreach ($roleDefs as $role) {
            $this->db->table('user_roles')->insert([
                'tenant_id'        => $tenantId,
                'name'             => $role['name'],
                'code'             => $role['code'],
                'access_behavior'  => $role['access_behavior'],
                'is_system'        => $role['is_system'],
                'status'           => 'active',
                'created_by'       => null,
                'updated_by'       => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
            $roleIds[$role['code']] = $this->db->insertID();
        }

        $allPrivileges = $this->db->table('privileges')->get()->getResultArray();
        $privMap = array_column($allPrivileges, 'id', 'code');

        if (empty($privMap)) {
            return;
        }

        $roleMappings = [
            'tenant_owner' => array_keys($privMap),
            'tenant_admin' => array_values(array_filter(
                array_keys($privMap),
                static fn($code) => $code !== 'billing.manage'
            )),
            'branch_manager' => [
                'users.view', 'users.impersonate', 'branches.view', 'roles.view', 'settings.view',
                'colleges.view', 'colleges.create', 'colleges.edit',
                'enquiries.view', 'enquiries.create', 'enquiries.edit',
                'enquiries.assign', 'enquiries.bulk_assign', 'enquiries.export',
                'enquiries.view_mobile_number', 'enquiries.update_contact_info', 'enquiries.update_college_info',
                'enquiries.close', 'enquiries.reopen',
                'enquiries.convert_to_admission', 'enquiries.view_created_on',
                'enquiries.view_modified_on', 'enquiries.view_created_by',
                'enquiries.view_modified_by', 'enquiries.reassign_in_edit',
                'enquiries.expired_assign', 'enquiries.closed_assign',
                'enquiries.assignment_history_view', 'enquiries.activity_view',
                'followups.view', 'followups.create', 'followups.edit', 'followups.delete',
                'admissions.view', 'admissions.create', 'admissions.edit', 'admissions.approve',
                'fees.view', 'fees.create', 'fees.edit', 'fees.receipts',
                'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.close',
                'students.view', 'students.edit', 'students.attendance', 'students.export',
                'batches.view',
                'reports.view', 'reports.advanced', 'reports.export',
                'whatsapp.view', 'whatsapp.send',
            ],
            'counsellor' => [
                'enquiries.view', 'enquiries.create', 'enquiries.edit',
                'enquiries.assign', 'followups.view', 'followups.create', 'followups.edit', 'followups.delete',
                'enquiries.view_mobile_number', 'enquiries.update_contact_info', 'enquiries.update_college_info',
                'enquiries.close', 'enquiries.convert_to_admission',
                'admissions.view', 'admissions.create', 'students.view',
                'reports.view', 'whatsapp.send',
            ],
            'accounts' => [
                'admissions.view', 'fees.view', 'fees.create', 'fees.edit', 'fees.receipts',
                'fees.discount', 'fees.structure', 'students.view',
                'reports.view', 'reports.export',
            ],
            'operations' => [
                'admissions.view', 'students.view', 'students.edit', 'students.attendance', 'students.export',
                'batches.view', 'batches.create', 'batches.edit',
                'tickets.view', 'tickets.create', 'tickets.edit',
                'reports.view',
            ],
            'placement' => [
                'students.view', 'placement.view', 'placement.manage',
                'placement.jobs', 'placement.interviews', 'placement.mock',
                'placement.college', 'reports.view', 'whatsapp.send',
            ],
            'faculty' => [
                'batches.view', 'students.view', 'students.attendance',
            ],
            'support_agent' => [
                'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.close',
                'students.view',
            ],
        ];

        $rpRows = [];
        foreach ($roleMappings as $roleCode => $privCodes) {
            $roleId = $roleIds[$roleCode] ?? null;
            if (! $roleId) {
                continue;
            }
            foreach ($privCodes as $privCode) {
                $privId = $privMap[$privCode] ?? null;
                if (! $privId) {
                    continue;
                }
                $rpRows[] = [
                    'role_id'      => $roleId,
                    'privilege_id' => $privId,
                    'created_at'   => $now,
                ];
            }
        }
        $this->db->table('role_privileges')->insertBatch($rpRows);

        $this->db->table('users')->insert([
            'tenant_id'           => null,
            'role_id'             => $platformRoleId,
            'employee_code'       => 'EMP-001',
            'username'            => 'platform_admin',
            'email'               => 'platform@edcrm.in',
            'first_name'          => 'Platform',
            'last_name'           => 'Admin',
            'mobile_number'       => '+910000000000',
            'whatsapp_number'     => null,
            'department'          => 'Platform',
            'designation'         => 'Platform Admin',
            'password_hash'       => password_hash('Demo@1234', PASSWORD_BCRYPT),
            'allow_impersonation' => 1,
            'is_active'           => 1,
            'must_reset_password' => 0,
            'last_login_at'       => null,
            'last_login_ip'       => null,
            'created_by'          => null,
            'updated_by'          => null,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
        $platformUserId = $this->db->insertID();

        $this->db->table('users')->insert([
            'tenant_id'           => $tenantId,
            'role_id'             => $roleIds['tenant_owner'],
            'employee_code'       => 'OWN-001',
            'username'            => 'demo_owner',
            'email'               => 'owner@demo.edcrm.in',
            'first_name'          => 'Demo',
            'last_name'           => 'Owner',
            'mobile_number'       => '+910000000001',
            'whatsapp_number'     => null,
            'department'          => 'Management',
            'designation'         => 'Tenant Owner',
            'password_hash'       => password_hash('Demo@1234', PASSWORD_BCRYPT),
            'allow_impersonation' => 1,
            'is_active'           => 1,
            'must_reset_password' => 0,
            'last_login_at'       => null,
            'last_login_ip'       => null,
            'created_by'          => null,
            'updated_by'          => null,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
        $tenantOwnerUserId = $this->db->insertID();

        $this->db->table('user_branches')->insert([
            'user_id'    => $tenantOwnerUserId,
            'branch_id'  => $branchId,
            'is_primary' => 1,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->table('tenant_settings')->insert([
            'tenant_id'                 => $tenantId,
            'branding_name'             => 'Demo Institute',
            'logo_path'                 => null,
            'favicon_path'              => null,
            'default_timezone'          => 'Asia/Kolkata',
            'default_currency_code'     => 'INR',
            'locale_code'               => 'en',
            'branch_visibility_mode'    => 'own',
            'enquiry_visibility_mode'   => 'own',
            'admission_visibility_mode' => 'own',
            'created_at'                => $now,
            'updated_at'                => $now,
        ]);

        $this->db->table('tenant_setting_values')->insert([
            'tenant_id'   => $tenantId,
            'key'         => 'enquiry.visibility.mode',
            'value'       => 'self',
            'value_type'  => 'string',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }
}
