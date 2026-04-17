<?php

namespace App\Controllers;

use App\Models\BranchModel;
use App\Models\TenantModel;
use App\Support\RegionalOptions;

class Branches extends BaseController
{
    protected BranchModel $branchModel;
    protected TenantModel $tenantModel;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
        $this->tenantModel = new TenantModel();
    }

    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $branches = $this->branchModel->getAdminGrid($tenantId);
        $editableBranchesById = [];

        foreach ($branches as $branch) {
            $editableBranchesById[(int) $branch->id] = $this->branchModel->findForTenant((int) $branch->id) ?: $branch;
        }

        return view('branches/index', $this->buildShellViewData([
            'title'     => 'Branches',
            'pageTitle' => 'Branches',
            'activeNav' => 'branches',
            'branches'  => $branches,
            'editableBranchesById' => $editableBranchesById,
            'regionalInputOptions' => $this->regionalInputOptions(),
        ]));
    }

    public function create(): string
    {
        return view('branches/form', $this->buildShellViewData([
            'title'      => 'Create Branch',
            'pageTitle'  => 'Create Branch',
            'activeNav'  => 'branches',
            'formAction' => site_url('branches'),
            'submitText' => 'Create branch',
            'branch'     => null,
            'regionalInputOptions' => $this->regionalInputOptions(),
        ]));
    }

    public function store()
    {
        $tenantId = (int) session()->get('tenant_id');
        $data     = $this->collectPayload();

        if (service('usageLimit')->wouldExceedLimit($tenantId, 'max_branches')) {
            return redirect()->back()->withInput()->with('error', 'Branch limit reached for the current plan. Upgrade the subscription to add more branches.');
        }

        if ($errors = $this->validateBranchInput($data, $tenantId)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->branchModel->insertWithActor([
            'tenant_id'      => $tenantId,
            'name'           => $data['name'],
            'code'           => $data['code'],
            'type'           => $data['type'],
            'country_code'   => $data['country_code'],
            'state_code'     => $data['state_code'],
            'city'           => $data['city'],
            'address_line_1' => $data['address_line_1'],
            'timezone'       => $data['timezone'],
            'currency_code'  => $data['currency_code'],
            'status'         => $data['status'],
        ]);

        return redirect()->to('/branches')->with('message', 'Branch created successfully.');
    }

    public function edit(int $id): string
    {
        $branch = $this->branchModel->findForTenant($id);
        if (! $branch) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('branches/form', $this->buildShellViewData([
            'title'      => 'Edit Branch',
            'pageTitle'  => 'Edit Branch',
            'activeNav'  => 'branches',
            'formAction' => site_url('branches/' . $id),
            'submitText' => 'Save changes',
            'branch'     => $branch,
            'regionalInputOptions' => $this->regionalInputOptions(),
        ]));
    }

    public function update(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $branch   = $this->branchModel->findForTenant($id);

        if (! $branch) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = $this->collectPayload();

        if ($errors = $this->validateBranchInput($data, $tenantId, $id)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->branchModel->updateWithActor($id, [
            'name'           => $data['name'],
            'code'           => $data['code'],
            'type'           => $data['type'],
            'country_code'   => $data['country_code'],
            'state_code'     => $data['state_code'],
            'city'           => $data['city'],
            'address_line_1' => $data['address_line_1'],
            'timezone'       => $data['timezone'],
            'currency_code'  => $data['currency_code'],
            'status'         => $data['status'],
        ]);

        return redirect()->to('/branches')->with('message', 'Branch updated successfully.');
    }

    public function updateStatus(int $id)
    {
        $branch = $this->branchModel->findForTenant($id);
        if (! $branch) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($branch->status === 'active' && $this->branchModel->countAssignedUsers($id) > 0) {
            return redirect()->to('/branches')->with('error', 'Reassign branch users before deactivating this branch.');
        }

        $this->branchModel->updateWithActor($id, [
            'status' => $branch->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->to('/branches')->with('message', 'Branch status updated.');
    }

    protected function collectPayload(): array
    {
        $tenantDefaults = $this->getTenantDefaults();

        return [
            'name'           => trim((string) $this->request->getPost('name')),
            'code'           => strtoupper(trim((string) $this->request->getPost('code'))),
            'type'           => trim((string) $this->request->getPost('type')),
            'country_code'   => strtoupper(trim((string) $this->request->getPost('country_code'))) ?: ($tenantDefaults->country_code ?? ''),
            'state_code'     => strtoupper(trim((string) $this->request->getPost('state_code'))),
            'city'           => trim((string) $this->request->getPost('city')),
            'address_line_1' => trim((string) $this->request->getPost('address_line_1')),
            'timezone'       => trim((string) $this->request->getPost('timezone')) ?: ($tenantDefaults->default_timezone ?? ''),
            'currency_code'  => strtoupper(trim((string) $this->request->getPost('currency_code'))) ?: ($tenantDefaults->default_currency_code ?? ''),
            'status'         => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ];
    }

    protected function validateBranchInput(array $data, int $tenantId, ?int $branchId = null): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Branch name is required.';
        }

        if ($data['code'] === '') {
            $errors[] = 'Branch code is required.';
        }

        if (! preg_match('/^[A-Z0-9_-]+$/', $data['code'])) {
            $errors[] = 'Branch code may contain uppercase letters, numbers, underscores, and hyphens only.';
        }

        if ($this->branchModel->codeExistsForTenant($data['code'], $tenantId, $branchId)) {
            $errors[] = 'Branch code already exists for this tenant.';
        }

        if ($data['country_code'] !== '' && strlen($data['country_code']) !== 2) {
            $errors[] = 'Country code must be 2 characters.';
        }

        if ($data['country_code'] !== '' && ! ctype_alpha($data['country_code'])) {
            $errors[] = 'Country code must contain letters only.';
        }

        if ($data['state_code'] !== '' && ! preg_match('/^[A-Z0-9-]+$/', $data['state_code'])) {
            $errors[] = 'State code may contain uppercase letters, numbers, and hyphens only.';
        }

        if ($data['currency_code'] !== '' && strlen($data['currency_code']) !== 3) {
            $errors[] = 'Currency code must be 3 characters.';
        }

        if ($data['currency_code'] !== '' && ! ctype_alpha($data['currency_code'])) {
            $errors[] = 'Currency code must contain letters only.';
        }

        if ($data['timezone'] !== '' && ! in_array($data['timezone'], timezone_identifiers_list(), true)) {
            $errors[] = 'Timezone must be a valid PHP timezone identifier.';
        }

        return $errors;
    }

    protected function getTenantDefaults(): ?object
    {
        $tenantId = (int) session()->get('tenant_id');

        if ($tenantId < 1) {
            return null;
        }

        return $this->tenantModel->find($tenantId);
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    protected function regionalInputOptions(): array
    {
        return [
            'timezones' => RegionalOptions::timezones(),
            'currencies' => RegionalOptions::currencies(),
            'countries' => RegionalOptions::countries(),
        ];
    }
}
