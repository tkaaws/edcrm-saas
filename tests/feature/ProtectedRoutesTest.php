<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ProtectedRoutesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testDashboardRedirectsGuestToLogin(): void
    {
        $result = $this->get('/dashboard');

        $result->assertRedirectTo('/auth/login');
    }

    public function testUsersRedirectGuestToLogin(): void
    {
        $result = $this->get('/users');

        $result->assertRedirectTo('/auth/login');
    }

    public function testPasswordResetEnforcementRedirectsAuthenticatedUser(): void
    {
        $result = $this->withSession([
            'user_id'             => 1,
            'must_reset_password' => 1,
            'tenant_id'           => 1,
            'user_role_code'      => 'tenant_owner',
            'user_first_name'     => 'Demo',
            'user_last_name'      => 'Owner',
            'user_email'          => 'demo@edcrm.in',
        ])->get('/dashboard');

        $result->assertRedirectTo('/auth/change-password');
    }
}
