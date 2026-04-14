<?php

namespace App\Controllers;

use App\Models\PlanModel;
use App\Models\TenantModel;
use App\Models\UserModel;

class PlatformTenants extends BaseController
{
    protected TenantModel $tenantModel;
    protected UserModel $userModel;
    protected PlanModel $planModel;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
        $this->userModel   = new UserModel();
        $this->planModel   = new PlanModel();
    }

    public function index(): string
    {
        $tenants = $this->tenantModel->orderBy('created_at', 'DESC')->findAll();
        $subscriptionMap = $this->getCurrentSubscriptionMap(array_map(static fn($tenant) => (int) $tenant->id, $tenants));

        foreach ($tenants as $tenant) {
            $current = $subscriptionMap[(int) $tenant->id] ?? null;
            $tenant->current_plan_id           = $current->plan_id ?? null;
            $tenant->current_plan_name          = $current->plan_name ?? null;
            $tenant->current_plan_code          = $current->plan_code ?? null;
            $tenant->current_subscription_id    = $current->id ?? null;
            $tenant->current_subscription_status = $current->status ?? null;
            $tenant->current_billing_cycle      = $current->billing_cycle ?? null;
        }

        return view('platform/tenants/index', $this->buildShellViewData([
            'title'       => 'Tenants',
            'pageTitle'   => 'Platform Tenants',
            'activeNav'   => 'tenants',
            'tenantLabel' => 'Platform scope',
            'branchLabel' => 'Multi-tenant',
            'roleLabel'   => 'Provisioning',
            'tenants'     => $tenants,
            'plans'       => $this->planModel->getAllActivePlans(),
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
        $fieldErrors = $this->validateInput($data);

        if (! empty($fieldErrors)) {
            return redirect()->back()->withInput()->with('fieldErrors', $fieldErrors);
        }

        $result = service('tenantProvisioning')->provision($data);

        return redirect()->to('/platform/tenants')
            ->with('message', 'Tenant created successfully. Owner login: ' . $result['owner_email']);
    }

    public function show(int $id): string
    {
        $tenant = $this->tenantModel->find($id);

        if (! $tenant) {
            return redirect()->to('/platform/tenants')->with('error', 'Tenant not found.');
        }

        $db = db_connect();
        $subscription = $this->getCurrentSubscriptionForTenant($id);
        $userCount    = $db->table('users')->where('tenant_id', $id)->countAllResults();
        $branchCount  = $db->table('tenant_branches')->where('tenant_id', $id)->countAllResults();

        return view('platform/tenants/show', $this->buildShellViewData([
            'title'       => 'Tenant - ' . esc($tenant->name),
            'pageTitle'   => esc($tenant->name),
            'activeNav'   => 'tenants',
            'tenantLabel' => 'Platform scope',
            'branchLabel' => 'Multi-tenant',
            'roleLabel'   => 'Provisioning',
            'tenant'      => $tenant,
            'subscription'=> $subscription,
            'userCount'   => $userCount,
            'branchCount' => $branchCount,
            'plans'       => $this->planModel->getAllActivePlans(),
        ]));
    }

    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $tenant = $this->tenantModel->find($id);

        if (! $tenant) {
            return redirect()->to('/platform/tenants')->with('error', 'Tenant not found.');
        }

        return view('platform/tenants/edit', $this->buildShellViewData([
            'title'       => 'Edit — ' . esc($tenant->name),
            'pageTitle'   => 'Edit Tenant',
            'activeNav'   => 'tenants',
            'tenantLabel' => 'Platform scope',
            'tenant'      => $tenant,
        ]));
    }

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenant = $this->tenantModel->find($id);

        if (! $tenant) {
            return redirect()->to('/platform/tenants')->with('error', 'Tenant not found.');
        }

        $name          = trim((string) $this->request->getPost('name'));
        $legalName     = trim((string) $this->request->getPost('legal_name'));
        $ownerName     = trim((string) $this->request->getPost('owner_name'));
        $ownerEmail    = strtolower(trim((string) $this->request->getPost('owner_email')));
        $ownerPhone    = trim((string) $this->request->getPost('owner_phone'));
        $timezone      = trim((string) $this->request->getPost('default_timezone'));
        $currencyCode  = strtoupper(trim((string) $this->request->getPost('default_currency_code')));
        $countryCode   = strtoupper(trim((string) $this->request->getPost('country_code')));
        $localeCode    = trim((string) $this->request->getPost('locale_code'));

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Institute name is required.';
        }

        if ($ownerEmail !== '' && ! filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['owner_email'] = 'Owner email must be a valid email address.';
        } elseif ($ownerEmail !== '' && $ownerEmail !== $tenant->owner_email) {
            $existing = $this->tenantModel->where('owner_email', $ownerEmail)->where('id !=', $id)->countAllResults();
            if ($existing > 0) {
                $errors['owner_email'] = 'This email is already registered to another tenant.';
            }
        }

        if (! empty($errors)) {
            return redirect()->back()->withInput()->with('fieldErrors', $errors);
        }

        $this->tenantModel->update($id, [
            'name'                  => $name,
            'legal_name'            => $legalName,
            'owner_name'            => $ownerName,
            'owner_email'           => $ownerEmail,
            'owner_phone'           => $ownerPhone,
            'default_timezone'      => $timezone,
            'default_currency_code' => $currencyCode,
            'country_code'          => $countryCode,
            'locale_code'           => $localeCode,
        ]);

        return redirect()->to('/platform/tenants/' . $id)
                         ->with('message', 'Tenant profile updated.');
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenant = $this->tenantModel->find($id);

        if (! $tenant) {
            return redirect()->to('/platform/tenants')->with('error', 'Tenant not found.');
        }

        // Safety: block delete if tenant has any active subscriptions
        $activeSub = db_connect()->table('subscriptions')
                                 ->whereIn('status', ['trial', 'active', 'grace'])
                                 ->where('tenant_id', $id)
                                 ->countAllResults();

        if ($activeSub > 0) {
            return redirect()->to('/platform/tenants/' . $id)
                             ->with('error', 'Cannot delete — tenant has an active subscription. Cancel the subscription first.');
        }

        // Hard delete — FK cascades handle branches, users, roles, settings
        $this->tenantModel->delete($id);

        return redirect()->to('/platform/tenants')
                         ->with('message', 'Tenant "' . $tenant->name . '" has been permanently deleted.');
    }

    public function updateStatus(int $id)
    {
        $tenant = $this->tenantModel->find($id);

        if (! $tenant) {
            return redirect()->to('/platform/tenants')->with('error', 'Tenant not found.');
        }

        $status = $this->request->getPost('status');
        $allowed = ['active', 'suspended', 'cancelled', 'draft'];

        if (! in_array($status, $allowed, true)) {
            return redirect()->back()->with('error', 'Invalid status value.');
        }

        $this->tenantModel->update($id, ['status' => $status]);

        return redirect()->to('/platform/tenants/' . $id)
            ->with('message', 'Tenant status updated to ' . $status . '.');
    }

    public function updatePlan(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenant = $this->tenantModel->find($id);

        if (! $tenant) {
            return redirect()->to('/platform/tenants')->with('error', 'Tenant not found.');
        }

        $planId         = (int) $this->request->getPost('plan_id');
        $billingCycle   = (string) $this->request->getPost('billing_cycle');
        $activationMode = (string) $this->request->getPost('activation_mode');
        $trialDays      = (int) ($this->request->getPost('trial_days') ?: 14);

        $plan = $this->planModel->where('status', 'active')->find($planId);
        if (! $plan) {
            return redirect()->to('/platform/tenants/' . $id)->with('error', 'Select a valid active plan.');
        }

        service('subscriptionPolicy')->replaceSubscription(
            tenantId: $id,
            planId: $planId,
            billingCycle: $billingCycle,
            activationMode: $activationMode,
            trialDays: $trialDays,
            performedBy: (int) session()->get('user_id'),
            summary: 'Plan changed by platform admin from tenant workspace'
        );

        service('featureGate')->flushCache($id);

        return redirect()->to('/platform/tenants/' . $id)
                         ->with('message', 'Tenant plan updated to ' . $plan->name . '.');
    }

    /**
     * @param list<int> $tenantIds
     * @return array<int, object>
     */
    protected function getCurrentSubscriptionMap(array $tenantIds): array
    {
        if ($tenantIds === []) {
            return [];
        }

        $rows = db_connect()->query("
            SELECT s.*, p.name AS plan_name, p.code AS plan_code
            FROM subscriptions s
            INNER JOIN (
                SELECT tenant_id, MAX(id) AS max_id
                FROM subscriptions
                WHERE status NOT IN ('cancelled', 'expired')
                GROUP BY tenant_id
            ) latest ON latest.max_id = s.id
            INNER JOIN plans p ON p.id = s.plan_id
        ")->getResult();

        $map = [];
        foreach ($rows as $row) {
            if (in_array((int) $row->tenant_id, $tenantIds, true)) {
                $map[(int) $row->tenant_id] = $row;
            }
        }

        return $map;
    }

    protected function getCurrentSubscriptionForTenant(int $tenantId): ?object
    {
        return db_connect()->query("
            SELECT s.*, p.name AS plan_name, p.code AS plan_code
            FROM subscriptions s
            INNER JOIN plans p ON p.id = s.plan_id
            WHERE s.tenant_id = ?
              AND s.status NOT IN ('cancelled', 'expired')
            ORDER BY s.id DESC
            LIMIT 1
        ", [$tenantId])->getRow();
    }

    protected function collectPayload(): array
    {
        $name = trim((string) $this->request->getPost('name'));
        $slug = trim((string) $this->request->getPost('slug'));
        $ownerFullName = trim((string) $this->request->getPost('owner_name'));

        [$ownerFirstName, $ownerLastName] = $this->splitName($ownerFullName);

        return [
            'name'                      => $name,
            'slug'                      => $slug !== '' ? strtolower($slug) : $this->slugify($name),
            'status'                    => $this->request->getPost('status') === 'draft' ? 'draft' : 'active',
            'legal_name'                => trim((string) $this->request->getPost('legal_name')),
            'owner_name'                => $ownerFullName,
            'owner_first_name'          => $ownerFirstName,
            'owner_last_name'           => $ownerLastName,
            'owner_email'               => strtolower(trim((string) $this->request->getPost('owner_email'))),
            'owner_phone'               => trim((string) $this->request->getPost('owner_phone')),
            'owner_username'            => strtolower(trim((string) $this->request->getPost('owner_username'))),
            'owner_employee_code'       => trim((string) $this->request->getPost('owner_employee_code')),
            'owner_password'            => (string) $this->request->getPost('owner_password'),
            'default_timezone'          => trim((string) $this->request->getPost('default_timezone')),
            'default_currency_code'     => strtoupper(trim((string) $this->request->getPost('default_currency_code'))),
            'country_code'              => strtoupper(trim((string) $this->request->getPost('country_code'))),
            'locale_code'               => trim((string) $this->request->getPost('locale_code')),
            'branch_name'               => trim((string) $this->request->getPost('branch_name')),
            'branch_code'               => strtoupper(trim((string) $this->request->getPost('branch_code'))),
            'branch_type'               => trim((string) $this->request->getPost('branch_type')),
            'branch_city'               => trim((string) $this->request->getPost('branch_city')),
            'branch_state_code'         => strtoupper(trim((string) $this->request->getPost('branch_state_code'))),
            'branch_address_line_1'     => trim((string) $this->request->getPost('branch_address_line_1')),
            'branch_address_line_2'     => trim((string) $this->request->getPost('branch_address_line_2')),
            'branch_postal_code'        => trim((string) $this->request->getPost('branch_postal_code')),
            'branch_timezone'           => trim((string) $this->request->getPost('branch_timezone')),
            'branch_currency_code'      => strtoupper(trim((string) $this->request->getPost('branch_currency_code'))),
            'branding_name'             => trim((string) $this->request->getPost('branding_name')),
            'branch_visibility_mode'    => (string) $this->request->getPost('branch_visibility_mode'),
            'enquiry_visibility_mode'   => (string) $this->request->getPost('enquiry_visibility_mode'),
            'admission_visibility_mode' => (string) $this->request->getPost('admission_visibility_mode'),
            'owner_password_confirm'    => (string) $this->request->getPost('owner_password_confirm'),
        ];
    }

    protected function validateInput(array $data): array
    {
        $errors = [];
        $allowedModes = ['own', 'restricted', 'all'];

        if ($data['name'] === '') {
            $errors['name'] = 'Institute name is required.';
        }

        if ($data['slug'] === '') {
            $errors['slug'] = 'Slug could not be generated. Please enter a valid institute name or slug.';
        } elseif (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors['slug'] = 'Slug may contain lowercase letters, numbers, and hyphens only.';
        } elseif ($this->tenantModel->findBySlug($data['slug'])) {
            $errors['slug'] = 'This slug is already taken.';
        }

        if ($data['default_timezone'] !== '' && ! in_array($data['default_timezone'], timezone_identifiers_list(), true)) {
            $errors['default_timezone'] = 'Default timezone must be a valid PHP timezone identifier.';
        }

        if ($data['default_currency_code'] !== '' && (strlen($data['default_currency_code']) !== 3 || ! ctype_alpha($data['default_currency_code']))) {
            $errors['default_currency_code'] = 'Default currency code must be a 3-letter currency code.';
        }

        if ($data['country_code'] !== '' && (strlen($data['country_code']) !== 2 || ! ctype_alpha($data['country_code']))) {
            $errors['country_code'] = 'Country code must be a 2-letter country code.';
        }

        if ($data['owner_name'] === '') {
            $errors['owner_name'] = 'Owner name is required.';
        }

        if ($data['owner_email'] === '') {
            $errors['owner_email'] = 'Owner email is required.';
        } elseif (! filter_var($data['owner_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['owner_email'] = 'Owner email must be a valid email address.';
        } elseif ($this->tenantModel->ownerEmailExists($data['owner_email'])) {
            $errors['owner_email'] = 'This email is already registered as a tenant owner.';
        } elseif ($this->userModel->emailExistsPlatformWide($data['owner_email'])) {
            $errors['owner_email'] = 'This email is already in use by another user.';
        }

        if ($data['owner_username'] === '') {
            $errors['owner_username'] = 'Owner username is required.';
        } elseif (! preg_match('/^[a-z0-9._-]+$/', $data['owner_username'])) {
            $errors['owner_username'] = 'Owner username may contain lowercase letters, numbers, dots, underscores, and hyphens only.';
        } elseif ($this->userModel->usernameExistsPlatformWide($data['owner_username'])) {
            $errors['owner_username'] = 'This username is already taken.';
        }

        if ($data['owner_password'] === '') {
            $errors['owner_password'] = 'Owner password is required.';
        } elseif (strlen($data['owner_password']) < 8) {
            $errors['owner_password'] = 'Password must be at least 8 characters.';
        } elseif ($data['owner_password'] !== $data['owner_password_confirm']) {
            $errors['owner_password_confirm'] = 'Passwords do not match.';
        }

        if ($data['branch_name'] === '') {
            $errors['branch_name'] = 'First branch name is required.';
        }

        if ($data['branch_code'] === '') {
            $errors['branch_code'] = 'First branch code is required.';
        } elseif (! preg_match('/^[A-Z0-9_-]+$/', $data['branch_code'])) {
            $errors['branch_code'] = 'First branch code may contain uppercase letters, numbers, underscores, and hyphens only.';
        }

        if ($data['branch_timezone'] !== '' && ! in_array($data['branch_timezone'], timezone_identifiers_list(), true)) {
            $errors['branch_timezone'] = 'Branch timezone must be a valid PHP timezone identifier.';
        }

        if ($data['branch_currency_code'] !== '' && (strlen($data['branch_currency_code']) !== 3 || ! ctype_alpha($data['branch_currency_code']))) {
            $errors['branch_currency_code'] = 'Branch currency code must be a 3-letter currency code.';
        }

        if ($data['branch_state_code'] !== '' && ! preg_match('/^[A-Z0-9-]+$/', $data['branch_state_code'])) {
            $errors['branch_state_code'] = 'State code may contain uppercase letters, numbers, and hyphens only.';
        }

        foreach (['branch_visibility_mode', 'enquiry_visibility_mode', 'admission_visibility_mode'] as $modeField) {
            if (! in_array($data[$modeField], $allowedModes, true)) {
                $errors[$modeField] = 'Visibility mode must be own, restricted, or all.';
            }
        }

        return $errors;
    }

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
