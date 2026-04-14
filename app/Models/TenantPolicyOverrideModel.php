<?php

namespace App\Models;

class TenantPolicyOverrideModel extends BaseModel
{
    protected $table      = 'tenant_policy_overrides';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'key',
        'override_value',
        'value_type',
        'lock_mode',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function findByTenantAndKey(int $tenantId, string $key): ?object
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('key', $key)
                    ->first();
    }

    /**
     * @return array<int, object>
     */
    public function getForTenant(int $tenantId): array
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->findAll();
    }

    public function upsertOverride(
        int $tenantId,
        string $key,
        mixed $value,
        string $valueType = 'string',
        string $lockMode = 'editable',
        ?string $notes = null
    ): void {
        $storedValue = match ($valueType) {
            'bool', 'boolean' => $value ? '1' : '0',
            'json', 'array', 'object' => json_encode($value),
            default => $value === null ? null : (string) $value,
        };

        $existing = $this->findByTenantAndKey($tenantId, $key);

        $payload = [
            'tenant_id'       => $tenantId,
            'key'             => $key,
            'override_value'  => $storedValue,
            'value_type'      => $valueType,
            'lock_mode'       => $lockMode,
            'notes'           => $notes,
        ];

        if ($existing) {
            $this->updateWithActor((int) $existing->id, $payload);
            return;
        }

        $this->insertWithActor($payload);
    }
}
