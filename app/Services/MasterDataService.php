<?php

namespace App\Services;

use App\Models\MasterDataTypeModel;
use App\Models\MasterDataValueModel;
use App\Models\TenantMasterDataOverrideModel;
use RuntimeException;

class MasterDataService
{
    protected MasterDataTypeModel $types;
    protected MasterDataValueModel $values;
    protected TenantMasterDataOverrideModel $overrides;

    /**
     * @var array<string, array<int, object>>
     */
    protected array $cache = [];

    public function __construct()
    {
        $this->types = new MasterDataTypeModel();
        $this->values = new MasterDataValueModel();
        $this->overrides = new TenantMasterDataOverrideModel();
    }

    public function getTypeByCode(string $typeCode): ?object
    {
        return $this->types->findByCode($typeCode);
    }

    public function getPlatformValues(string $typeCode): array
    {
        $type = $this->requireType($typeCode);
        return $this->values->getPlatformValuesByType((int) $type->id);
    }

    public function getTenantValues(string $typeCode, int $tenantId): array
    {
        $type = $this->requireType($typeCode);
        return $this->values->getTenantValuesByType((int) $type->id, $tenantId);
    }

    public function getEffectiveValues(string $typeCode, int $tenantId): array
    {
        $cacheKey = $typeCode . ':' . $tenantId;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $type = $this->requireType($typeCode);
        $platformValues = $this->values->getPlatformValuesByType((int) $type->id);
        $tenantValues = $this->values->getTenantValuesByType((int) $type->id, $tenantId);

        $overrideMap = $this->overrides->getOverrideMapForTenant(
            $tenantId,
            array_map(static fn(object $row): int => (int) $row->id, $platformValues)
        );

        $effective = [];

        foreach ($platformValues as $value) {
            $override = $overrideMap[(int) $value->id] ?? null;
            if ($override && (int) $override->is_visible !== 1) {
                continue;
            }

            if ($override && $override->label_override) {
                $value->label = $override->label_override;
            }

            if ($override && $override->sort_order_override !== null) {
                $value->sort_order = (int) $override->sort_order_override;
            }

            $effective[] = $value;
        }

        foreach ($tenantValues as $value) {
            $effective[] = $value;
        }

        usort($effective, static function (object $left, object $right): int {
            $leftSort = (int) ($left->sort_order ?? 0);
            $rightSort = (int) ($right->sort_order ?? 0);

            if ($leftSort !== $rightSort) {
                return $leftSort <=> $rightSort;
            }

            return strcmp((string) $left->label, (string) $right->label);
        });

        return $this->cache[$cacheKey] = $effective;
    }

    public function createTenantValue(string $typeCode, int $tenantId, array $payload): int
    {
        $type = $this->requireType($typeCode);

        if (! (bool) $type->allow_tenant_entries) {
            throw new RuntimeException("Tenant entries are not allowed for master type {$typeCode}.");
        }

        $codeSource = (string) ($payload['code'] ?? '');
        if ($codeSource === '') {
            $codeSource = (string) ($payload['label'] ?? '');
        }

        $code = $this->normalizeCode($codeSource);
        $label = trim((string) ($payload['label'] ?? ''));

        if ($label === '') {
            throw new RuntimeException('Master data name is required.');
        }

        if ($code === '') {
            throw new RuntimeException('Unable to generate a valid master data code from the name.');
        }

        if ($this->values->codeExistsForScope((int) $type->id, 'tenant', $code, $tenantId)) {
            throw new RuntimeException("A tenant value with code {$code} already exists for {$typeCode}.");
        }

        $id = $this->values->insert([
            'type_id'         => (int) $type->id,
            'scope_type'      => 'tenant',
            'tenant_id'       => $tenantId,
            'parent_value_id' => $payload['parent_value_id'] ?? null,
            'code'            => $code,
            'label'           => $label,
            'short_label'     => $payload['short_label'] ?? null,
            'description'     => $payload['description'] ?? null,
            'color_code'      => $payload['color_code'] ?? null,
            'icon_name'       => $payload['icon_name'] ?? null,
            'sort_order'      => (int) ($payload['sort_order'] ?? 0),
            'is_system'       => 0,
            'status'          => ($payload['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
            'metadata_json'   => $this->encodeMetadata($payload['metadata'] ?? null),
            'created_by'      => session()->get('user_id') ?: null,
            'updated_by'      => session()->get('user_id') ?: null,
        ]);

        unset($this->cache[$typeCode . ':' . $tenantId]);

        return (int) $id;
    }

    public function hidePlatformValue(int $tenantId, int $valueId): void
    {
        $row = $this->values->find($valueId);
        if (! $row || $row->scope_type !== 'platform') {
            throw new RuntimeException('Only platform master values can be hidden for a tenant.');
        }

        $type = $this->types->find((int) $row->type_id);
        if (! $type || ! (bool) $type->allow_tenant_hide_platform_values) {
            throw new RuntimeException('This master type does not allow tenant-level hiding of platform values.');
        }

        $this->overrides->upsertVisibility($tenantId, $valueId, false, session()->get('user_id') ?: null);
        unset($this->cache[$type->code . ':' . $tenantId]);
    }

    public function showPlatformValue(int $tenantId, int $valueId): void
    {
        $row = $this->values->find($valueId);
        if (! $row || $row->scope_type !== 'platform') {
            throw new RuntimeException('Only platform master values can be restored for a tenant.');
        }

        $type = $this->types->find((int) $row->type_id);
        $this->overrides->upsertVisibility($tenantId, $valueId, true, session()->get('user_id') ?: null);
        if ($type) {
            unset($this->cache[$type->code . ':' . $tenantId]);
        }
    }

    protected function requireType(string $typeCode): object
    {
        $type = $this->types->findByCode($typeCode);
        if (! $type) {
            throw new RuntimeException("Unknown master data type: {$typeCode}");
        }

        return $type;
    }

    protected function normalizeCode(string $value): string
    {
        $code = strtolower(trim($value));
        $code = preg_replace('/[^a-z0-9]+/', '_', $code) ?? '';
        return trim($code, '_');
    }

    protected function encodeMetadata(mixed $metadata): ?string
    {
        if ($metadata === null || $metadata === '') {
            return null;
        }

        return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }
}
