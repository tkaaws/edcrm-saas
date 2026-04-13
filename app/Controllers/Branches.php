<?php

namespace App\Controllers;

use App\Models\BranchModel;

class Branches extends BaseController
{
    protected BranchModel $branchModel;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
    }

    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');

        return view('branches/index', $this->buildShellViewData([
            'title'     => 'Branches',
            'pageTitle' => 'Branches',
            'activeNav' => 'branches',
            'branches'  => $this->branchModel->getAdminGrid($tenantId),
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
        ]));
    }

    public function store()
    {
        $tenantId = (int) session()->get('tenant_id');
        $data     = $this->collectPayload();

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
            'address_line_2' => $data['address_line_2'],
            'postal_code'    => $data['postal_code'],
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
            'address_line_2' => $data['address_line_2'],
            'postal_code'    => $data['postal_code'],
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

        $this->branchModel->updateWithActor($id, [
            'status' => $branch->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->to('/branches')->with('message', 'Branch status updated.');
    }

    /**
     * @return array<string, string>
     */
    protected function collectPayload(): array
    {
        return [
            'name'           => trim((string) $this->request->getPost('name')),
            'code'           => strtoupper(trim((string) $this->request->getPost('code'))),
            'type'           => trim((string) $this->request->getPost('type')),
            'country_code'   => strtoupper(trim((string) $this->request->getPost('country_code'))),
            'state_code'     => trim((string) $this->request->getPost('state_code')),
            'city'           => trim((string) $this->request->getPost('city')),
            'address_line_1' => trim((string) $this->request->getPost('address_line_1')),
            'address_line_2' => trim((string) $this->request->getPost('address_line_2')),
            'postal_code'    => trim((string) $this->request->getPost('postal_code')),
            'timezone'       => trim((string) $this->request->getPost('timezone')),
            'currency_code'  => strtoupper(trim((string) $this->request->getPost('currency_code'))),
            'status'         => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ];
    }

    /**
     * @return list<string>
     */
    protected function validateBranchInput(array $data, int $tenantId, ?int $branchId = null): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Branch name is required.';
        }

        if ($data['code'] === '') {
            $errors[] = 'Branch code is required.';
        }

        if ($this->branchModel->codeExistsForTenant($data['code'], $tenantId, $branchId)) {
            $errors[] = 'Branch code already exists for this tenant.';
        }

        if ($data['country_code'] !== '' && strlen($data['country_code']) !== 2) {
            $errors[] = 'Country code must be 2 characters.';
        }

        if ($data['currency_code'] !== '' && strlen($data['currency_code']) !== 3) {
            $errors[] = 'Currency code must be 3 characters.';
        }

        return $errors;
    }
}
