<?php

namespace App\Filters;

use App\Services\PermissionService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    protected PermissionService $permissionService;

    public function __construct()
    {
        $this->permissionService = service('permissions');
    }

    public function before(RequestInterface $request, $arguments = null): mixed
    {
        $rawArguments = is_array($arguments) ? $arguments : [];
        $codes = [];

        foreach ($rawArguments as $argument) {
            foreach (explode(',', (string) $argument) as $code) {
                $code = trim($code);
                if ($code !== '') {
                    $codes[] = $code;
                }
            }
        }

        if ($codes === [] || $this->permissionService->hasAny($codes)) {
            return null;
        }

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['error' => 'You do not have permission to access this action.']);
        }

        return redirect()->to('/dashboard')
            ->with('error', 'You do not have permission to access this area.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): mixed
    {
        return null;
    }
}
