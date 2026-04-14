<?php

use App\Models\TenantModel;
use App\Services\SubscriptionPolicyService;
use App\Services\TenantAccessPolicy;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TenantAccessPolicyTest extends CIUnitTestCase
{
    public function testActiveTenantIsAllowed(): void
    {
        $policy = $this->makePolicyReturningStatus('active');

        $this->assertSame(TenantAccessPolicy::ALLOW, $policy->check(1, 'tenant_owner', 'GET'));
    }

    public function testSuspendedOperationalUserGetsReadOnlyWarning(): void
    {
        $policy = $this->makePolicyReturningStatus('suspended');

        $this->assertSame(TenantAccessPolicy::WARN_READ, $policy->check(1, 'counsellor', 'GET'));
    }

    public function testSuspendedOperationalUserCannotWrite(): void
    {
        $policy = $this->makePolicyReturningStatus('suspended');

        $this->assertSame(TenantAccessPolicy::DENY_WRITE, $policy->check(1, 'counsellor', 'POST'));
    }

    public function testSuspendedTenantOwnerKeepsAccess(): void
    {
        $policy = $this->makePolicyReturningStatus('suspended');

        $this->assertSame(TenantAccessPolicy::ALLOW, $policy->check(1, 'tenant_owner', 'POST'));
    }

    public function testDraftTenantIsBlockedWithActivationMessage(): void
    {
        $policy = $this->makePolicyReturningStatus('draft');

        $this->assertSame(TenantAccessPolicy::DENY_BLOCKED, $policy->check(1, 'tenant_owner', 'GET'));
        $this->assertSame('This account has not been activated yet.', $policy->blockedMessage(1));
    }

    public function testMissingTenantIsBlocked(): void
    {
        $policy = $this->makePolicyReturningStatus(null);

        $this->assertSame(TenantAccessPolicy::DENY_BLOCKED, $policy->check(999, 'tenant_owner', 'GET'));
    }

    public function testGraceSubscriptionAllowsReadWithWarning(): void
    {
        $policy = $this->makePolicyReturningStatus('active', 'grace');

        $this->assertSame(TenantAccessPolicy::WARN_READ, $policy->check(1, 'counsellor', 'GET'));
    }

    public function testExpiredSubscriptionBlocksAccess(): void
    {
        $policy = $this->makePolicyReturningStatus('active', 'expired');

        $this->assertSame(TenantAccessPolicy::DENY_BLOCKED, $policy->check(1, 'tenant_owner', 'GET'));
    }

    public function testNoSubscriptionAllowsAccess(): void
    {
        $policy = $this->makePolicyReturningStatus('active', 'none');

        $this->assertSame(TenantAccessPolicy::ALLOW, $policy->check(1, 'tenant_owner', 'GET'));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @param string|null $tenantStatus  Tenant account status (null = tenant not found)
     * @param string      $subStatus     Subscription status returned by mock service
     */
    private function makePolicyReturningStatus(?string $tenantStatus, string $subStatus = 'none'): TenantAccessPolicy
    {
        // Mock TenantModel
        $tenantModel = $this->createMock(TenantModel::class);
        $tenantModel->method('find')->willReturn(
            $tenantStatus === null ? null : (object) ['status' => $tenantStatus]
        );

        // Mock SubscriptionPolicyService — returns a fixed status, no DB required
        $subscriptionPolicy = $this->createMock(SubscriptionPolicyService::class);
        $subscriptionPolicy->method('getStatus')->willReturn($subStatus);

        $policy = new class () extends TenantAccessPolicy {
            public function replaceTenantModel(TenantModel $tenantModel): void
            {
                $this->tenantModel = $tenantModel;
            }

            public function replaceSubscriptionPolicy(SubscriptionPolicyService $service): void
            {
                $this->subscriptionPolicyInstance = $service;
            }
        };

        $policy->replaceTenantModel($tenantModel);
        $policy->replaceSubscriptionPolicy($subscriptionPolicy);

        return $policy;
    }
}
