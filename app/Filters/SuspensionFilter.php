<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\TenantModel;

/**
 * SuspensionFilter
 *
 * Enforces subscription-based access restrictions.
 * Applied on all operational routes (not billing/support routes).
 *
 * Phase 1A: checks tenant status (active/suspended/cancelled).
 * Phase 1B: will be extended to check subscription state machine.
 *
 * Restriction levels:
 * - active    → full access
 * - suspended → read-only for operational users, billing access for owner/admin
 * - cancelled → read-only data export access only
 * - draft     → blocked (not yet activated)
 */
class SuspensionFilter implements FilterInterface
{
    // Routes always accessible regardless of suspension
    protected array $alwaysAllowed = [
        '/auth/login',
        '/auth/logout',
        '/auth/forgot-password',
        '/auth/reset-password',
        '/billing',
        '/support',
    ];

    // HTTP methods that are write operations — blocked during suspension
    protected array $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $tenantId = session()->get('tenant_id');
        if (! $tenantId) return null;

        $currentPath = '/' . ltrim($request->getUri()->getPath(), '/');

        // Always allow billing and auth routes
        foreach ($this->alwaysAllowed as $allowed) {
            if (str_starts_with($currentPath, $allowed)) {
                return null;
            }
        }

        $tenant = (new TenantModel())->find($tenantId);
        if (! $tenant) return null;

        $roleCode = session()->get('user_role_code');

        return match ($tenant->status) {
            'active' => null, // full access

            'suspended' => $this->handleSuspended($request, $roleCode),

            'cancelled', 'draft' => $this->handleBlocked($tenant->status),

            default => null,
        };
    }

    protected function handleSuspended(RequestInterface $request, ?string $roleCode): mixed
    {
        // Owners and admins retain full access during suspension
        if (in_array($roleCode, ['tenant_owner', 'tenant_admin'], true)) {
            return null;
        }

        // Block all write operations for operational users
        if (in_array($request->getMethod(), $this->writeMethods, true)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['error' => 'Account suspended. Contact your administrator.']);
            }

            return redirect()->back()
                             ->with('error', 'Your account is suspended. New records cannot be created. Please contact your administrator.');
        }

        // Allow read-only access with warning
        session()->setFlashdata('suspension_warning', true);
        return null;
    }

    protected function handleBlocked(string $status): mixed
    {
        $message = $status === 'draft'
            ? 'This account has not been activated yet.'
            : 'This account has been cancelled. Please contact support to retrieve your data.';

        return redirect()->to('/auth/login')->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
