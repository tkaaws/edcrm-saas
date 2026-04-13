<?php

namespace App\Controllers;

use App\Models\BranchModel;
use App\Models\RoleModel;
use App\Models\UserModel;

class Users extends BaseController
{
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected BranchModel $branchModel;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->roleModel   = new RoleModel();
        $this->branchModel = new BranchModel();
    }

    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $users    = $this->userModel->getAdminGrid($tenantId);

        return view('users/index', $this->buildShellViewData([
            'title'      => 'Users',
            'pageTitle'  => 'Users',
            'activeNav'  => 'users',
            'users'      => $users,
        ]));
    }

    public function create(): string
    {
        return view('users/form', $this->buildFormViewData([
            'title'      => 'Create User',
            'pageTitle'  => 'Create User',
            'formAction' => site_url('users'),
            'submitText' => 'Create user',
            'user'       => null,
            'userBranchIds' => [],
            'primaryBranchId' => null,
        ]));
    }

    public function store()
    {
        $tenantId = (int) session()->get('tenant_id');
        $data     = $this->collectPayload();

        if ($errors = $this->validateUserInput($data, $tenantId)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $userId = $this->userModel->insertWithActor([
            'tenant_id'           => $tenantId,
            'role_id'             => (int) $data['role_id'],
            'employee_code'       => $data['employee_code'],
            'username'            => $data['username'],
            'email'               => $data['email'],
            'first_name'          => $data['first_name'],
            'last_name'           => $data['last_name'],
            'mobile_number'       => $data['mobile_number'],
            'whatsapp_number'     => $data['whatsapp_number'],
            'department'          => $data['department'],
            'designation'         => $data['designation'],
            'password_hash'       => password_hash($data['password'], PASSWORD_BCRYPT),
            'is_active'           => (int) $data['is_active'],
            'must_reset_password' => (int) $data['must_reset_password'],
        ]);

        $this->syncUserBranches((int) $userId, $data['branch_ids'], (int) $data['primary_branch_id']);

        return redirect()->to('/users')->with('message', 'User created successfully.');
    }

    public function edit(int $id): string
    {
        $user = $this->userModel->findForTenant($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $branches = $this->userModel->getBranches($id);
        $userBranchIds = array_map(static fn(array $branch) => (int) $branch['id'], $branches);
        $primaryBranch = array_values(array_filter($branches, static fn(array $branch) => (int) $branch['is_primary'] === 1));

        return view('users/form', $this->buildFormViewData([
            'title'            => 'Edit User',
            'pageTitle'        => 'Edit User',
            'formAction'       => site_url('users/' . $id),
            'submitText'       => 'Save changes',
            'user'             => $user,
            'userBranchIds'    => $userBranchIds,
            'primaryBranchId'  => $primaryBranch[0]['id'] ?? null,
        ]));
    }

    public function update(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $user     = $this->userModel->findForTenant($id);

        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = $this->collectPayload();

        if ($errors = $this->validateUserInput($data, $tenantId, $id, false)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $updateData = [
            'role_id'             => (int) $data['role_id'],
            'employee_code'       => $data['employee_code'],
            'username'            => $data['username'],
            'email'               => $data['email'],
            'first_name'          => $data['first_name'],
            'last_name'           => $data['last_name'],
            'mobile_number'       => $data['mobile_number'],
            'whatsapp_number'     => $data['whatsapp_number'],
            'department'          => $data['department'],
            'designation'         => $data['designation'],
            'is_active'           => (int) $data['is_active'],
            'must_reset_password' => (int) $data['must_reset_password'],
        ];

        if ($data['password'] !== '') {
            $updateData['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $this->userModel->updateWithActor($id, $updateData);
        $this->syncUserBranches($id, $data['branch_ids'], (int) $data['primary_branch_id']);

        return redirect()->to('/users')->with('message', 'User updated successfully.');
    }

    public function updateStatus(int $id)
    {
        $user = $this->userModel->findForTenant($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->userModel->updateWithActor($id, [
            'is_active' => $user->is_active ? 0 : 1,
        ]);

        return redirect()->to('/users')->with('message', 'User status updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectPayload(): array
    {
        $branchIds = array_map('intval', (array) $this->request->getPost('branch_ids'));

        return [
            'employee_code'       => trim((string) $this->request->getPost('employee_code')),
            'username'            => trim((string) $this->request->getPost('username')),
            'email'               => strtolower(trim((string) $this->request->getPost('email'))),
            'first_name'          => trim((string) $this->request->getPost('first_name')),
            'last_name'           => trim((string) $this->request->getPost('last_name')),
            'mobile_number'       => trim((string) $this->request->getPost('mobile_number')),
            'whatsapp_number'     => trim((string) $this->request->getPost('whatsapp_number')),
            'department'          => trim((string) $this->request->getPost('department')),
            'designation'         => trim((string) $this->request->getPost('designation')),
            'password'            => (string) $this->request->getPost('password'),
            'role_id'             => (int) $this->request->getPost('role_id'),
            'branch_ids'          => array_values(array_unique(array_filter($branchIds))),
            'primary_branch_id'   => (int) $this->request->getPost('primary_branch_id'),
            'is_active'           => $this->request->getPost('is_active') ? 1 : 0,
            'must_reset_password' => $this->request->getPost('must_reset_password') ? 1 : 0,
        ];
    }

    /**
     * @return list<string>
     */
    protected function validateUserInput(array $data, int $tenantId, ?int $userId = null, bool $requirePassword = true): array
    {
        $errors = [];

        if ($data['first_name'] === '') {
            $errors[] = 'First name is required.';
        }

        if ($data['username'] === '') {
            $errors[] = 'Username is required.';
        }

        if ($data['email'] === '' || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        if ($data['role_id'] < 1) {
            $errors[] = 'Please choose a role.';
        }

        if ($requirePassword && $data['password'] === '') {
            $errors[] = 'Password is required.';
        }

        if ($data['password'] !== '' && strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($data['primary_branch_id'] < 1 || ! in_array($data['primary_branch_id'], $data['branch_ids'], true)) {
            $errors[] = 'Choose at least one branch and select a matching primary branch.';
        }

        if ($this->userModel->emailExistsForTenant($data['email'], $tenantId, $userId)) {
            $errors[] = 'Email already exists for this tenant.';
        }

        if ($this->userModel->usernameExistsForTenant($data['username'], $tenantId, $userId)) {
            $errors[] = 'Username already exists for this tenant.';
        }

        return $errors;
    }

    /**
     * @param list<int> $branchIds
     */
    protected function syncUserBranches(int $userId, array $branchIds, int $primaryBranchId): void
    {
        $existingBranches = $this->userModel->getBranches($userId);
        $existingBranchIds = array_map(static fn(array $branch) => (int) $branch['id'], $existingBranches);

        foreach ($existingBranchIds as $existingBranchId) {
            if (! in_array($existingBranchId, $branchIds, true)) {
                $this->userModel->removeFromBranch($userId, $existingBranchId);
            }
        }

        foreach ($branchIds as $branchId) {
            $this->userModel->assignToBranch($userId, $branchId, $branchId === $primaryBranchId);
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function buildFormViewData(array $data): array
    {
        return $this->buildShellViewData(array_merge([
            'activeNav' => 'users',
            'roles'     => $this->roleModel->getActiveRoles(),
            'branches'  => $this->branchModel->getActiveBranches(),
        ], $data));
    }
}
