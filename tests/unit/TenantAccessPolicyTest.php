<?php

use App\Models\TenantModel;
use App\Services\SubscriptionPolicyService;
use App\Services\TenantAccessPolicy;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * TenantAccessPolicyTest
 *
 * Pure unit test — no database migrations required.
 *
 * TenantModel is mocked via an anonymous subclass so the DB is never queried.
 * SubscriptionPolicyService is mocked via Services::injectMock() so the
 * CI4 service container returns the mock instead of the real service.
 * CIUnitTestCase resets the service container before each test (resetServices=true),
 * so injected mocks are always cleaned up automatically.
 *
 * @internal
 */
final class TenantAccessPolicyTest extends CIUnitTestCase
{
    // ---------------------------------------------------------------
    // Tenant account status — no subscription layer
    // ---------------------------------------------------------------

    public function testActiveTenantIsAllowed(): void
    {
        $policy = $this->makePolicy('active');

        $this->assertSame(TenantAccessPolicy::ALLOW, $policy->check(1, 'tenant_owner', 'GET'));
    }

    public function testSuspendedOperationalUserGetsReadOnlyWarning(): void
    {
        $policy = $this->makePolicy('suspended');

        $this->assertSame(TenantAccessPolicy::WARN_READ, $policy->check(1, 'counsellor', 'GET'));
    }

    public function testSuspendedOperationalUserCannotWrite(): void
    {
        $policy = $this->makePolicy('suspended');

        $this->assertSame(TenantAccessPolicy::DENY_WRITE, $policy->check(1, 'counsellor', 'POST'));
    }

    public function testSuspendedTenantOwnerKeepsAccess(): void
    {
        $policy = $this->makePolicy('suspended');

        $this->assertSame(TenantAccessPolicy::ALLOW, $policy->check(1, 'tenant_owner', 'POST'));
    }

    public function testDraftTenantIsBlockedWithActivationMessage(): void
    {
        $policy = $this->makePolicy('draft');

        $this->assertSame(TenantAccessPolicy::DENY_BLOCKED, $policy->check(1, 'tenant_owner', 'GET'));
        $this->assertSame('This account has not been activated yet.', $policy->blockedMessage(1));
    }

    public function testMissingTenantIsBlocked(): void
    {
        $policy = $this->makePolicy(null);

        $this->assertSame(TenantAccessPolicy::DENY_BLOCKED, $policy->check(999, 'tenant_owner', 'GET'));
    }

    // ---------------------------------------------------------------
    // Subscription state layer (tenant is 'active', sub status varies)
    // ---------------------------------------------------------------

    public function testGraceSubscriptionAllowsReadWithWarning(): void
    {
        $policy = $this->makePolicy('active', 'grace');

        $this->assertSame(TenantAccessPolicy::WARN_READ, $policy->check(1, 'counsellor', 'GET'));
    }

    public function testExpiredSubscriptionBlocksAccess(): void
    {
        $policy = $this->makePolicy('active', 'expired');

        $this->assertSame(TenantAccessPolicy::DENY_BLOCKED, $policy->check(1, 'tenant_owner', 'GET'));
    }

    public function testNoSubscriptionAllowsAccess(): void
    {
        $policy = $this->makePolicy('active', 'none');

        $this->assertSame(TenantAccessPolicy::ALLOW, $policy->check(1, 'tenant_owner', 'GET'));
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    /**
     * Build a TenantAccessPolicy with both dependencies mocked.
     *
     * @param string|null $tenantStatus  Tenant account status; null = tenant not found
     * @param string      $subStatus     Subscription status the mock service returns
     */
    private function makePolicy(?string $tenantStatus, string $subStatus = 'none'): TenantAccessPolicy
    {
        // --- Mock SubscriptionPolicyService via CI4 service container ---
        // Services::injectMock() replaces the shared service instance so
        // TenantAccessPolicy::getSubscriptionPolicy() returns this mock.
        // CIUnitTestCase::setUp() resets the container before each test,
        // so there is no leakage between test methods.
        $subMock = $this->createMock(SubscriptionPolicyService::class);
        $subMock->method('getStatus')->willReturn($subStatus);
        Services::injectMock('subscriptionPolicy', $subMock);

        // --- Mock TenantModel via anonymous subclass ---
        // TenantAccessPolicy creates a real TenantModel in __construct(), so
        // we extend the class and replace the property immediately after.
        $tenantMock = $this->createMock(TenantModel::class);
        $tenantMock->method('find')->willReturn(
            $tenantStatus === null ? null : (object) ['status' => $tenantStatus]
        );

        $policy = new class () extends TenantAccessPolicy {
            public function setTenantModel(TenantModel $model): void
            {
                $this->tenantModel = $model;
            }
        };

        $policy->setTenantModel($tenantMock);

        return $policy;
    }
}
