<?php

namespace App\Controllers;

use App\Controllers\Concerns\PaginatesCollections;
use App\Models\PrivilegeModel;
use App\Models\RoleModel;
use App\Services\DelegationGuardService;

class Roles extends BaseController
{
    use PaginatesCollections;

    protected RoleModel $roleModel;
    protected PrivilegeModel $privilegeModel;
    protected DelegationGuardService $delegationGuard;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->privilegeModel = new PrivilegeModel();
        $this->delegationGuard = service('delegationGuard');
    }

    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $paginated = $this->paginateCollection($this->roleModel->getAdminGrid($tenantId));

        return view('roles/index', $this->buildShellViewData([
            'title'     => 'Roles',
            'pageTitle' => 'Roles',
            'activeNav' => 'roles',
            'roles'     => $paginated['items'],
            'pagination' => $paginated['pagination'],
        ]));
    }

    public function create(): string
    {
        return view('roles/form', $this->buildFormViewData([
            'title'                => 'Create Role',
            'pageTitle'            => 'Create Role',
            'activeNav'            => 'roles',
            'formAction'           => site_url('roles'),
            'submitText'           => 'Create role',
            'role'                 => null,
            'selectedPrivilegeIds' => [],
        ]));
    }

    public function store()
    {
        $tenantId = (int) session()->get('tenant_id');
        $data = $this->collectPayload();

        if ($errors = $this->validateRoleInput($data, $tenantId)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $roleId = $this->roleModel->insertWithActor([
            'tenant_id'  => $tenantId,
            'name'       => $data['name'],
            'code'       => $data['code'],
            'access_behavior' => 'branch',
            'is_system'  => 0,
            'status'     => 'active',
        ]);

        $this->roleModel->syncPrivileges((int) $roleId, $data['privilege_ids']);

        return redirect()->to('/roles')->with('message', 'Role created successfully.');
    }

    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $role = $this->roleModel->findForTenant($id);
        if (! $role) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tenantId = (int) session()->get('tenant_id');
        if (! $this->delegationGuard->canAssignRoleForTenant($tenantId, (int) $role->id)) {
            return redirect()->to('/roles')->with('error', 'You cannot manage a role outside your delegation scope.');
        }

        return view('roles/form', $this->buildFormViewData([
            'title'                => 'Edit Role',
            'pageTitle'            => 'Edit Role',
            'activeNav'            => 'roles',
            'formAction'           => site_url('roles/' . $id),
            'submitText'           => 'Save changes',
            'role'                 => $role,
            'selectedPrivilegeIds' => $this->privilegeModel->getPrivilegeIdsForRole($id),
        ]));
    }

    public function update(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $role = $this->roleModel->findForTenant($id);

        if (! $role) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->delegationGuard->canAssignRoleForTenant($tenantId, (int) $role->id)) {
            return redirect()->to('/roles')->with('error', 'You cannot manage a role outside your delegation scope.');
        }

        $data = $this->collectPayload();
        $data['code'] = (string) $role->code;

        if ($errors = $this->validateRoleInput($data, $tenantId, $id, (bool) $role->is_system)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $updateData = [
            'name'            => $data['name'],
        ];

        $this->roleModel->updateWithActor($id, $updateData);
        $this->roleModel->syncPrivileges($id, $data['privilege_ids']);

        return redirect()->to('/roles')->with('message', 'Role updated successfully.');
    }

    public function updateStatus(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $role = $this->roleModel->findForTenant($id);
        if (! $role) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->delegationGuard->canAssignRoleForTenant($tenantId, (int) $role->id)) {
            return redirect()->to('/roles')->with('error', 'You cannot manage a role outside your delegation scope.');
        }

        if ($role->code === 'tenant_owner' && $role->status === 'active') {
            return redirect()->to('/roles')->with('error', 'Tenant owner role must remain active.');
        }

        $this->roleModel->updateWithActor($id, [
            'status' => $role->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->to('/roles')->with('message', 'Role status updated.');
    }

    protected function collectPayload(): array
    {
        $privilegeIds = array_map('intval', (array) $this->request->getPost('privilege_ids'));

        return [
            'name'            => trim((string) $this->request->getPost('name')),
            'code'            => $this->buildRoleCode((string) $this->request->getPost('name')),
            'privilege_ids'   => array_values(array_unique(array_filter($privilegeIds))),
        ];
    }

    protected function validateRoleInput(array $data, int $tenantId, ?int $roleId = null, bool $isSystemRole = false): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Role name is required.';
        }

        if (! $isSystemRole && $data['code'] === '') {
            $errors[] = 'Role name must include letters or numbers so a valid internal code can be generated.';
        }

        if (! $isSystemRole && $data['code'] !== '' && ! preg_match('/^[a-z0-9_]+$/', $data['code'])) {
            $errors[] = 'Role code may contain lowercase letters, numbers, and underscores only.';
        }

        if (! $isSystemRole && $this->roleModel->codeExistsForTenant($data['code'], $tenantId, $roleId)) {
            $errors[] = 'Role code already exists for this tenant.';
        }

        if ($data['privilege_ids'] === []) {
            $errors[] = 'Select at least one privilege.';
        }

        if ($data['privilege_ids'] !== []
            && $this->privilegeModel->whereIn('id', $data['privilege_ids'])->countAllResults() !== count($data['privilege_ids'])) {
            $errors[] = 'One or more selected privileges are invalid.';
        }

        if ($data['privilege_ids'] !== []
            && $this->delegationGuard->getInvalidPrivilegeIdsForTenant($tenantId, $data['privilege_ids']) !== []) {
            $errors[] = 'Selected privileges must stay within the tenant plan and your own delegation scope.';
        }

        return $errors;
    }

    protected function buildFormViewData(array $data): array
    {
        $tenantId = (int) session()->get('tenant_id');

        return $this->buildShellViewData(array_merge([
            'activeNav'            => 'roles',
            'privilegeGroups'      => $this->delegationGuard->getGroupedAssignablePrivilegesForTenant($tenantId),
        ], $data));
    }

    protected function buildRoleCode(string $name): string
    {
        $code = strtolower(trim($name));
        $code = preg_replace('/[^a-z0-9]+/', '_', $code) ?? '';
        return trim($code, '_');
    }
}
