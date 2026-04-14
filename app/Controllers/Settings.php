<?php

namespace App\Controllers;

use App\Models\SettingDefinitionModel;
use App\Models\TenantEmailConfigModel;
use App\Models\TenantModel;
use App\Models\TenantSettingValueModel;
use App\Models\TenantSettingsModel;
use App\Models\TenantWhatsappConfigModel;
use App\Services\SettingsResolverService;
use Config\Encryption;

class Settings extends BaseController
{
    protected TenantModel $tenantModel;
    protected TenantSettingsModel $tenantSettingsModel;
    protected TenantSettingValueModel $tenantSettingValueModel;
    protected TenantEmailConfigModel $tenantEmailConfigModel;
    protected TenantWhatsappConfigModel $tenantWhatsappConfigModel;
    protected SettingDefinitionModel $settingDefinitionModel;
    protected SettingsResolverService $settingsResolver;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
        $this->tenantSettingsModel = new TenantSettingsModel();
        $this->tenantSettingValueModel = new TenantSettingValueModel();
        $this->tenantEmailConfigModel = new TenantEmailConfigModel();
        $this->tenantWhatsappConfigModel = new TenantWhatsappConfigModel();
        $this->settingDefinitionModel = new SettingDefinitionModel();
        $this->settingsResolver = service('settingsResolver');
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
            'catalogSections' => $this->buildCatalogSections($tenantId),
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
            'default_timezone'          => $settings->default_timezone ?? null,
            'default_currency_code'     => $settings->default_currency_code ?? null,
            'locale_code'               => $settings->locale_code ?? null,
            'branch_visibility_mode'    => $settings->branch_visibility_mode ?? 'restricted',
            'enquiry_visibility_mode'   => $settings->enquiry_visibility_mode ?? 'restricted',
            'admission_visibility_mode' => $settings->admission_visibility_mode ?? 'restricted',
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

    public function updateCatalogCategory(string $category)
    {
        $tenantId = (int) session()->get('tenant_id');
        $definitions = $this->settingDefinitionModel
            ->where('scope', 'tenant')
            ->where('category', $category)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        if ($definitions === []) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $errors = [];
        $resolvedValues = [];

        foreach ($definitions as $definition) {
            if ($this->settingsResolver->isLockedForTenant($tenantId, $definition->key)) {
                continue;
            }

            $formKey = $this->fieldNameForKey($definition->key);
            $rawValue = $this->request->getPost($formKey);
            $normalized = $this->normalizeDefinitionInput($definition, $rawValue);

            $fieldErrors = $this->validateDefinitionValue($definition, $normalized);
            if ($fieldErrors !== []) {
                $errors = array_merge($errors, $fieldErrors);
                continue;
            }

            $resolvedValues[$definition->key] = $normalized;
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        foreach ($definitions as $definition) {
            if (! array_key_exists($definition->key, $resolvedValues)) {
                continue;
            }

            $this->tenantSettingValueModel->upsertValue(
                $tenantId,
                $definition->key,
                $resolvedValues[$definition->key],
                (string) $definition->value_type
            );
        }

        $this->syncLegacySettings($tenantId, $category, $resolvedValues);

        return redirect()->to('/settings')->with('message', ucfirst($category) . ' settings updated successfully.');
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

        if ($data['branding_name'] === '') {
            $errors[] = 'Branding name is required.';
        }

        if ($data['default_timezone'] !== '' && ! in_array($data['default_timezone'], timezone_identifiers_list(), true)) {
            $errors[] = 'Default timezone must be a valid PHP timezone identifier.';
        }

        if ($data['default_currency_code'] !== '' && (strlen($data['default_currency_code']) !== 3 || ! ctype_alpha($data['default_currency_code']))) {
            $errors[] = 'Default currency code must be a 3-letter currency code.';
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

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildCatalogSections(int $tenantId): array
    {
        $sectionMeta = [
            'regional' => [
                'title' => 'Regional defaults',
                'subtitle' => 'Timezone, currency, locale, and calendar defaults for the institute.',
            ],
            'visibility' => [
                'title' => 'Visibility policy',
                'subtitle' => 'Control how records are visible across branches, teams, and expired pipelines.',
            ],
            'security' => [
                'title' => 'Security policy',
                'subtitle' => 'Password, session, and impersonation rules for tenant users.',
            ],
            'enquiry' => [
                'title' => 'Enquiry policy',
                'subtitle' => 'Expiry, duplicate handling, assignment, and lifecycle defaults for Enquiry.',
            ],
        ];

        $sections = [];

        foreach ($sectionMeta as $category => $meta) {
            $definitions = $this->settingDefinitionModel
                ->where('scope', 'tenant')
                ->where('category', $category)
                ->where('is_active', 1)
                ->orderBy('sort_order', 'ASC')
                ->findAll();

            $fields = [];
            foreach ($definitions as $definition) {
                $fields[] = [
                    'definition' => $definition,
                    'formKey'    => $this->fieldNameForKey($definition->key),
                    'value'      => $this->settingsResolver->getEffectiveSetting($tenantId, null, $definition->key),
                    'lockMode'   => $this->settingsResolver->getLockModeForTenant($tenantId, $definition->key),
                    'isLocked'   => $this->settingsResolver->isLockedForTenant($tenantId, $definition->key),
                    'options'    => $this->decodeOptions($definition->allowed_options_json),
                ];
            }

            $sections[] = [
                'category' => $category,
                'title' => $meta['title'],
                'subtitle' => $meta['subtitle'],
                'fields' => $fields,
            ];
        }

        return $sections;
    }

    protected function fieldNameForKey(string $key): string
    {
        return str_replace(['.', '-'], '__', $key);
    }

    protected function normalizeDefinitionInput(object $definition, mixed $rawValue): mixed
    {
        return match ((string) $definition->value_type) {
            'int', 'integer' => $rawValue === '' || $rawValue === null ? null : (int) $rawValue,
            'bool', 'boolean' => in_array((string) $rawValue, ['1', 'true', 'on', 'yes'], true),
            'json', 'array', 'object' => $this->normalizeListInput((string) $rawValue),
            default => trim((string) $rawValue),
        };
    }

    /**
     * @return array<int, string>
     */
    protected function validateDefinitionValue(object $definition, mixed $value): array
    {
        $errors = [];
        $options = $this->decodeOptions($definition->allowed_options_json);

        if ($options !== [] && ! in_array((string) $value, $options, true)) {
            $errors[] = $definition->label . ' must use one of the allowed values.';
        }

        if ((string) $definition->value_type === 'string' && str_contains($definition->key, 'timezone') && $value !== '' && ! in_array((string) $value, timezone_identifiers_list(), true)) {
            $errors[] = $definition->label . ' must be a valid timezone.';
        }

        if (str_contains($definition->key, 'currency') && $value !== '' && (! is_string($value) || strlen($value) !== 3 || ! ctype_alpha($value))) {
            $errors[] = $definition->label . ' must be a 3-letter currency code.';
        }

        if (str_contains($definition->key, 'locale') && $value !== '' && ! is_string($value)) {
            $errors[] = $definition->label . ' must be a valid locale code.';
        }

        if (in_array((string) $definition->value_type, ['int', 'integer'], true) && $value !== null && $value < 0) {
            $errors[] = $definition->label . ' cannot be negative.';
        }

        return $errors;
    }

    protected function syncLegacySettings(int $tenantId, string $category, array $values): void
    {
        if ($category === 'regional') {
            $tenantUpdate = [];
            $settingsUpdate = ['tenant_id' => $tenantId];

            if (isset($values['tenant.regional.timezone'])) {
                $tenantUpdate['default_timezone'] = $values['tenant.regional.timezone'];
                $settingsUpdate['default_timezone'] = $values['tenant.regional.timezone'];
            }

            if (isset($values['tenant.regional.currency'])) {
                $tenantUpdate['default_currency_code'] = strtoupper((string) $values['tenant.regional.currency']);
                $settingsUpdate['default_currency_code'] = strtoupper((string) $values['tenant.regional.currency']);
            }

            if (isset($values['tenant.regional.locale'])) {
                $tenantUpdate['locale_code'] = (string) $values['tenant.regional.locale'];
                $settingsUpdate['locale_code'] = (string) $values['tenant.regional.locale'];
            }

            if ($tenantUpdate !== []) {
                $this->tenantModel->update($tenantId, $tenantUpdate);
            }

            if (count($settingsUpdate) > 1) {
                $this->upsertStructuredTenantSettings($tenantId, $settingsUpdate);
            }
        }

        if ($category === 'visibility') {
            $settingsUpdate = ['tenant_id' => $tenantId];

            if (isset($values['tenant.visibility.branch_mode'])) {
                $settingsUpdate['branch_visibility_mode'] = (string) $values['tenant.visibility.branch_mode'];
            }

            if (isset($values['tenant.visibility.enquiry_mode'])) {
                $settingsUpdate['enquiry_visibility_mode'] = (string) $values['tenant.visibility.enquiry_mode'];
            }

            if ($settingsUpdate !== ['tenant_id' => $tenantId]) {
                $this->upsertStructuredTenantSettings($tenantId, $settingsUpdate);
            }
        }
    }

    protected function upsertStructuredTenantSettings(int $tenantId, array $data): void
    {
        $settings = $this->tenantSettingsModel->findByTenant($tenantId);

        if ($settings) {
            $this->tenantSettingsModel->updateWithActor((int) $settings->id, $data);
            return;
        }

        $this->tenantSettingsModel->insertWithActor($data);
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeListInput(string $rawValue): array
    {
        $parts = preg_split('/[\r\n,]+/', $rawValue) ?: [];
        $parts = array_map(static fn(string $item): string => trim($item), $parts);
        $parts = array_filter($parts, static fn(string $item): bool => $item !== '');

        return array_values(array_unique($parts));
    }

    /**
     * @return array<int, string>
     */
    protected function decodeOptions(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded)));
    }
}
