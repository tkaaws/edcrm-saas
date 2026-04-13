<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * PlatformAdminFilter
 *
 * Restricts access to /platform/* routes to platform admins only.
 *
 * Platform admin identity is determined by:
 *   session tenant_id === APP_PLATFORM_TENANT_ID (env)
 *
 * This prevents any customer tenant user from accessing platform
 * management surfaces even if they know the URL.
 *
 * Setup: set APP_PLATFORM_TENANT_ID in .env to the tenant_id
 * of the platform's own admin tenant (created during first setup).
 */
class PlatformAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        // Must be authenticated first
        if (! session()->get('user_id')) {
            return redirect()->to('/auth/login')->with('error', 'Please log in to continue.');
        }

        $platformTenantId = (int) env('APP_PLATFORM_TENANT_ID', 0);

        // If env is not set, block all access to platform routes in production
        if ($platformTenantId === 0 && ENVIRONMENT !== 'development') {
            log_message('error', 'APP_PLATFORM_TENANT_ID is not set. Platform routes are blocked.');
            return redirect()->to('/dashboard')->with('error', 'Platform access is not configured.');
        }

        $sessionTenantId = (int) session()->get('tenant_id');

        // In development with no env set, allow if role is tenant_owner (demo mode only)
        if ($platformTenantId === 0 && ENVIRONMENT === 'development') {
            if (session()->get('user_role_code') === 'tenant_owner') {
                return null;
            }
        }

        if ($sessionTenantId !== $platformTenantId) {
            log_message('warning', "Unauthorised platform access attempt by user_id=" . session()->get('user_id') . " tenant_id={$sessionTenantId}");
            return redirect()->to('/dashboard')->with('error', 'You do not have access to this area.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
