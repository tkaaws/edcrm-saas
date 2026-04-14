<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Shared shell context for authenticated admin pages.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function buildShellViewData(array $data = []): array
    {
        $firstName  = session()->get('user_first_name');
        $lastName   = session()->get('user_last_name');
        $roleCode   = session()->get('user_role_code');
        $roleName   = session()->get('user_role_name');
        $tenantId   = (int) session()->get('tenant_id');
        $branchId   = session()->get('branch_id');
        $tenantName = session()->get('tenant_name');
        $branchName = session()->get('branch_name');

        $platformTenantId = (int) env('APP_PLATFORM_TENANT_ID', 0);

        if ($platformTenantId > 0) {
            // Production: exact tenant match
            $isPlatformAdmin = $tenantId > 0 && $tenantId === $platformTenantId;
        } else {
            // Dev fallback: no env set — allow tenant_owner role (mirrors PlatformAdminFilter dev fallback)
            $isPlatformAdmin = ENVIRONMENT === 'development' && $roleCode === 'tenant_owner';
        }

        // Resolve enabled feature modules for nav gating.
        // Platform admins have no tenant subscription — skip gate lookup.
        $enabledModules = [];
        if ($tenantId && ! $isPlatformAdmin) {
            try {
                $enabledModules = service('featureGate')->getEnabledModules($tenantId);
            } catch (\Throwable) {
                // Billing catalog not yet seeded or service unavailable — degrade gracefully
                $enabledModules = [];
            }
        }

        return array_merge([
            'title'            => 'EDCRM SaaS',
            'pageTitle'        => 'Operations Workspace',
            'activeNav'        => 'dashboard',
            'tenantId'         => $tenantId,
            'branchId'         => $branchId,
            'roleCode'         => $roleCode,
            'tenantLabel'      => $tenantName ?: ($tenantId ? 'Tenant #' . $tenantId : 'Not resolved'),
            'branchLabel'      => $branchName ?: ($branchId ? 'Branch #' . $branchId : 'Not assigned'),
            'roleLabel'        => $roleName ?: ($roleCode ?: 'Unknown'),
            'userDisplayName'  => trim((string) $firstName . ' ' . (string) $lastName) ?: 'EDCRM User',
            'userEmail'        => session()->get('user_email'),
            'firstName'        => $firstName,
            'isPlatformAdmin'  => $isPlatformAdmin,
            'enabledModules'   => $enabledModules,
        ], $data);
    }
}
