<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\TenantAccessPolicy;

/**
 * SuspensionFilter
 *
 * Enforces subscription-based access restrictions on operational routes.
 *
 * Delegates the access decision to TenantAccessPolicy, which contains
 * all tenant status and (in Phase 1B) subscription state logic.
 * This filter file does not need to change when billing rules evolve.
 *
 * Routes always accessible regardless of status:
 * - /auth/*
 * - /billing/*
 * - /support/*
 */
class SuspensionFilter implements FilterInterface
{
    protected array $alwaysAllowed = [
        '/auth/login',
        '/auth/logout',
        '/auth/forgot-password',
        '/auth/reset-password',
        '/billing',
        '/support',
    ];

    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $tenantId = (int) session()->get('tenant_id');
        if (! $tenantId) {
            return null;
        }

        $currentPath = '/' . ltrim($request->getUri()->getPath(), '/');

        foreach ($this->alwaysAllowed as $allowed) {
            if (str_starts_with($currentPath, $allowed)) {
                return null;
            }
        }

        $roleCode = (string) session()->get('user_role_code');
        $result   = service('tenantAccessPolicy')->check($tenantId, $roleCode, $request->getMethod());

        return match ($result) {
            TenantAccessPolicy::ALLOW     => null,

            TenantAccessPolicy::WARN_READ => $this->allowWithWarning(),

            TenantAccessPolicy::DENY_WRITE => $this->denyWrite($request),

            TenantAccessPolicy::DENY_BLOCKED => $this->denyBlocked($tenantId),

            default => null,
        };
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }

    protected function allowWithWarning(): null
    {
        session()->setFlashdata('suspension_warning', true);
        return null;
    }

    protected function denyWrite(RequestInterface $request): mixed
    {
        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['error' => 'Account suspended. Contact your administrator.']);
        }

        return redirect()->back()
                         ->with('error', 'Your account is suspended. New records cannot be created. Please contact your administrator.');
    }

    protected function denyBlocked(int $tenantId): mixed
    {
        $message = service('tenantAccessPolicy')->blockedMessage($tenantId);
        return redirect()->to('/auth/login')->with('error', $message);
    }
}
