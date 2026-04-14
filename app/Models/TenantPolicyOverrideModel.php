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
}
