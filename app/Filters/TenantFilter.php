<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TenantFilter
 *
 * Verifies a valid tenant_id is in session on every protected request.
 * Catches cases where session has user_id but tenant_id was somehow lost.
 * Prevents any cross-tenant data leakage at the request entry point.
 */
class TenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $tenantId = session()->get('tenant_id');

        if (! $tenantId) {
            session()->destroy();
            return redirect()->to('/auth/login')
                             ->with('error', 'Session expired. Please log in again.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
