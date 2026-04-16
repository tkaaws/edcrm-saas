<?php

namespace App\Controllers;

use App\Models\CollegeModel;

class Colleges extends BaseController
{
    protected CollegeModel $collegeModel;

    public function __construct()
    {
        $this->collegeModel = new CollegeModel();
    }

    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        service('collegeService')->ensureDefaultCollegeExists($tenantId);

        return view('colleges/index', $this->buildShellViewData([
            'title'     => 'Colleges',
            'pageTitle' => 'Colleges',
            'activeNav' => 'colleges',
            'colleges'  => $this->collegeModel->getAdminGrid($tenantId),
        ]));
    }

    public function create(): string
    {
        return view('colleges/form', $this->buildShellViewData([
            'title'      => 'Add College',
            'pageTitle'  => 'Add College',
            'activeNav'  => 'colleges',
            'formAction' => site_url('colleges'),
            'submitText' => 'Create college',
            'college'    => null,
        ]));
    }

    public function store()
    {
        $tenantId = (int) session()->get('tenant_id');
        $data = $this->collectPayload();

        if ($errors = $this->validateInput($data, $tenantId)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->collegeModel->insertWithActor([
            'tenant_id'   => $tenantId,
            'name'        => $data['name'],
            'city_name'   => $data['city_name'],
            'state_name'  => $data['state_name'],
            'status'      => $data['status'],
        ]);

        return redirect()->to('/colleges')->with('message', 'College created successfully.');
    }

    public function edit(int $id): string
    {
        $college = $this->collegeModel->findForTenant($id);
        if (! $college) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('colleges/form', $this->buildShellViewData([
            'title'      => 'Edit College',
            'pageTitle'  => 'Edit College',
            'activeNav'  => 'colleges',
            'formAction' => site_url('colleges/' . $id),
            'submitText' => 'Save changes',
            'college'    => $college,
        ]));
    }

    public function update(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $college = $this->collegeModel->findForTenant($id);

        if (! $college) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = $this->collectPayload();
        if ($errors = $this->validateInput($data, $tenantId, $id)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->collegeModel->updateWithActor($id, [
            'name'        => $data['name'],
            'city_name'   => $data['city_name'],
            'state_name'  => $data['state_name'],
            'status'      => $data['status'],
        ]);

        return redirect()->to('/colleges')->with('message', 'College updated successfully.');
    }

    public function delete(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $college = $this->collegeModel->findForTenant($id);

        if (! $college) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $usageCount = db_connect()->table('enquiries')
            ->where('tenant_id', $tenantId)
            ->where('college_id', $id)
            ->countAllResults();

        if ($usageCount > 0) {
            return redirect()->to('/colleges')->with('error', 'This college is already used in enquiries and cannot be deleted.');
        }

        $this->collegeModel->delete($id);
        service('collegeService')->ensureDefaultCollegeExists($tenantId);

        return redirect()->to('/colleges')->with('message', 'College removed.');
    }

    public function options()
    {
        $tenantId = (int) session()->get('tenant_id');
        $search = trim((string) $this->request->getGet('q'));

        return $this->response->setJSON([
            'items' => service('collegeService')->searchOptions($tenantId, $search),
        ]);
    }

    protected function collectPayload(): array
    {
        return [
            'name'       => trim((string) $this->request->getPost('name')),
            'city_name'  => trim((string) $this->request->getPost('city_name')),
            'state_name' => trim((string) $this->request->getPost('state_name')),
            'status'     => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ];
    }

    protected function validateInput(array $data, int $tenantId, ?int $collegeId = null): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'College name is required.';
        }

        if ($data['state_name'] === '') {
            $errors[] = 'State is required.';
        }

        if ($data['city_name'] === '') {
            $errors[] = 'City is required.';
        }

        if ($data['name'] !== '' && $this->collegeModel->nameExistsForTenant($data['name'], $tenantId, $collegeId)) {
            $errors[] = 'A college with this name already exists for your company.';
        }

        return $errors;
    }
}
