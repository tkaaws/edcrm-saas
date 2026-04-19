<?php

namespace App\Controllers;

use App\Controllers\Concerns\PaginatesCollections;
use App\Models\BranchModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\UserHierarchyModel;
use App\Services\DelegationGuardService;
use App\Services\UsageLimitService;
use App\Services\UserAccessScopeService;

class Users extends BaseController
{
    use PaginatesCollections;

    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected BranchModel $branchModel;
    protected UserHierarchyModel $userHierarchyModel;
    protected DelegationGuardService $delegationGuard;
    protected UsageLimitService $usageLimit;
    protected UserAccessScopeService $userAccessScope;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->roleModel   = new RoleModel();
        $this->branchModel = new BranchModel();
        $this->userHierarchyModel = new UserHierarchyModel();
        $this->delegationGuard = service('delegationGuard');
        $this->usageLimit = service('usageLimit');
        $this->userAccessScope = service('userAccessScope');
    }

    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $users    = array_values(array_filter(
            $this->userModel->getAdminGrid($tenantId),
            fn(object $user): bool => $this->userAccessScope->canViewTargetUser($user)
        ));

        foreach ($users as $user) {
            $user->can_manage_target = $this->userAccessScope->canManageTargetUser($user);
        }

        $paginated = $this->paginateCollection($users);

        return view('users/index', $this->buildShellViewData([
            'title'      => 'Users',
            'pageTitle'  => 'Users',
            'activeNav'  => 'users',
            'users'      => $paginated['items'],
            'pagination' => $paginated['pagination'],
        ]));
    }

    public function create(): string
    {
        return view('users/form', $this->buildFormViewData([
            'title'           => 'Create User',
            'pageTitle'       => 'Create User',
            'formAction'      => site_url('users'),
            'submitText'      => 'Create user',
            'user'            => null,
            'hierarchy'       => null,
            'userBranchIds'   => [],
            'primaryBranchId' => null,
        ]));
    }

    public function store()
    {
        $tenantId = (int) session()->get('tenant_id');
        $data     = $this->collectPayload();

        if ($this->usageLimit->wouldExceedLimit($tenantId, 'max_users')) {
            return redirect()->back()->withInput()->with('error', 'User limit reached for the current plan. Upgrade the subscription to add more users.');
        }

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
            'allow_impersonation' => 1,
            'password_hash'       => password_hash($data['password'], PASSWORD_BCRYPT),
            'is_active'           => 1,
            'must_reset_password' => 0,
        ]);

        $this->syncUserBranches((int) $userId, $data['branch_ids'], (int) $data['primary_branch_id']);
        $this->syncUserHierarchy($tenantId, (int) $userId, $data['manager_user_id']);

        return redirect()->to('/users')->with('message', 'User created successfully.');
    }

    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $user = $this->userModel->findForTenant($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tenantId = (int) session()->get('tenant_id');
        if (! $this->delegationGuard->canAssignRoleForTenant($tenantId, (int) $user->role_id)) {
            return redirect()->to('/users')->with('error', 'You cannot manage a user whose role is outside your delegation scope.');
        }

        if (! $this->userAccessScope->canManageTargetUser($user)) {
            return redirect()->to('/users')->with('error', 'You cannot manage a user outside your access scope.');
        }

        $hierarchy = $this->userHierarchyModel->findByUser($id);
        $branches = $this->userModel->getBranches($id);
        $userBranchIds = array_map(static fn(array $branch) => (int) $branch['id'], $branches);
        $primaryBranch = array_values(array_filter($branches, static fn(array $branch) => (int) $branch['is_primary'] === 1));

        return view('users/form', $this->buildFormViewData([
            'title'            => 'Edit User',
            'pageTitle'        => 'Edit User',
            'formAction'       => site_url('users/' . $id),
            'submitText'       => 'Save changes',
            'user'             => $user,
            'hierarchy'        => $hierarchy,
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

        if (! $this->delegationGuard->canAssignRoleForTenant($tenantId, (int) $user->role_id)) {
            return redirect()->to('/users')->with('error', 'You cannot manage a user whose role is outside your delegation scope.');
        }

        if (! $this->userAccessScope->canManageTargetUser($user)) {
            return redirect()->to('/users')->with('error', 'You cannot manage a user outside your access scope.');
        }

        $data = $this->collectPayload();

        if ($errors = $this->validateUserInput($data, $tenantId, $id, false)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        if ($errors = $this->validateUserStateTransition($user, $data, $tenantId)) {
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
            'is_active'           => $this->request->getPost('is_active') !== null
                ? (int) $data['is_active']
                : (int) ($user->is_active ?? 1),
            'must_reset_password' => $this->request->getPost('must_reset_password') !== null
                ? (int) $data['must_reset_password']
                : (int) ($user->must_reset_password ?? 0),
        ];

        if ($data['password'] !== '') {
            $updateData['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $this->userModel->updateWithActor($id, $updateData);

        if ($data['password'] !== '') {
            $refreshedUser = $this->userModel->withoutTenantScope()->find($id);
            if (! $refreshedUser || ! $this->userModel->verifyPassword($data['password'], (string) ($refreshedUser->password_hash ?? ''))) {
                return redirect()->back()->withInput()->with('error', 'Password could not be updated correctly. Please try again.');
            }
        }
        $this->syncUserBranches($id, $data['branch_ids'], (int) $data['primary_branch_id']);
        $this->syncUserHierarchy($tenantId, $id, $data['manager_user_id']);

        return redirect()->to('/users')->with('message', 'User updated successfully.');
    }

    public function updateStatus(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $user = $this->userModel->findForTenant($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ((int) $user->id === (int) session()->get('user_id')) {
            return redirect()->to('/users')->with('error', 'You cannot deactivate your own account.');
        }

        if (! $this->delegationGuard->canAssignRoleForTenant($tenantId, (int) $user->role_id)) {
            return redirect()->to('/users')->with('error', 'You cannot manage a user whose role is outside your delegation scope.');
        }

        if (! $this->userAccessScope->canManageTargetUser($user)) {
            return redirect()->to('/users')->with('error', 'You cannot manage a user outside your access scope.');
        }

        $role = $this->roleModel->findForTenant((int) $user->role_id);
        if ($user->is_active && $role?->code === 'tenant_owner'
            && $this->userModel->countActiveUsersByRole($tenantId, 'tenant_owner', (int) $user->id) === 0) {
            return redirect()->to('/users')->with('error', 'At least one active tenant owner must remain assigned to this institute.');
        }

        if (! $user->is_active && $this->usageLimit->wouldExceedLimit($tenantId, 'max_users')) {
            return redirect()->to('/users')->with('error', 'User limit reached for the current plan. Upgrade the subscription to reactivate this user.');
        }

        $this->userModel->updateWithActor($id, [
            'is_active' => $user->is_active ? 0 : 1,
        ]);

        return redirect()->to('/users')->with('message', 'User status updated.');
    }

    protected function collectPayload(): array
    {
        $branchIds = array_map('intval', (array) $this->request->getPost('branch_ids'));

        $email = strtolower(trim((string) $this->request->getPost('email')));

        return [
            'employee_code'       => trim((string) $this->request->getPost('employee_code')),
            'email'               => $email,
            'first_name'          => trim((string) $this->request->getPost('first_name')),
            'last_name'           => trim((string) $this->request->getPost('last_name')),
            'mobile_number'       => trim((string) $this->request->getPost('mobile_number')),
            'whatsapp_number'     => trim((string) $this->request->getPost('whatsapp_number')),
            'department'          => trim((string) $this->request->getPost('department')),
            'designation'         => trim((string) $this->request->getPost('designation')),
            'manager_user_id'     => (int) $this->request->getPost('manager_user_id'),
            'password'            => (string) $this->request->getPost('password'),
            'role_id'             => (int) $this->request->getPost('role_id'),
            'branch_ids'          => array_values(array_unique(array_filter($branchIds))),
            'primary_branch_id'   => (int) $this->request->getPost('primary_branch_id'),
            'is_active'           => $this->request->getPost('is_active') ? 1 : 0,
            'must_reset_password' => $this->request->getPost('must_reset_password') ? 1 : 0,
            'username'            => $email,
        ];
    }

    protected function validateUserInput(array $data, int $tenantId, ?int $userId = null, bool $requirePassword = true): array
    {
        $errors = [];

        if ($data['first_name'] === '') {
            $errors[] = 'First name is required.';
        }

        if ($data['email'] === '' || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        if ($data['role_id'] < 1) {
            $errors[] = 'Please choose a role.';
        }

        $role = $data['role_id'] > 0 ? $this->roleModel->findForTenant($data['role_id']) : null;
        if ($data['role_id'] > 0 && (! $role || $role->status !== 'active')) {
            $errors[] = 'Choose an active role from this tenant.';
        } elseif ($role && ! $this->delegationGuard->canAssignRoleForTenant($tenantId, (int) $role->id)) {
            $errors[] = 'You can only assign roles that stay within the tenant plan and your own delegation scope.';
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

        if (! $this->userAccessScope->canAssignBranches($data['branch_ids'])) {
            $errors[] = 'You can only assign branches inside your allowed management scope.';
        }

        $activeBranchIds = array_map(
            static fn(object $branch): int => (int) $branch->id,
            $this->branchModel->getActiveBranches()
        );

        foreach ($data['branch_ids'] as $branchId) {
            if (! in_array($branchId, $activeBranchIds, true)) {
                $errors[] = 'All assigned branches must be active branches from this tenant.';
                break;
            }
        }

        if ($this->userModel->emailExistsForTenant($data['email'], $tenantId, $userId)) {
            $errors[] = 'Email already exists for this tenant.';
        }

        if ($this->userModel->usernameExistsForTenant($data['email'], $tenantId, $userId)) {
            $errors[] = 'This email conflicts with an existing login identity in this tenant.';
        }

        if ($data['manager_user_id'] > 0) {
            $manager = $this->userModel->findForTenant($data['manager_user_id']);

            if (! $manager) {
                $errors[] = 'Reporting head must be an active user from this tenant.';
            } elseif ((int) ($manager->is_active ?? 0) !== 1) {
                $errors[] = 'Reporting head must be active.';
            } elseif ($userId !== null && $data['manager_user_id'] === $userId) {
                $errors[] = 'A user cannot report to themselves.';
            } elseif (! $this->userAccessScope->canAssignManager($data['manager_user_id'])) {
                $errors[] = 'Reporting head must be inside your allowed management scope.';
            }
        }

        return $errors;
    }

    protected function validateUserStateTransition(object $user, array $data, int $tenantId): array
    {
        $errors = [];
        $currentRole = $this->roleModel->findForTenant((int) $user->role_id);
        $nextRole = $this->roleModel->findForTenant((int) $data['role_id']);

        if ((int) $user->id === (int) session()->get('user_id') && (int) $data['is_active'] !== 1) {
            $errors[] = 'You cannot deactivate your own account.';
        }

        if ($currentRole?->code === 'tenant_owner') {
            $ownerCountExcludingCurrent = $this->userModel->countActiveUsersByRole($tenantId, 'tenant_owner', (int) $user->id);
            $losingOwnerCoverage = (int) $data['is_active'] !== 1 || $nextRole?->code !== 'tenant_owner';

            if ($losingOwnerCoverage && $ownerCountExcludingCurrent === 0) {
                $errors[] = 'At least one active tenant owner must remain assigned to this institute.';
            }
        }

        return $errors;
    }

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

    protected function syncUserHierarchy(int $tenantId, int $userId, int $managerUserId): void
    {
        $this->userHierarchyModel->upsertForUser(
            $tenantId,
            $userId,
            $managerUserId > 0 ? $managerUserId : null
        );
    }

    protected function buildFormViewData(array $data): array
    {
        $tenantId = (int) session()->get('tenant_id');
        $roles = $this->delegationGuard->getAssignableRolesForTenant($tenantId);
        $ignoreUserId = isset($data['user']->id) ? (int) $data['user']->id : null;
        $assignableBranches = $this->userAccessScope->filterAssignableBranches($this->branchModel->getActiveBranches());

        return $this->buildShellViewData(array_merge([
            'activeNav'         => 'users',
            'roles'             => $roles,
            'branches'          => $assignableBranches,
            'managerUsers'      => $this->userAccessScope->getAllowedManagerOptions($tenantId, $ignoreUserId),
            'canSubmit'         => $roles !== [],
        ], $data));
    }
}
