<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\AuthService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class AuthServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

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

    public function testAttemptBuildsSessionAndWritesAuditLog(): void
    {
        $service = new AuthService();
        $result = $service->attempt('owner@demo.edcrm.in', 'Demo@1234');

        $this->assertSame(AuthService::LOGIN_SUCCESS, $result);
        $this->assertSame('owner@demo.edcrm.in', session()->get('user_email'));
        $this->assertSame('tenant_owner', session()->get('user_role_code'));
        $this->assertFalse((bool) session()->get('must_reset_password'));

        $user = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();

        $this->assertNotNull($user->last_login_at);
        $this->assertSame('127.0.0.1', $user->last_login_ip);
        $this->assertSame(1, $this->db->table('audit_logs')->where('action', 'login_success')->countAllResults());
    }

    public function testAttemptReturnsMustResetWhenFlagged(): void
    {
        $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->update([
            'must_reset_password' => 1,
        ]);

        $service = new AuthService();
        $result = $service->attempt('owner@demo.edcrm.in', 'Demo@1234');

        $this->assertSame(AuthService::LOGIN_MUST_RESET, $result);
        $this->assertTrue((bool) session()->get('must_reset_password'));
    }

    public function testForgotPasswordInvalidatesOldTokensAndCreatesFreshOne(): void
    {
        $userId = $this->getUserId();
        $this->db->table('password_reset_tokens')->insert([
            'user_id'    => $userId,
            'token_hash' => hash('sha256', 'old-token'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
            'used_at'    => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $service = new AuthService();
        $result = $service->forgotPassword('owner@demo.edcrm.in');

        $this->assertTrue($result);
        $this->assertSame(2, $this->db->table('password_reset_tokens')->countAllResults());
        $this->assertSame(1, $this->db->table('password_reset_tokens')->where('used_at', null)->countAllResults());
        $this->assertSame(1, $this->db->table('audit_logs')->where('action', 'password_reset_requested')->countAllResults());
    }

    public function testResetPasswordUpdatesHashArchivesHistoryAndConsumesToken(): void
    {
        $user = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();
        $plainToken = 'reset-token-123';

        $this->db->table('password_reset_tokens')->insert([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
            'used_at'    => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->set('must_reset_password', true);

        $service = new AuthService();
        $result = $service->resetPassword($plainToken, 'Fresh@1234');

        $this->assertTrue($result);
        $this->assertFalse((bool) session()->get('must_reset_password'));

        $updatedUser = $this->db->table('users')->where('id', $user->id)->get()->getRow();
        $usedToken = $this->db->table('password_reset_tokens')->where('token_hash', hash('sha256', $plainToken))->get()->getRow();

        $this->assertSame(0, (int) $updatedUser->must_reset_password);
        $this->assertTrue(password_verify('Fresh@1234', $updatedUser->password_hash));
        $this->assertNotNull($usedToken->used_at);
        $this->assertSame(1, $this->db->table('user_password_histories')->where('user_id', $user->id)->countAllResults());
        $this->assertSame(1, $this->db->table('audit_logs')->where('action', 'password_reset_completed')->countAllResults());
    }

    public function testChangePasswordRejectsReuseAndClearsResetFlagOnSuccess(): void
    {
        $user = $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow();

        $this->db->table('user_password_histories')->insert([
            'user_id'       => $user->id,
            'password_hash' => password_hash('Reuse@1234', PASSWORD_BCRYPT),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $service = new AuthService();

        $reusedResult = $service->changePassword($user->id, 'Demo@1234', 'Reuse@1234');
        $this->assertSame('password_reused', $reusedResult);

        session()->set('must_reset_password', true);
        $successResult = $service->changePassword($user->id, 'Demo@1234', 'BrandNew@1234');

        $this->assertTrue($successResult);
        $this->assertFalse((bool) session()->get('must_reset_password'));

        $updatedUser = $this->db->table('users')->where('id', $user->id)->get()->getRow();
        $this->assertSame(0, (int) $updatedUser->must_reset_password);
        $this->assertTrue(password_verify('BrandNew@1234', $updatedUser->password_hash));
        $this->assertSame(1, $this->db->table('audit_logs')->where('action', 'password_changed')->countAllResults());
    }

    private function getTenantId(): int
    {
        return (int) $this->db->table('tenants')->where('slug', 'demo-institute')->get()->getRow()->id;
    }

    private function getUserId(): int
    {
        return (int) $this->db->table('users')->where('email', 'owner@demo.edcrm.in')->get()->getRow()->id;
    }
}
