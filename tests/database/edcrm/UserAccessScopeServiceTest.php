<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\UserAccessScopeService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class UserAccessScopeServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        session()->destroy();
    }

    protected function tearDown(): void
    {
        session()->destroy();
        parent::tearDown();
    }

    public function testTenantAdminFallsBackToTenantLevelScopesWhenLegacyDefaultsExist(): void
    {
        $tenantId = $this->getDemoTenantId();
        $branchId = $this->getDemoBranchId();
        $roleId = $this->getRoleId('tenant_admin', $tenantId);

        $this->db->table('users')->insert([
            'tenant_id'           => $tenantId,
            'role_id'             => $roleId,
            'employee_code'       => 'ADM-LEGACY',
            'username'            => 'legacy_admin',
            'email'               => 'legacy-admin@demo.edcrm.in',
            'first_name'          => 'Legacy',
            'last_name'           => 'Admin',
            'password_hash'       => password_hash('Demo@1234', PASSWORD_BCRYPT),
            'data_scope'          => 'self',
            'manage_scope'        => 'none',
            'hierarchy_mode'      => 'hierarchy',
            'allow_impersonation' => 1,
            'is_active'           => 1,
            'must_reset_password' => 0,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
        $userId = (int) $this->db->insertID();

        $this->db->table('user_branches')->insert([
            'user_id'    => $userId,
            'branch_id'  => $branchId,
            'is_primary' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        session()->set([
            'user_id'        => $userId,
            'tenant_id'      => $tenantId,
            'user_role_code' => 'tenant_admin',
        ]);

        $service = new UserAccessScopeService();

        $this->assertContains('tenant', $service->getAllowedDataScopes());
        $this->assertContains('tenant', $service->getAllowedManageScopes());
        $this->assertTrue($service->canAssignScopes('tenant', 'tenant'));
    }

    public function testBranchManagerCanOnlyAssignUsersInsideOwnBranch(): void
    {
        [$managerId, $managerBranchId] = $this->createBranchManager();
        $secondBranchId = $this->createBranch('HYD');

        session()->set([
            'user_id'        => $managerId,
            'tenant_id'      => $this->getDemoTenantId(),
            'user_role_code' => 'branch_manager',
        ]);

        $service = new UserAccessScopeService();

        $this->assertTrue($service->canAssignBranches([$managerBranchId]));
        $this->assertFalse($service->canAssignBranches([$secondBranchId]));
        $this->assertFalse($service->canAssignBranches([$managerBranchId, $secondBranchId]));
    }

    public function testBranchManagerCannotManageTenantOwner(): void
    {
        [$managerId] = $this->createBranchManager();
        $owner = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();

        session()->set([
            'user_id'        => $managerId,
            'tenant_id'      => $this->getDemoTenantId(),
            'user_role_code' => 'branch_manager',
        ]);

        $service = new UserAccessScopeService();

        $this->assertFalse($service->canManageTargetUser($owner));
    }

    public function testTenantOwnerCanManageBranchManagerInSameTenant(): void
    {
        [$managerId] = $this->createBranchManager();
        $manager = $this->db->table('users')->where('id', $managerId)->get()->getRow();
        $owner = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();

        session()->set([
            'user_id'        => (int) $owner->id,
            'tenant_id'      => (int) $owner->tenant_id,
            'user_role_code' => 'tenant_owner',
        ]);

        $service = new UserAccessScopeService();

        $this->assertTrue($service->canManageTargetUser($manager));
    }

    private function createBranchManager(): array
    {
        $tenantId = $this->getDemoTenantId();
        $branchId = $this->getDemoBranchId();
        $roleId = $this->getRoleId('branch_manager', $tenantId);

        $this->db->table('users')->insert([
            'tenant_id'           => $tenantId,
            'role_id'             => $roleId,
            'employee_code'       => 'BRM-001',
            'username'            => 'branch_manager_1',
            'email'               => 'branch-manager-1@demo.edcrm.in',
            'first_name'          => 'Branch',
            'last_name'           => 'Manager',
            'password_hash'       => password_hash('Demo@1234', PASSWORD_BCRYPT),
            'data_scope'          => 'branch',
            'manage_scope'        => 'branch',
            'hierarchy_mode'      => 'hierarchy',
            'allow_impersonation' => 1,
            'is_active'           => 1,
            'must_reset_password' => 0,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
        $userId = (int) $this->db->insertID();

        $this->db->table('user_branches')->insert([
            'user_id'    => $userId,
            'branch_id'  => $branchId,
            'is_primary' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return [$userId, $branchId];
    }

    private function createBranch(string $code): int
    {
        $this->db->table('tenant_branches')->insert([
            'tenant_id'    => $this->getDemoTenantId(),
            'name'         => 'Branch ' . $code,
            'code'         => $code,
            'type'         => 'satellite',
            'country_code' => 'IN',
            'city'         => 'Pune',
            'status'       => 'active',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    private function getDemoTenantId(): int
    {
        return (int) $this->db->table('tenants')->where('slug', 'demo-institute')->get()->getRow()->id;
    }

    private function getDemoBranchId(): int
    {
        return (int) $this->db->table('tenant_branches')->where('code', 'HQ')->get()->getRow()->id;
    }

    private function getRoleId(string $roleCode, int $tenantId): int
    {
        return (int) $this->db->table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('code', $roleCode)
            ->get()
            ->getRow()
            ->id;
    }
}
