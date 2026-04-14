<?php

namespace App\Models;

class BranchSettingValueModel extends BaseModel
{
    protected $table      = 'branch_setting_values';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'key',
        'value',
        'value_type',
        'created_by',
        'updated_by',
    ];

    public function findValue(int $branchId, string $key): ?object
    {
        return $this->where('branch_id', $branchId)
                    ->where('key', $key)
                    ->first();
    }

    public function upsertValue(int $tenantId, int $branchId, string $key, mixed $value, string $valueType = 'string'): void
    {
        $storedValue = match ($valueType) {
            'bool', 'boolean' => $value ? '1' : '0',
            'json', 'array', 'object' => json_encode($value),
            default => $value === null ? null : (string) $value,
        };

        $existing = $this->withoutTenantScope()
                         ->where('branch_id', $branchId)
                         ->where('key', $key)
                         ->first();

        $payload = [
            'tenant_id'  => $tenantId,
            'branch_id'  => $branchId,
            'key'        => $key,
            'value'      => $storedValue,
            'value_type' => $valueType,
        ];

        if ($existing) {
            $this->updateWithActor((int) $existing->id, $payload);
            return;
        }

        $this->insertWithActor($payload);
    }
}
