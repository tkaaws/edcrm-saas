<?php

namespace App\Controllers;

use App\Models\BranchModel;
use App\Models\BranchSettingValueModel;
use App\Models\SettingDefinitionModel;
use App\Support\RegionalOptions;

class BranchSettings extends BaseController
{
    protected BranchModel $branchModel;
    protected BranchSettingValueModel $branchSettingValueModel;
    protected SettingDefinitionModel $settingDefinitionModel;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
        $this->branchSettingValueModel = new BranchSettingValueModel();
        $this->settingDefinitionModel = new SettingDefinitionModel();
    }

    public function index(int $branchId): string
    {
        $branch = $this->branchModel->findForTenant($branchId);
        if (! $branch) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('branches/settings', $this->buildShellViewData([
            'title'        => 'Branch Settings',
            'pageTitle'    => 'Branch Settings',
            'activeNav'    => 'branches',
            'branchRecord' => $branch,
            'sections'     => $this->buildSections((int) session()->get('tenant_id'), $branch),
        ]));
    }

    public function updateCategory(int $branchId, string $category)
    {
        $branch = $this->branchModel->findForTenant($branchId);
        if (! $branch) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tenantId = (int) session()->get('tenant_id');
        $definitions = array_values(array_filter(
            $this->settingDefinitionModel
            ->where('scope', 'branch')
            ->where('category', $category)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll(),
            fn(object $definition): bool => $this->isDefinitionEnabledForTenant($tenantId, $definition)
        ));

        if ($definitions === []) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $errors = [];
        $values = [];

        foreach ($definitions as $definition) {
            $lockMode = service('settingsResolver')->getLockModeForTenant($tenantId, $definition->key);
            if (in_array($lockMode, ['tenant_locked', 'branch_locked', 'platform_enforced'], true)) {
                continue;
            }

            $fieldName = $this->fieldNameForKey($definition->key);
            $value = $this->normalizeInput($definition, $this->request->getPost($fieldName));

            $fieldErrors = $this->validateDefinitionValue($definition, $value);
            if ($fieldErrors !== []) {
                $errors = array_merge($errors, $fieldErrors);
                continue;
            }

            $values[$definition->key] = $value;
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        foreach ($definitions as $definition) {
            if (! array_key_exists($definition->key, $values)) {
                continue;
            }

            $this->branchSettingValueModel->upsertValue(
                $tenantId,
                $branchId,
                $definition->key,
                $values[$definition->key],
                (string) $definition->value_type
            );
        }

        return redirect()->to('/branches/' . $branchId . '/settings')->with('message', ucfirst($category) . ' branch settings updated.');
    }

    protected function buildSections(int $tenantId, object $branch): array
    {
        $categories = [
            'regional' => [
                'title' => 'Regional override',
                'subtitle' => 'Override tenant timezone, currency, and branch-local calendar defaults.',
            ],
            'operations' => [
                'title' => 'Operations',
                'subtitle' => 'Set branch-level routing and ownership defaults.',
            ],
            'enquiry' => [
                'title' => 'Enquiry override',
                'subtitle' => 'Override enquiry expiry and duplicate behavior for this branch.',
            ],
        ];

        $sections = [];
        foreach ($categories as $category => $meta) {
            $definitions = $this->settingDefinitionModel
                ->where('scope', 'branch')
                ->where('category', $category)
                ->where('is_active', 1)
                ->orderBy('sort_order', 'ASC')
                ->findAll();
            $definitions = array_values(array_filter(
                $definitions,
                fn(object $definition): bool => $this->isDefinitionEnabledForTenant($tenantId, $definition)
            ));

            $fields = [];
            foreach ($definitions as $definition) {
                $fields[] = [
                    'definition' => $definition,
                    'formKey'    => $this->fieldNameForKey($definition->key),
                    'value'      => service('settingsResolver')->getEffectiveSetting($tenantId, (int) $branch->id, $definition->key),
                    'lockMode'   => service('settingsResolver')->getLockModeForTenant($tenantId, $definition->key),
                    'isLocked'   => in_array(service('settingsResolver')->getLockModeForTenant($tenantId, $definition->key), ['tenant_locked', 'branch_locked', 'platform_enforced'], true),
                    'options'    => $this->resolveOptionsForDefinition($definition),
                    'optionLabels' => $this->resolveOptionLabels($definition),
                ];
            }

            if ($fields === []) {
                continue;
            }

            $sections[] = [
                'category' => $category,
                'title'    => $meta['title'],
                'subtitle' => $meta['subtitle'],
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

    protected function validateDefinitionValue(object $definition, mixed $value): array
    {
        $errors = [];
        $options = $this->decodeOptions($definition->allowed_options_json);

        if ($options !== [] && ! in_array((string) $value, $options, true)) {
            $errors[] = $definition->label . ' must use one of the allowed values.';
        }

        if ((string) $definition->value_type === 'int' && $value !== null && $value < 0) {
            $errors[] = $definition->label . ' cannot be negative.';
        }

        if (str_contains($definition->key, 'currency') && $value !== '' && (! is_string($value) || strlen($value) !== 3 || ! ctype_alpha($value))) {
            $errors[] = $definition->label . ' must be a 3-letter currency code.';
        }

        if (str_contains($definition->key, 'timezone') && $value !== '' && ! in_array((string) $value, timezone_identifiers_list(), true)) {
            $errors[] = $definition->label . ' must be a valid timezone.';
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

    /**
     * @return list<string>
     */
    protected function resolveOptionsForDefinition(object $definition): array
    {
        $options = $this->decodeOptions($definition->allowed_options_json);
        if ($options !== []) {
            return $options;
        }

        return RegionalOptions::definitionOptions((string) $definition->key);
    }

    /**
     * @return array<string, string>
     */
    protected function resolveOptionLabels(object $definition): array
    {
        return RegionalOptions::definitionOptionLabels((string) $definition->key);
    }

    protected function isDefinitionEnabledForTenant(int $tenantId, object $definition): bool
    {
        $moduleCode = (string) ($definition->module_code ?? 'crm_core');
        return $moduleCode === '' || $moduleCode === 'crm_core'
            ? true
            : service('featureGate')->isEnabled($tenantId, $moduleCode);
    }
}
