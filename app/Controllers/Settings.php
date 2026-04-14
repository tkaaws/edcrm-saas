<?php

namespace App\Controllers;

use App\Models\TenantEmailConfigModel;
use App\Models\TenantModel;
use App\Models\TenantSettingsModel;
use App\Models\TenantWhatsappConfigModel;
use Config\Encryption;

class Settings extends BaseController
{
    protected TenantModel $tenantModel;
    protected TenantSettingsModel $tenantSettingsModel;
    protected TenantEmailConfigModel $tenantEmailConfigModel;
    protected TenantWhatsappConfigModel $tenantWhatsappConfigModel;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
        $this->tenantSettingsModel = new TenantSettingsModel();
        $this->tenantEmailConfigModel = new TenantEmailConfigModel();
        $this->tenantWhatsappConfigModel = new TenantWhatsappConfigModel();
    }

    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $tenant = $this->tenantModel->find($tenantId);
        $settings = $this->tenantSettingsModel->findByTenant($tenantId);
        $emailConfig = $this->tenantEmailConfigModel->findDefaultForTenant($tenantId);
        $whatsappConfig = $this->tenantWhatsappConfigModel->findDefaultForTenant($tenantId);

        return view('settings/index', $this->buildShellViewData([
            'title'          => 'Settings',
            'pageTitle'      => 'Tenant Settings',
            'activeNav'      => 'settings',
            'tenant'         => $tenant,
            'settings'       => $settings,
            'emailConfig'    => $this->decorateEmailConfig($emailConfig),
            'whatsappConfig' => $this->decorateWhatsappConfig($whatsappConfig),
        ]));
    }

    public function updateProfile()
    {
        $tenantId = (int) session()->get('tenant_id');
        $tenant = $this->tenantModel->find($tenantId);

        if (! $tenant) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'name'                  => trim((string) $this->request->getPost('name')),
            'legal_name'            => trim((string) $this->request->getPost('legal_name')),
            'owner_name'            => trim((string) $this->request->getPost('owner_name')),
            'owner_email'           => strtolower(trim((string) $this->request->getPost('owner_email'))),
            'owner_phone'           => trim((string) $this->request->getPost('owner_phone')),
            'default_timezone'      => trim((string) $this->request->getPost('default_timezone')),
            'default_currency_code' => strtoupper(trim((string) $this->request->getPost('default_currency_code'))),
            'country_code'          => strtoupper(trim((string) $this->request->getPost('country_code'))),
            'locale_code'           => trim((string) $this->request->getPost('locale_code')),
        ];

        if ($errors = $this->validateProfileInput($data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->tenantModel->update($tenantId, $data);

        return redirect()->to('/settings')->with('message', 'Tenant profile updated successfully.');
    }

    public function updatePreferences()
    {
        $tenantId = (int) session()->get('tenant_id');
        $settings = $this->tenantSettingsModel->findByTenant($tenantId);

        $data = [
            'tenant_id'                 => $tenantId,
            'branding_name'             => trim((string) $this->request->getPost('branding_name')),
            'default_timezone'          => trim((string) $this->request->getPost('default_timezone')),
            'default_currency_code'     => strtoupper(trim((string) $this->request->getPost('default_currency_code'))),
            'locale_code'               => trim((string) $this->request->getPost('locale_code')),
            'branch_visibility_mode'    => (string) $this->request->getPost('branch_visibility_mode'),
            'enquiry_visibility_mode'   => (string) $this->request->getPost('enquiry_visibility_mode'),
            'admission_visibility_mode' => (string) $this->request->getPost('admission_visibility_mode'),
        ];

        if ($errors = $this->validatePreferencesInput($data)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        if ($settings) {
            $this->tenantSettingsModel->updateWithActor($settings->id, $data);
        } else {
            $this->tenantSettingsModel->insertWithActor($data);
        }

        return redirect()->to('/settings')->with('message', 'Tenant preferences updated successfully.');
    }

    public function updateEmailConfig()
    {
        $tenantId = (int) session()->get('tenant_id');
        $existing = $this->tenantEmailConfigModel->findDefaultForTenant($tenantId);

        $secret = trim((string) $this->request->getPost('password'));
        $payload = [
            'tenant_id'          => $tenantId,
            'provider_name'      => trim((string) $this->request->getPost('provider_name')),
            'from_name'          => trim((string) $this->request->getPost('from_name')),
            'from_email'         => strtolower(trim((string) $this->request->getPost('from_email'))),
            'host'               => trim((string) $this->request->getPost('host')),
            'port'               => (int) $this->request->getPost('port'),
            'username'           => trim((string) $this->request->getPost('username')),
            'password_encrypted' => $secret !== '' ? $this->encryptSecret($secret) : ($existing->password_encrypted ?? null),
            'encryption'         => strtolower(trim((string) $this->request->getPost('encryption'))),
            'is_default'         => 1,
            'status'             => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ];

        if ($errors = $this->validateEmailConfigInput($payload)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        if ($existing) {
            $this->tenantEmailConfigModel->updateWithActor($existing->id, $payload);
        } else {
            $this->tenantEmailConfigModel->insertWithActor($payload);
        }

        return redirect()->to('/settings')->with('message', 'SMTP configuration updated successfully.');
    }

    public function updateWhatsappConfig()
    {
        $tenantId = (int) session()->get('tenant_id');
        $existing = $this->tenantWhatsappConfigModel->findDefaultForTenant($tenantId);

        $secret = trim((string) $this->request->getPost('api_key'));
        $payload = [
            'tenant_id'         => $tenantId,
            'provider_name'     => trim((string) $this->request->getPost('provider_name')),
            'api_base_url'      => trim((string) $this->request->getPost('api_base_url')),
            'api_key_encrypted' => $secret !== '' ? $this->encryptSecret($secret) : ($existing->api_key_encrypted ?? null),
            'sender_id'         => trim((string) $this->request->getPost('sender_id')),
            'is_default'        => 1,
            'status'            => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ];

        if ($errors = $this->validateWhatsappConfigInput($payload)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        if ($existing) {
            $this->tenantWhatsappConfigModel->updateWithActor($existing->id, $payload);
        } else {
            $this->tenantWhatsappConfigModel->insertWithActor($payload);
        }

        return redirect()->to('/settings')->with('message', 'WhatsApp configuration updated successfully.');
    }

    protected function validateProfileInput(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Institute name is required.';
        }

        if ($data['owner_email'] !== '' && ! filter_var($data['owner_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Owner email must be valid.';
        }

        if ($data['country_code'] !== '' && strlen($data['country_code']) !== 2) {
            $errors[] = 'Country code must be 2 characters.';
        }

        if ($data['country_code'] !== '' && ! ctype_alpha($data['country_code'])) {
            $errors[] = 'Country code must contain letters only.';
        }

        if ($data['default_currency_code'] !== '' && strlen($data['default_currency_code']) !== 3) {
            $errors[] = 'Currency code must be 3 characters.';
        }

        if ($data['default_currency_code'] !== '' && ! ctype_alpha($data['default_currency_code'])) {
            $errors[] = 'Currency code must contain letters only.';
        }

        if ($data['default_timezone'] !== '' && ! in_array($data['default_timezone'], timezone_identifiers_list(), true)) {
            $errors[] = 'Default timezone must be a valid PHP timezone identifier.';
        }

        return $errors;
    }

    protected function validatePreferencesInput(array $data): array
    {
        $errors = [];
        $allowedModes = ['own', 'restricted', 'all'];

        if ($data['branding_name'] === '') {
            $errors[] = 'Branding name is required.';
        }

        if ($data['default_timezone'] !== '' && ! in_array($data['default_timezone'], timezone_identifiers_list(), true)) {
            $errors[] = 'Default timezone must be a valid PHP timezone identifier.';
        }

        if ($data['default_currency_code'] !== '' && (strlen($data['default_currency_code']) !== 3 || ! ctype_alpha($data['default_currency_code']))) {
            $errors[] = 'Default currency code must be a 3-letter currency code.';
        }

        if (! in_array($data['branch_visibility_mode'], $allowedModes, true)
            || ! in_array($data['enquiry_visibility_mode'], $allowedModes, true)
            || ! in_array($data['admission_visibility_mode'], $allowedModes, true)) {
            $errors[] = 'Visibility modes must use own, restricted, or all.';
        }

        return $errors;
    }

    protected function validateEmailConfigInput(array $data): array
    {
        $errors = [];

        if ($data['provider_name'] === '') {
            $errors[] = 'SMTP provider name is required.';
        }

        if ($data['from_email'] !== '' && ! filter_var($data['from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'From email must be valid.';
        }

        if ($data['host'] === '') {
            $errors[] = 'SMTP host is required.';
        }

        if ((int) $data['port'] < 1 || (int) $data['port'] > 65535) {
            $errors[] = 'SMTP port must be between 1 and 65535.';
        }

        if ($data['encryption'] !== '' && ! in_array($data['encryption'], ['tls', 'ssl', 'starttls', 'none'], true)) {
            $errors[] = 'Encryption must be tls, ssl, starttls, or none.';
        }

        return $errors;
    }

    protected function validateWhatsappConfigInput(array $data): array
    {
        $errors = [];

        if ($data['provider_name'] === '') {
            $errors[] = 'WhatsApp provider name is required.';
        }

        if ($data['api_base_url'] !== '' && filter_var($data['api_base_url'], FILTER_VALIDATE_URL) === false) {
            $errors[] = 'WhatsApp API base URL must be a valid URL.';
        }

        if ($data['sender_id'] === '') {
            $errors[] = 'Sender ID is required.';
        }

        return $errors;
    }

    protected function encryptSecret(string $value): string
    {
        $config = config(Encryption::class);

        if ($config->key === '') {
            return base64_encode($value);
        }

        return bin2hex(service('encrypter')->encrypt($value));
    }

    protected function decorateEmailConfig(?object $config): ?object
    {
        if (! $config) {
            return null;
        }

        $config->has_password = $config->password_encrypted !== null && $config->password_encrypted !== '';
        return $config;
    }

    protected function decorateWhatsappConfig(?object $config): ?object
    {
        if (! $config) {
            return null;
        }

        $config->has_api_key = $config->api_key_encrypted !== null && $config->api_key_encrypted !== '';
        return $config;
    }
}
