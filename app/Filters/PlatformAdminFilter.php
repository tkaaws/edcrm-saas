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
 * Platform admin identity is determined by role code:
 *   session user_role_code === 'platform_admin'
 *
 * No environment variables needed — any user with the platform_admin
 * role code (set in user_roles) is granted access.
 */
class PlatformAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        // Must be authenticated first
        if (! session()->get('user_id')) {
            return redirect()->to('/auth/login')->with('error', 'Please log in to continue.');
        }

        if (session()->get('user_role_code') !== 'platform_admin') {
            log_message('warning', 'Unauthorised platform access attempt by user_id=' . session()->get('user_id'));
            return redirect()->to('/dashboard')->with('error', 'You do not have access to this area.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
