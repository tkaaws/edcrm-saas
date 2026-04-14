<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Services\TenantResolver;
use App\Services\BranchContextResolver;
use App\Services\PermissionService;
use App\Services\CurrentUserContext;
use App\Services\AuthService;
use App\Services\TenantProvisioningService;
use App\Services\TenantAccessPolicy;
use App\Services\SubscriptionPolicyService;
use App\Services\FeatureGateService;
use App\Services\UsageLimitService;
use App\Services\DelegationGuardService;
use App\Services\SettingsResolverService;

/**
 * Services Configuration
 *
 * Register application services here so they can be accessed via:
 *   service('tenantResolver')
 *   service('branchContext')
 *   service('permissions')
 *   service('userContext')
 *
 * Shared = true means one instance per request (default).
 */
class Services extends BaseService
{
    /**
     * Resolves the current tenant from session.
     */
    public static function tenantResolver(bool $getShared = true): TenantResolver
    {
        if ($getShared) {
            return static::getSharedInstance('tenantResolver');
        }
        return new TenantResolver();
    }

    /**
     * Resolves the current branch context and handles branch switching.
     */
    public static function branchContext(bool $getShared = true): BranchContextResolver
    {
        if ($getShared) {
            return static::getSharedInstance('branchContext');
        }
        return new BranchContextResolver();
    }

    /**
     * Checks user privileges.
     */
    public static function permissions(bool $getShared = true): PermissionService
    {
        if ($getShared) {
            return static::getSharedInstance('permissions');
        }
        return new PermissionService();
    }

    /**
     * Provides full current user context (user, tenant, branch, permissions).
     */
    public static function userContext(bool $getShared = true): CurrentUserContext
    {
        if ($getShared) {
            return static::getSharedInstance('userContext');
        }
        return new CurrentUserContext();
    }

    /**
     * Handles login, logout, forgot/reset password, password history.
     */
    public static function auth(bool $getShared = true): AuthService
    {
        if ($getShared) {
            return static::getSharedInstance('auth');
        }
        return new AuthService();
    }

    /**
     * Provisions a new tenant with branch, owner user, roles, and defaults.
     */
    public static function tenantProvisioning(bool $getShared = true): TenantProvisioningService
    {
        if ($getShared) {
            return static::getSharedInstance('tenantProvisioning');
        }
        return new TenantProvisioningService();
    }

    /**
     * Evaluates whether a tenant's status allows the current operation.
     * Used by SuspensionFilter; extended in Phase 1B for subscription checks.
     */
    public static function tenantAccessPolicy(bool $getShared = true): TenantAccessPolicy
    {
        if ($getShared) {
            return static::getSharedInstance('tenantAccessPolicy');
        }
        return new TenantAccessPolicy();
    }

    /**
     * Subscription state machine — status, transitions, trial provisioning.
     */
    public static function subscriptionPolicy(bool $getShared = true): SubscriptionPolicyService
    {
        if ($getShared) {
            return static::getSharedInstance('subscriptionPolicy');
        }
        return new SubscriptionPolicyService();
    }

    /**
     * Feature gate — is a module enabled for this tenant?
     */
    public static function featureGate(bool $getShared = true): FeatureGateService
    {
        if ($getShared) {
            return static::getSharedInstance('featureGate');
        }
        return new FeatureGateService();
    }

    /**
     * Usage limits — is the tenant at or over their user/branch cap?
     */
    public static function usageLimit(bool $getShared = true): UsageLimitService
    {
        if ($getShared) {
            return static::getSharedInstance('usageLimit');
        }
        return new UsageLimitService();
    }

    /**
     * Delegation guard — plan-aware + actor-aware privilege and role assignment.
     */
    public static function delegationGuard(bool $getShared = true): DelegationGuardService
    {
        if ($getShared) {
            return static::getSharedInstance('delegationGuard');
        }
        return new DelegationGuardService();
    }

    /**
     * Settings resolver â€” platform override -> branch override -> tenant value -> default.
     */
    public static function settingsResolver(bool $getShared = true): SettingsResolverService
    {
        if ($getShared) {
            return static::getSharedInstance('settingsResolver');
        }
        return new SettingsResolverService();
    }
}
