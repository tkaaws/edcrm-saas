<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\ImpersonationService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ImpersonationServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
    protected $refresh = true;
    protected $seed = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        session()->destroy();
    }

    protected function tearDown(): void
    {
        session()->destroy();
        parent::tearDown();
    }

    public function testPlatformAdminCanImpersonateTenantOwnerAndReturn(): void
    {
        $platformAdmin = $this->db->table('users')->where('email', 'platform@edcrm.in')->get()->getRow();
        $tenantOwner = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();

        session()->set([
            'user_id'          => (int) $platformAdmin->id,
            'tenant_id'        => null,
            'user_role_code'   => 'platform_admin',
            'user_first_name'  => $platformAdmin->first_name,
            'user_last_name'   => $platformAdmin->last_name,
            'user_email'       => $platformAdmin->email,
            'user_privilege_codes' => [],
        ]);

        $service = new ImpersonationService();
        $service->start((int) $tenantOwner->id, 'Support investigation');

        $this->assertTrue((bool) session()->get('impersonation_active'));
        $this->assertSame((int) $platformAdmin->id, (int) session()->get('original_user_id'));
        $this->assertSame((int) $tenantOwner->id, (int) session()->get('user_id'));
        $this->assertSame('tenant_owner', session()->get('user_role_code'));
        $this->assertSame(1, $this->db->table('impersonation_sessions')->countAllResults());

        $record = $this->db->table('impersonation_sessions')->get()->getRow();
        $this->assertNull($record->ended_at);

        $service->stop();

        $this->assertFalse((bool) session()->get('impersonation_active'));
        $this->assertSame((int) $platformAdmin->id, (int) session()->get('user_id'));
        $this->assertSame('platform_admin', session()->get('user_role_code'));

        $endedRecord = $this->db->table('impersonation_sessions')->get()->getRow();
        $this->assertNotNull($endedRecord->ended_at);
    }

    public function testTenantOwnerCanImpersonateBranchManagerWithinSameTenant(): void
    {
        $tenantOwner = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();
        $branchManagerId = $this->createBranchManager();

        session()->set([
            'user_id'               => (int) $tenantOwner->id,
            'tenant_id'             => (int) $tenantOwner->tenant_id,
            'user_role_code'        => 'tenant_owner',
            'user_first_name'       => $tenantOwner->first_name,
            'user_last_name'        => $tenantOwner->last_name,
            'user_email'            => $tenantOwner->email,
            'user_privilege_codes'  => $this->getPrivilegeCodesForRole((int) $tenantOwner->role_id),
        ]);

        $service = new ImpersonationService();
        $service->start($branchManagerId, 'Tenant support review');

        $this->assertTrue((bool) session()->get('impersonation_active'));
        $this->assertSame($branchManagerId, (int) session()->get('user_id'));
        $this->assertSame('branch_manager', session()->get('user_role_code'));
    }

    public function testTenantOwnerCannotImpersonateWhenTenantSettingDisablesIt(): void
    {
        $tenantOwner = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();
        $branchManagerId = $this->createBranchManager();

        $this->db->table('tenant_setting_values')->insert([
            'tenant_id'   => (int) $tenantOwner->tenant_id,
            'key'         => 'tenant.security.allow_impersonation',
            'value'       => '0',
            'value_type'  => 'bool',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        session()->set([
            'user_id'               => (int) $tenantOwner->id,
            'tenant_id'             => (int) $tenantOwner->tenant_id,
            'user_role_code'        => 'tenant_owner',
            'user_first_name'       => $tenantOwner->first_name,
            'user_last_name'        => $tenantOwner->last_name,
            'user_email'            => $tenantOwner->email,
            'user_privilege_codes'  => $this->getPrivilegeCodesForRole((int) $tenantOwner->role_id),
        ]);

        $service = new ImpersonationService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tenant impersonation is disabled.');

        $service->start($branchManagerId, 'Tenant support review');
    }

    private function createBranchManager(): int
    {
        $tenantId = (int) $this->db->table('tenants')->where('slug', 'demo-institute')->get()->getRow()->id;
        $branchId = (int) $this->db->table('tenant_branches')->where('code', 'HQ')->get()->getRow()->id;
        $roleId = (int) $this->db->table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('code', 'branch_manager')
            ->get()
            ->getRow()
            ->id;

        $this->db->table('users')->insert([
            'tenant_id'           => $tenantId,
            'role_id'             => $roleId,
            'employee_code'       => 'BRM-IMP',
            'username'            => 'impersonation_branch_manager',
            'email'               => 'impersonation-branch-manager@demo.edcrm.in',
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

        return $userId;
    }

    /**
     * @return list<string>
     */
    private function getPrivilegeCodesForRole(int $roleId): array
    {
        $rows = $this->db->table('role_privileges rp')
            ->select('p.code')
            ->join('privileges p', 'p.id = rp.privilege_id')
            ->where('rp.role_id', $roleId)
            ->get()
            ->getResultArray();

        return array_column($rows, 'code');
    }
}
