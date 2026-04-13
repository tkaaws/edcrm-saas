<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Services\TenantResolver;
use App\Services\BranchContextResolver;
use App\Services\PermissionService;
use App\Services\CurrentUserContext;
use App\Services\AuthService;

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
}
