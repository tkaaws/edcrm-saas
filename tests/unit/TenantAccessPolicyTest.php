<?php

use App\Models\TenantModel;
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

    private function makePolicyReturningStatus(?string $status): TenantAccessPolicy
    {
        $tenantModel = $this->createMock(TenantModel::class);
        $tenantModel->method('find')->willReturn($status === null ? null : (object) ['status' => $status]);

        $policy = new class () extends TenantAccessPolicy {
            public function replaceTenantModel(TenantModel $tenantModel): void
            {
                $this->tenantModel = $tenantModel;
            }
        };

        $policy->replaceTenantModel($tenantModel);

        return $policy;
    }
}
