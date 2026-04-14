<?php

use App\Services\FeatureGateService;
use App\Services\ImpersonationService;
use App\Services\TenantAccessPolicy;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class ImpersonationAndScopeRoutesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testBranchSettingsRedirectWhenUserLacksBranchEditPrivilege(): void
    {
        $this->allowTenantAndFeatures();

        $result = $this->withSession([
            'user_id'              => 1,
            'tenant_id'            => 1,
            'user_role_id'         => 1,
            'user_role_code'       => 'counsellor',
            'user_privilege_codes' => ['branches.view'],
        ])->get('/branches/1/settings');

        $result->assertRedirectTo('/dashboard');
    }

    public function testPlatformPolicyRedirectsNonPlatformUserToDashboard(): void
    {
        $result = $this->withSession([
            'user_id'        => 1,
            'tenant_id'      => 1,
            'user_role_code' => 'tenant_owner',
        ])->get('/platform/tenants/1/policy');

        $result->assertRedirectTo('/dashboard');
    }

    public function testImpersonationStartRedirectsGuestToLogin(): void
    {
        $result = $this->post('/impersonation/start/5', ['reason' => 'Support']);

        $result->assertRedirectTo('/auth/login');
    }

    public function testImpersonationStartUsesServiceAndRedirectsToDashboard(): void
    {
        $mock = $this->createMock(ImpersonationService::class);
        $mock->expects($this->once())
            ->method('start')
            ->with(5, 'Support review');
        Services::injectMock('impersonation', $mock);

        $result = $this->withSession([
            'user_id'        => 1,
            'tenant_id'      => 1,
            'user_role_code' => 'tenant_owner',
        ])->post('/impersonation/start/5', ['reason' => 'Support review']);

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
