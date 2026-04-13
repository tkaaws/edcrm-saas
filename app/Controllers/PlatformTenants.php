<?php

namespace App\Controllers;

use App\Models\TenantModel;

class PlatformTenants extends BaseController
{
    protected TenantModel $tenantModel;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
    }

    public function index(): string
    {
        $tenants = $this->tenantModel->orderBy('created_at', 'DESC')->findAll();

        return view('platform/tenants/index', $this->buildShellViewData([
            'title'       => 'Tenants',
            'pageTitle'   => 'Platform Tenants',
            'activeNav'   => 'tenants',
            'tenantLabel' => 'Platform scope',
            'branchLabel' => 'Multi-tenant',
            'roleLabel'   => 'Provisioning',
            'tenants'     => $tenants,
        ]));
    }

    public function create(): string
    {
        return view('platform/tenants/create', $this->buildShellViewData([
            'title'       => 'Create Tenant',
            'pageTitle'   => 'Create Tenant',
            'activeNav'   => 'tenants',
            'tenantLabel' => 'Platform scope',
            'branchLabel' => 'Onboarding',
            'roleLabel'   => 'Provisioning',
        ]));
    }

    public function store()
    {
        $data = $this->collectPayload();

        if ($errors = $this->validateInput($data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $result = service('tenantProvisioning')->provision($data);

        return redirect()->to('/platform/tenants')
            ->with('message', 'Tenant created successfully. Owner login: ' . $result['owner_email']);
    }

    /**
     * @return array<string, string>
     */
    protected function collectPayload(): array
    {
        $name = trim((string) $this->request->getPost('name'));
        $slug = trim((string) $this->request->getPost('slug'));
        $ownerFullName = trim((string) $this->request->getPost('owner_name'));

        [$ownerFirstName, $ownerLastName] = $this->splitName($ownerFullName);

        return [
            'name'                     => $name,
            'slug'                     => $slug !== '' ? strtolower($slug) : $this->slugify($name),
            'status'                   => $this->request->getPost('status') === 'draft' ? 'draft' : 'active',
            'legal_name'               => trim((string) $this->request->getPost('legal_name')),
            'owner_name'               => $ownerFullName,
            'owner_first_name'         => $ownerFirstName,
            'owner_last_name'          => $ownerLastName,
            'owner_email'              => strtolower(trim((string) $this->request->getPost('owner_email'))),
            'owner_phone'              => trim((string) $this->request->getPost('owner_phone')),
            'owner_username'           => strtolower(trim((string) $this->request->getPost('owner_username'))),
            'owner_employee_code'      => trim((string) $this->request->getPost('owner_employee_code')),
            'owner_password'           => (string) $this->request->getPost('owner_password'),
            'default_timezone'         => trim((string) $this->request->getPost('default_timezone')),
            'default_currency_code'    => strtoupper(trim((string) $this->request->getPost('default_currency_code'))),
            'country_code'             => strtoupper(trim((string) $this->request->getPost('country_code'))),
            'locale_code'              => trim((string) $this->request->getPost('locale_code')),
            'branch_name'              => trim((string) $this->request->getPost('branch_name')),
            'branch_code'              => strtoupper(trim((string) $this->request->getPost('branch_code'))),
            'branch_type'              => trim((string) $this->request->getPost('branch_type')),
            'branch_city'              => trim((string) $this->request->getPost('branch_city')),
            'branch_state_code'        => trim((string) $this->request->getPost('branch_state_code')),
            'branch_address_line_1'    => trim((string) $this->request->getPost('branch_address_line_1')),
            'branch_address_line_2'    => trim((string) $this->request->getPost('branch_address_line_2')),
            'branch_postal_code'       => trim((string) $this->request->getPost('branch_postal_code')),
            'branch_timezone'          => trim((string) $this->request->getPost('branch_timezone')),
            'branch_currency_code'     => strtoupper(trim((string) $this->request->getPost('branch_currency_code'))),
            'branding_name'            => trim((string) $this->request->getPost('branding_name')),
            'branch_visibility_mode'   => (string) $this->request->getPost('branch_visibility_mode'),
            'enquiry_visibility_mode'  => (string) $this->request->getPost('enquiry_visibility_mode'),
            'admission_visibility_mode'=> (string) $this->request->getPost('admission_visibility_mode'),
        ];
    }

    /**
     * @param array<string, string> $data
     *
     * @return list<string>
     */
    protected function validateInput(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Institute name is required.';
        }

        if ($data['owner_name'] === '') {
            $errors[] = 'Owner name is required.';
        }

        if ($data['owner_email'] === '' || ! filter_var($data['owner_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid owner email is required.';
        }

        if ($data['owner_username'] === '') {
            $errors[] = 'Owner username is required.';
        }

        if ($data['owner_password'] === '' || strlen($data['owner_password']) < 8) {
            $errors[] = 'Owner password must be at least 8 characters.';
        }

        if ($data['slug'] === '') {
            $errors[] = 'Slug is required.';
        }

        if ($this->tenantModel->findBySlug($data['slug'])) {
            $errors[] = 'Slug already exists.';
        }

        if ($data['branch_name'] === '') {
            $errors[] = 'First branch name is required.';
        }

        if ($data['branch_code'] === '') {
            $errors[] = 'First branch code is required.';
        }

        return $errors;
    }

    /**
     * @return array{0:string,1:string}
     */
    protected function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [''];
        $first = array_shift($parts) ?: '';
        $last = trim(implode(' ', $parts));

        return [$first, $last];
    }

    protected function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }
}
