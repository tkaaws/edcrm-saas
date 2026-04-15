<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\ImpersonationService;
use App\Services\SettingsResolverService;
use Config\Services;
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

        Services::reset();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        session()->destroy();
    }

    protected function tearDown(): void
    {
        session()->destroy();
        Services::reset();
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
        $this->assertSame(1, (int) session()->get('impersonation_level'));
        $this->assertSame(1, $this->db->table('impersonation_sessions')->countAllResults());

        $record = $this->db->table('impersonation_sessions')->get()->getRow();
        $this->assertNull($record->ended_at);
        $this->assertSame(1, (int) $record->depth);
        $this->assertSame((int) $platformAdmin->id, (int) $record->root_actor_user_id);

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
        $this->assertSame(1, (int) session()->get('impersonation_level'));
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

        $resolver = new SettingsResolverService();
        $this->assertFalse((bool) $resolver->getEffectiveSetting((int) $tenantOwner->tenant_id, null, 'tenant.security.allow_impersonation'));

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

    public function testSupportStackAllowsFourLevelsAndReturnsLifo(): void
    {
        $platformAdmin = $this->db->table('users')->where('email', 'platform@edcrm.in')->get()->getRow();
        $tenantOwner = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();
        $tenantAdminIds = $this->createTenantAdmins(3);

        session()->set([
            'user_id'              => (int) $platformAdmin->id,
            'tenant_id'            => null,
            'user_role_code'       => 'platform_admin',
            'user_first_name'      => $platformAdmin->first_name,
            'user_last_name'       => $platformAdmin->last_name,
            'user_email'           => $platformAdmin->email,
            'user_privilege_codes' => [],
        ]);

        $service = new ImpersonationService();
        $service->start((int) $tenantOwner->id, 'Platform support');
        $service->start($tenantAdminIds[0], 'Owner to tenant admin');
        $service->start($tenantAdminIds[1], 'Tenant admin to tenant admin');
        $service->start($tenantAdminIds[2], 'Deep support review');

        $this->assertSame(4, (int) session()->get('impersonation_level'));
        $this->assertSame($tenantAdminIds[2], (int) session()->get('user_id'));
        $this->assertSame(
            ['Platform Admin', 'Demo Owner', 'Tenant Admin 1', 'Tenant Admin 2', 'Tenant Admin 3'],
            session()->get('impersonation_path')
        );

        $rows = $this->db->table('impersonation_sessions')->orderBy('id', 'ASC')->get()->getResult();
        $this->assertCount(4, $rows);
        $this->assertSame(1, (int) $rows[0]->depth);
        $this->assertSame(2, (int) $rows[1]->depth);
        $this->assertSame(3, (int) $rows[2]->depth);
        $this->assertSame(4, (int) $rows[3]->depth);
        $this->assertSame((int) $platformAdmin->id, (int) $rows[3]->root_actor_user_id);
        $this->assertSame((int) $rows[0]->id, (int) $rows[1]->parent_session_id);
        $this->assertSame((int) $rows[1]->id, (int) $rows[2]->parent_session_id);
        $this->assertSame((int) $rows[2]->id, (int) $rows[3]->parent_session_id);

        $service->stop();
        $this->assertSame($tenantAdminIds[1], (int) session()->get('user_id'));
        $this->assertSame(3, (int) session()->get('impersonation_level'));

        $service->stop();
        $this->assertSame($tenantAdminIds[0], (int) session()->get('user_id'));
        $this->assertSame(2, (int) session()->get('impersonation_level'));

        $service->stop();
        $this->assertSame((int) $tenantOwner->id, (int) session()->get('user_id'));
        $this->assertSame(1, (int) session()->get('impersonation_level'));

        $service->stop();
        $this->assertSame((int) $platformAdmin->id, (int) session()->get('user_id'));
        $this->assertFalse((bool) session()->get('impersonation_active'));
    }

    public function testSupportStackBlocksFifthLevel(): void
    {
        $platformAdmin = $this->db->table('users')->where('email', 'platform@edcrm.in')->get()->getRow();
        $tenantOwner = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();
        $tenantAdminIds = $this->createTenantAdmins(4);

        session()->set([
            'user_id'              => (int) $platformAdmin->id,
            'tenant_id'            => null,
            'user_role_code'       => 'platform_admin',
            'user_first_name'      => $platformAdmin->first_name,
            'user_last_name'       => $platformAdmin->last_name,
            'user_email'           => $platformAdmin->email,
            'user_privilege_codes' => [],
        ]);

        $service = new ImpersonationService();
        $service->start((int) $tenantOwner->id, 'Platform support');
        $service->start($tenantAdminIds[0], 'Level 2');
        $service->start($tenantAdminIds[1], 'Level 3');
        $service->start($tenantAdminIds[2], 'Level 4');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Maximum support depth reached. Return one level before continuing.');

        $service->start($tenantAdminIds[3], 'Level 5');
    }

    public function testStopAllReturnsToOriginalAccountAndClosesAllSessions(): void
    {
        $platformAdmin = $this->db->table('users')->where('email', 'platform@edcrm.in')->get()->getRow();
        $tenantOwner = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();
        $tenantAdminIds = $this->createTenantAdmins(2);

        session()->set([
            'user_id'              => (int) $platformAdmin->id,
            'tenant_id'            => null,
            'user_role_code'       => 'platform_admin',
            'user_first_name'      => $platformAdmin->first_name,
            'user_last_name'       => $platformAdmin->last_name,
            'user_email'           => $platformAdmin->email,
            'user_privilege_codes' => [],
        ]);

        $service = new ImpersonationService();
        $service->start((int) $tenantOwner->id, 'Platform support');
        $service->start($tenantAdminIds[0], 'Level 2');
        $service->start($tenantAdminIds[1], 'Level 3');

        $service->stopAll();

        $this->assertSame((int) $platformAdmin->id, (int) session()->get('user_id'));
        $this->assertFalse((bool) session()->get('impersonation_active'));

        $openSessions = $this->db->table('impersonation_sessions')->where('ended_at', null)->countAllResults();
        $this->assertSame(0, $openSessions);
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
     * @return list<int>
     */
    private function createTenantAdmins(int $count): array
    {
        $ids = [];

        for ($index = 1; $index <= $count; $index++) {
            $ids[] = $this->createNamedTenantAdmin($index);
        }

        return $ids;
    }

    private function createNamedTenantAdmin(int $index): int
    {
        $tenantId = (int) $this->db->table('tenants')->where('slug', 'demo-institute')->get()->getRow()->id;
        $branchId = (int) $this->db->table('tenant_branches')->where('code', 'HQ')->get()->getRow()->id;
        $roleId = (int) $this->db->table('user_roles')
            ->where('tenant_id', $tenantId)
            ->where('code', 'tenant_admin')
            ->get()
            ->getRow()
            ->id;

        $this->db->table('users')->insert([
            'tenant_id'           => $tenantId,
            'role_id'             => $roleId,
            'employee_code'       => 'TAD-' . $index,
            'username'            => 'impersonation_tenant_admin_' . $index,
            'email'               => 'impersonation-tenant-admin-' . $index . '@demo.edcrm.in',
            'first_name'          => 'Tenant',
            'last_name'           => 'Admin ' . $index,
            'password_hash'       => password_hash('Demo@1234', PASSWORD_BCRYPT),
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
