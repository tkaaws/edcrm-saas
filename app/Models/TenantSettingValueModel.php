<?php

namespace App\Models;

class TenantSettingValueModel extends BaseModel
{
    protected $table      = 'tenant_setting_values';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'key',
        'value',
        'value_type',
    ];

    public function findValue(int $tenantId, string $key): ?object
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('key', $key)
                    ->first();
    }

    public function upsertValue(int $tenantId, string $key, mixed $value, string $valueType = 'string'): void
    {
        $storedValue = match ($valueType) {
            'bool', 'boolean' => $value ? '1' : '0',
            'json', 'array', 'object' => json_encode($value),
            default => $value === null ? null : (string) $value,
        };

        $existing = $this->findValue($tenantId, $key);

        $payload = [
            'tenant_id'   => $tenantId,
            'key'         => $key,
            'value'       => $storedValue,
            'value_type'  => $valueType,
        ];

        if ($existing) {
            $this->updateWithActor((int) $existing->id, $payload);
            return;
        }

        $this->insertWithActor($payload);
    }
}
