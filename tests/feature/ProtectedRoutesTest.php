<?php

use App\Services\FeatureGateService;
use App\Services\TenantAccessPolicy;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

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

    public function testBillingRedirectsWhenAuthenticatedUserLacksBillingPrivilege(): void
    {
        $this->allowTenantAndFeatures();

        $result = $this->withSession([
            'user_id'              => 1,
            'tenant_id'            => 1,
            'user_role_id'         => 1,
            'user_role_code'       => 'counsellor',
            'user_privilege_codes' => ['users.view'],
        ])->get('/billing');

        $result->assertRedirectTo('/dashboard');
    }

    public function testUsersCreateRedirectsWhenAuthenticatedUserLacksCreatePrivilege(): void
    {
        $this->allowTenantAndFeatures();

        $result = $this->withSession([
            'user_id'              => 1,
            'tenant_id'            => 1,
            'user_role_id'         => 1,
            'user_role_code'       => 'counsellor',
            'user_privilege_codes' => ['users.view'],
        ])->get('/users/create');

        $result->assertRedirectTo('/dashboard');
    }

    private function allowTenantAndFeatures(): void
    {
        $tenantPolicy = $this->createMock(TenantAccessPolicy::class);
        $tenantPolicy->method('check')->willReturn(TenantAccessPolicy::ALLOW);
        Services::injectMock('tenantAccessPolicy', $tenantPolicy);

        $featureGate = $this->createMock(FeatureGateService::class);
        $featureGate->method('isEnabled')->willReturn(true);
        Services::injectMock('featureGate', $featureGate);
    }
}
