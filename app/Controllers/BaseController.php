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
     * @var list<int>
     */
    protected array $perPageOptions = [10, 25, 50, 100];
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

        $isPlatformAdmin = $roleCode === 'platform_admin';

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

    /**
     * @param array<int, mixed> $items
     * @return array{items: array<int, mixed>, pagination: array<string, mixed>}
     */
    protected function paginateCollection(array $items, string $pageParam = 'page', string $perPageParam = 'per_page'): array
    {
        $query = $this->request->getGet();
        $requestedPerPage = (int) ($query[$perPageParam] ?? $this->perPageOptions[0]);
        $perPage = in_array($requestedPerPage, $this->perPageOptions, true) ? $requestedPerPage : $this->perPageOptions[0];

        $total = count($items);
        $lastPage = max(1, (int) ceil(max($total, 1) / $perPage));
        $page = max(1, (int) ($query[$pageParam] ?? 1));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        unset($query[$pageParam], $query[$perPageParam]);

        $buildUrl = function (int $targetPage, ?int $targetPerPage = null) use ($query, $pageParam, $perPageParam): string {
            $params = $query;
            $params[$pageParam] = $targetPage;
            $params[$perPageParam] = $targetPerPage ?? ($params[$perPageParam] ?? null);

            if (($params[$perPageParam] ?? null) === null) {
                unset($params[$perPageParam]);
            }

            $url = current_url();

            return $params === [] ? $url : $url . '?' . http_build_query($params);
        };

        $links = [];
        $startPage = max(1, $page - 2);
        $endPage = min($lastPage, $page + 2);

        for ($cursor = $startPage; $cursor <= $endPage; $cursor++) {
            $links[] = [
                'label' => (string) $cursor,
                'url' => $buildUrl($cursor, $perPage),
                'active' => $cursor === $page,
            ];
        }

        return [
            'items' => array_values(array_slice($items, $offset, $perPage)),
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'lastPage' => $lastPage,
                'start' => $total === 0 ? 0 : $offset + 1,
                'end' => min($offset + $perPage, $total),
                'pageParam' => $pageParam,
                'perPageParam' => $perPageParam,
                'query' => $query,
                'options' => $this->perPageOptions,
                'hasPrev' => $page > 1,
                'hasNext' => $page < $lastPage,
                'prevUrl' => $page > 1 ? $buildUrl($page - 1, $perPage) : null,
                'nextUrl' => $page < $lastPage ? $buildUrl($page + 1, $perPage) : null,
                'links' => $links,
            ],
        ];
    }
}
