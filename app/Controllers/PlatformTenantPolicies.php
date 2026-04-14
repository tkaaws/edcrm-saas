<?php

namespace App\Controllers;

use App\Models\SettingDefinitionModel;
use App\Models\TenantModel;
use App\Models\TenantPolicyOverrideModel;

class PlatformTenantPolicies extends BaseController
{
    protected TenantModel $tenantModel;
    protected SettingDefinitionModel $settingDefinitionModel;
    protected TenantPolicyOverrideModel $tenantPolicyOverrideModel;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
        $this->settingDefinitionModel = new SettingDefinitionModel();
        $this->tenantPolicyOverrideModel = new TenantPolicyOverrideModel();
    }

    public function index(int $tenantId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $tenant = $this->tenantModel->find($tenantId);
        if (! $tenant) {
            return redirect()->to('/platform/tenants')->with('error', 'Tenant not found.');
        }

        return view('platform/tenants/policy', $this->buildShellViewData([
            'title'       => 'Tenant Policy',
            'pageTitle'   => 'Tenant Policy',
            'activeNav'   => 'tenants',
            'tenantLabel' => 'Platform scope',
            'branchLabel' => 'Policy control',
            'roleLabel'   => 'Provisioning',
            'tenant'      => $tenant,
            'sections'    => $this->buildSections($tenantId),
        ]));
    }

    public function updateCategory(int $tenantId, string $scope, string $category)
    {
        $tenant = $this->tenantModel->find($tenantId);
        if (! $tenant) {
            return redirect()->to('/platform/tenants')->with('error', 'Tenant not found.');
        }

        $definitions = $this->settingDefinitionModel
            ->where('scope', $scope)
            ->where('category', $category)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        if ($definitions === []) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $errors = [];
        foreach ($definitions as $definition) {
            $formKey = $this->fieldNameForKey($definition->key);
            $lockField = $formKey . '__lock_mode';
            $notesField = $formKey . '__notes';

            $value = $this->normalizeInput($definition, $this->request->getPost($formKey));
            $lockMode = $scope === 'platform_policy'
                ? 'platform_enforced'
                : (string) ($this->request->getPost($lockField) ?: 'editable');
            $notes = trim((string) $this->request->getPost($notesField)) ?: null;

            $fieldErrors = $this->validateDefinitionValue($definition, $value, $lockMode);
            if ($fieldErrors !== []) {
                $errors = array_merge($errors, $fieldErrors);
                continue;
            }

            $this->tenantPolicyOverrideModel->upsertOverride(
                $tenantId,
                $definition->key,
                $value,
                (string) $definition->value_type,
                $lockMode,
                $notes
            );
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        return redirect()->to('/platform/tenants/' . $tenantId . '/policy')->with('message', ucfirst($category) . ' platform policy updated.');
    }

    protected function buildSections(int $tenantId): array
    {
        $sectionSpecs = [
            ['scope' => 'platform_policy', 'category' => 'support_access', 'title' => 'Support access', 'subtitle' => 'Platform impersonation and support session controls.'],
            ['scope' => 'tenant', 'category' => 'regional', 'title' => 'Regional locks', 'subtitle' => 'Lock or override timezone, currency, and locale defaults.'],
            ['scope' => 'tenant', 'category' => 'visibility', 'title' => 'Visibility locks', 'subtitle' => 'Govern cross-branch and expired enquiry visibility.'],
            ['scope' => 'tenant', 'category' => 'security', 'title' => 'Security locks', 'subtitle' => 'Control impersonation, password, and session policy.'],
            ['scope' => 'tenant', 'category' => 'enquiry', 'title' => 'Enquiry locks', 'subtitle' => 'Override enquiry expiry, duplicate, and assignment behavior.'],
        ];

        $overrideMap = [];
        foreach ($this->tenantPolicyOverrideModel->getForTenant($tenantId) as $override) {
            $overrideMap[$override->key] = $override;
        }

        $sections = [];
        foreach ($sectionSpecs as $spec) {
            $definitions = $this->settingDefinitionModel
                ->where('scope', $spec['scope'])
                ->where('category', $spec['category'])
                ->where('is_active', 1)
                ->orderBy('sort_order', 'ASC')
                ->findAll();

            $fields = [];
            foreach ($definitions as $definition) {
                $override = $overrideMap[$definition->key] ?? null;
                $value = $override
                    ? $this->decodeValue($override->override_value, (string) $override->value_type)
                    : service('settingsResolver')->getEffectiveSetting($tenantId, null, $definition->key);

                $fields[] = [
                    'definition' => $definition,
                    'formKey'    => $this->fieldNameForKey($definition->key),
                    'value'      => $value,
                    'lockMode'   => $override->lock_mode ?? 'editable',
                    'notes'      => $override->notes ?? '',
                    'options'    => $this->decodeOptions($definition->allowed_options_json),
                    'scope'      => $spec['scope'],
                ];
            }

            $sections[] = [
                'scope'    => $spec['scope'],
                'category' => $spec['category'],
                'title'    => $spec['title'],
                'subtitle' => $spec['subtitle'],
                'fields'   => $fields,
            ];
        }

        return $sections;
    }

    protected function fieldNameForKey(string $key): string
    {
        return str_replace(['.', '-'], '__', $key);
    }

    protected function normalizeInput(object $definition, mixed $rawValue): mixed
    {
        return match ((string) $definition->value_type) {
            'int', 'integer' => $rawValue === '' || $rawValue === null ? null : (int) $rawValue,
            'bool', 'boolean' => in_array((string) $rawValue, ['1', 'true', 'on', 'yes'], true),
            'json', 'array', 'object' => $this->normalizeListInput((string) $rawValue),
            default => trim((string) $rawValue),
        };
    }

    protected function validateDefinitionValue(object $definition, mixed $value, string $lockMode): array
    {
        $errors = [];
        $allowedLockModes = ['editable', 'tenant_locked', 'branch_locked', 'platform_enforced'];
        $options = $this->decodeOptions($definition->allowed_options_json);

        if (! in_array($lockMode, $allowedLockModes, true)) {
            $errors[] = $definition->label . ' uses an invalid lock mode.';
        }

        if ($options !== [] && ! in_array((string) $value, $options, true)) {
            $errors[] = $definition->label . ' must use one of the allowed values.';
        }

        if ((string) $definition->value_type === 'int' && $value !== null && $value < 0) {
            $errors[] = $definition->label . ' cannot be negative.';
        }

        return $errors;
    }

    protected function normalizeListInput(string $rawValue): array
    {
        $parts = preg_split('/[\r\n,]+/', $rawValue) ?: [];
        $parts = array_map(static fn(string $item): string => trim($item), $parts);
        $parts = array_filter($parts, static fn(string $item): bool => $item !== '');

        return array_values(array_unique($parts));
    }

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

    protected function decodeValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'decimal' => (float) $value,
            'bool', 'boolean' => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            'json', 'array', 'object' => json_decode($value, true) ?? [],
            default => $value,
        };
    }
}
