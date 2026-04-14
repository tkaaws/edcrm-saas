<?php

namespace App\Services;

use App\Models\PlanModel;
use CodeIgniter\Database\BaseConnection;

class TenantProvisioningService
{
    protected BaseConnection $db;
    protected PlanModel $planModel;

    public function __construct()
    {
        $this->db        = db_connect();
        $this->planModel = new PlanModel();
    }

    /**
     * @param array<string, string> $data
     *
     * @return array<string, mixed>
     */
    public function provision(array $data): array
    {
        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        $tenantId = $this->createTenant($data, $now);
        $branchId = $this->createBranch($tenantId, $data, $now);
        $roleIds  = $this->createSystemRoles($tenantId, $now);
        $this->attachRolePrivileges($roleIds, $now);
        $userId   = $this->createOwnerUser($tenantId, $roleIds['tenant_owner'], $data, $now);
        $this->assignUserToPrimaryBranch($userId, $branchId, $now);
        $this->createTenantSettings($tenantId, $data, $now);

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Tenant provisioning failed.');
        }

        // Create trial subscription outside the main transaction — non-critical,
        // failure here should not roll back the tenant.
        $this->createTrialSubscription($tenantId);

        return [
            'tenant_id'      => $tenantId,
            'branch_id'      => $branchId,
            'owner_user_id'  => $userId,
            'owner_email'    => $data['owner_email'],
            'tenant_slug'    => $data['slug'],
        ];
    }

    /**
     * @param array<string, string> $data
     */
    protected function createTenant(array $data, string $now): int
    {
        $this->db->table('tenants')->insert([
            'name'                  => $data['name'],
            'slug'                  => $data['slug'],
            'status'                => $data['status'],
            'legal_name'            => $data['legal_name'],
            'owner_name'            => $data['owner_name'],
            'owner_email'           => $data['owner_email'],
            'owner_phone'           => $data['owner_phone'],
            'default_timezone'      => $data['default_timezone'],
            'default_currency_code' => $data['default_currency_code'],
            'country_code'          => $data['country_code'],
            'locale_code'           => $data['locale_code'],
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, string> $data
     */
    protected function createBranch(int $tenantId, array $data, string $now): int
    {
        $this->db->table('tenant_branches')->insert([
            'tenant_id'      => $tenantId,
            'name'           => $data['branch_name'],
            'code'           => $data['branch_code'],
            'type'           => $data['branch_type'],
            'country_code'   => $data['country_code'],
            'state_code'     => $data['branch_state_code'],
            'city'           => $data['branch_city'],
            'address_line_1' => $data['branch_address_line_1'],
            'address_line_2' => $data['branch_address_line_2'],
            'postal_code'    => $data['branch_postal_code'],
            'timezone'       => $data['branch_timezone'] ?: null,
            'currency_code'  => $data['branch_currency_code'] ?: null,
            'status'         => 'active',
            'created_by'     => null,
            'updated_by'     => null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @return array<string, int>
     */
    protected function createSystemRoles(int $tenantId, string $now): array
    {
        $definitions = [
            ['code' => 'tenant_owner', 'name' => 'Tenant Owner'],
            ['code' => 'tenant_admin', 'name' => 'Tenant Admin'],
            ['code' => 'branch_manager', 'name' => 'Branch Manager'],
            ['code' => 'counsellor', 'name' => 'Counsellor'],
            ['code' => 'accounts', 'name' => 'Accounts'],
            ['code' => 'operations', 'name' => 'Operations'],
            ['code' => 'placement', 'name' => 'Placement'],
            ['code' => 'faculty', 'name' => 'Faculty'],
            ['code' => 'support_agent', 'name' => 'Support Agent'],
        ];

        $roleIds = [];

        foreach ($definitions as $definition) {
            $this->db->table('tenant_roles')->insert([
                'tenant_id'  => $tenantId,
                'name'       => $definition['name'],
                'code'       => $definition['code'],
                'is_system'  => 1,
                'status'     => 'active',
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $roleIds[$definition['code']] = (int) $this->db->insertID();
        }

        return $roleIds;
    }

    /**
     * @param array<string, int> $roleIds
     */
    protected function attachRolePrivileges(array $roleIds, string $now): void
    {
        $allPrivileges = $this->db->table('privileges')->get()->getResultArray();
        $privMap = array_column($allPrivileges, 'id', 'code');

        $roleMappings = [
            'tenant_owner' => array_keys($privMap),
            'tenant_admin' => array_values(array_filter(array_keys($privMap), static fn(string $code) => $code !== 'billing.manage')),
            'branch_manager' => [
                'users.view', 'branches.view', 'roles.view', 'settings.view',
                'enquiries.view', 'enquiries.create', 'enquiries.edit',
                'enquiries.assign', 'enquiries.bulk_assign', 'enquiries.export',
                'followups.view', 'followups.create', 'followups.edit',
                'admissions.view', 'admissions.create', 'admissions.edit', 'admissions.approve',
                'fees.view', 'fees.create', 'fees.edit', 'fees.receipts',
                'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.close',
                'students.view', 'students.edit', 'students.attendance', 'students.export',
                'batches.view', 'reports.view', 'reports.advanced', 'reports.export',
                'whatsapp.view', 'whatsapp.send',
            ],
            'counsellor' => [
                'enquiries.view', 'enquiries.create', 'enquiries.edit', 'enquiries.assign',
                'followups.view', 'followups.create', 'followups.edit',
                'admissions.view', 'admissions.create', 'students.view', 'reports.view', 'whatsapp.send',
            ],
            'accounts' => [
                'admissions.view', 'fees.view', 'fees.create', 'fees.edit', 'fees.receipts',
                'fees.discount', 'fees.structure', 'students.view', 'reports.view', 'reports.export',
            ],
            'operations' => [
                'admissions.view', 'students.view', 'students.edit', 'students.attendance', 'students.export',
                'batches.view', 'batches.create', 'batches.edit',
                'tickets.view', 'tickets.create', 'tickets.edit', 'reports.view',
            ],
            'placement' => [
                'students.view', 'placement.view', 'placement.manage', 'placement.jobs',
                'placement.interviews', 'placement.mock', 'placement.college', 'reports.view', 'whatsapp.send',
            ],
            'faculty' => [
                'batches.view', 'students.view', 'students.attendance',
            ],
            'support_agent' => [
                'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.close', 'students.view',
            ],
        ];

        $rows = [];
        foreach ($roleMappings as $roleCode => $privilegeCodes) {
            $roleId = $roleIds[$roleCode] ?? null;
            if (! $roleId) {
                continue;
            }

            foreach ($privilegeCodes as $privilegeCode) {
                $privilegeId = $privMap[$privilegeCode] ?? null;
                if (! $privilegeId) {
                    continue;
                }

                $rows[] = [
                    'role_id'      => $roleId,
                    'privilege_id' => $privilegeId,
                    'created_at'   => $now,
                ];
            }
        }

        if ($rows !== []) {
            $this->db->table('role_privileges')->insertBatch($rows);
        }
    }

    /**
     * @param array<string, string> $data
     */
    protected function createOwnerUser(int $tenantId, int $roleId, array $data, string $now): int
    {
        $this->db->table('users')->insert([
            'tenant_id'           => $tenantId,
            'role_id'             => $roleId,
            'employee_code'       => $data['owner_employee_code'],
            'username'            => $data['owner_username'],
            'email'               => $data['owner_email'],
            'first_name'          => $data['owner_first_name'],
            'last_name'           => $data['owner_last_name'],
            'mobile_number'       => $data['owner_phone'],
            'whatsapp_number'     => $data['owner_phone'],
            'department'          => 'Management',
            'designation'         => 'Owner',
            'password_hash'       => password_hash($data['owner_password'], PASSWORD_BCRYPT),
            'is_active'           => 1,
            'must_reset_password' => 0,
            'last_login_at'       => null,
            'last_login_ip'       => null,
            'created_by'          => null,
            'updated_by'          => null,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        return (int) $this->db->insertID();
    }

    protected function assignUserToPrimaryBranch(int $userId, int $branchId, string $now): void
    {
        $this->db->table('user_branches')->insert([
            'user_id'    => $userId,
            'branch_id'  => $branchId,
            'is_primary' => 1,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param array<string, string> $data
     */
    protected function createTenantSettings(int $tenantId, array $data, string $now): void
    {
        $this->db->table('tenant_settings')->insert([
            'tenant_id'                 => $tenantId,
            'branding_name'             => $data['branding_name'] ?: $data['name'],
            'logo_path'                 => null,
            'favicon_path'              => null,
            'default_timezone'          => $data['default_timezone'],
            'default_currency_code'     => $data['default_currency_code'],
            'locale_code'               => $data['locale_code'],
            'branch_visibility_mode'    => $data['branch_visibility_mode'],
            'enquiry_visibility_mode'   => $data['enquiry_visibility_mode'],
            'admission_visibility_mode' => $data['admission_visibility_mode'],
            'created_at'                => $now,
            'updated_at'                => $now,
        ]);
    }

    /**
     * Create a 14-day trial subscription for the newly provisioned tenant.
     * Uses the 'starter' plan by default; falls back gracefully if no plan exists.
     */
    protected function createTrialSubscription(int $tenantId): void
    {
        try {
            $plan = $this->planModel->findByCode('starter');

            if (! $plan) {
                log_message('warning', "TenantProvisioningService: no 'starter' plan found — trial subscription skipped for tenant {$tenantId}");
                return;
            }

            service('subscriptionPolicy')->createTrialSubscription($tenantId, (int) $plan->id, 14);

            log_message('info', "TenantProvisioningService: trial subscription created for tenant {$tenantId}");
        } catch (\Throwable $e) {
            log_message('error', "TenantProvisioningService: failed to create trial subscription for tenant {$tenantId}: " . $e->getMessage());
        }
    }
}
