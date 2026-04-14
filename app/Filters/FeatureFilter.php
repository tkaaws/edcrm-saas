<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * FeatureFilter
 *
 * Blocks access to routes whose module is not enabled for the tenant.
 *
 * Usage in Routes.php:
 *   ['filter' => 'feature:admissions']
 *   ['filter' => ['auth', 'tenant', 'suspension', 'feature:placement']]
 *
 * The argument is the feature_catalog code (e.g. admissions, placement,
 * service_tickets, batch_management, whatsapp, advanced_reports, student_portal).
 *
 * crm_core is always-on and is never blocked by this filter.
 *
 * Platform admins bypass feature gating entirely — they are never
 * resolved against a tenant's feature set.
 */
class FeatureFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): mixed
    {
        // No feature argument supplied — nothing to gate
        if (empty($arguments)) {
            return null;
        }

        $featureCode = (string) $arguments[0];

        // crm_core is always on — never block it
        if ($featureCode === 'crm_core') {
            return null;
        }

        $tenantId = (int) session()->get('tenant_id');
        if (! $tenantId) {
            // No tenant in session — TenantFilter will have already handled this
            return null;
        }

        $enabled = service('featureGate')->isEnabled($tenantId, $featureCode);

        if ($enabled) {
            return null;
        }

        // Feature is not enabled — deny access
        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['error' => 'This module is not available on your current plan.']);
        }

        return redirect()->to('/dashboard')
                         ->with('error', 'This module is not included in your current plan. Contact your administrator to upgrade.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
