<?php

namespace App\Services;

use App\Models\BranchSettingValueModel;
use App\Models\SettingDefinitionModel;
use App\Models\TenantPolicyOverrideModel;
use App\Models\TenantSettingValueModel;

class SettingsResolverService
{
    protected SettingDefinitionModel $definitions;
    protected TenantSettingValueModel $tenantValues;
    protected BranchSettingValueModel $branchValues;
    protected TenantPolicyOverrideModel $policyOverrides;

    /**
     * @var array<string, mixed>
     */
    protected array $cache = [];

    public function __construct()
    {
        $this->definitions     = new SettingDefinitionModel();
        $this->tenantValues    = new TenantSettingValueModel();
        $this->branchValues    = new BranchSettingValueModel();
        $this->policyOverrides = new TenantPolicyOverrideModel();
    }

    public function getEffectiveSetting(int $tenantId, ?int $branchId, string $key): mixed
    {
        $cacheKey = implode(':', [$tenantId, $branchId ?? 0, $key]);
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $definition = $this->definitions->findByKey($key);
        $default = $definition ? $this->decodeValue($definition->default_value_json, (string) $definition->value_type) : null;

        $override = $this->policyOverrides->findByTenantAndKey($tenantId, $key);
        if ($override && $override->override_value !== null) {
            return $this->cache[$cacheKey] = $this->decodeValue($override->override_value, (string) $override->value_type);
        }

        if ($branchId !== null) {
            $branchValue = $this->branchValues->forTenant($tenantId)->findValue($branchId, $key);
            if ($branchValue && $branchValue->value !== null) {
                return $this->cache[$cacheKey] = $this->decodeValue($branchValue->value, (string) $branchValue->value_type);
            }
        }

        $tenantValue = $this->tenantValues->forTenant($tenantId)
                                          ->where('key', $key)
                                          ->first();
        if ($tenantValue && $tenantValue->value !== null) {
            return $this->cache[$cacheKey] = $this->decodeValue($tenantValue->value, (string) $tenantValue->value_type);
        }

        return $this->cache[$cacheKey] = $default;
    }

    public function isLockedForTenant(int $tenantId, string $key): bool
    {
        $override = $this->policyOverrides->findByTenantAndKey($tenantId, $key);
        if (! $override) {
            return false;
        }

        return in_array($override->lock_mode, ['tenant_locked', 'branch_locked', 'platform_enforced'], true);
    }

    public function getLockModeForTenant(int $tenantId, string $key): string
    {
        return $this->policyOverrides->findByTenantAndKey($tenantId, $key)->lock_mode ?? 'editable';
    }

    /**
     * @return array<string, array<int, object>>
     */
    public function getDefinitionsForScope(string $scope): array
    {
        return $this->definitions->getGroupedByScopeAndCategory($scope);
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
