<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterDataValueModel extends Model
{
    protected $table      = 'master_data_values';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'type_id',
        'scope_type',
        'tenant_id',
        'parent_value_id',
        'code',
        'label',
        'short_label',
        'description',
        'color_code',
        'icon_name',
        'sort_order',
        'is_system',
        'status',
        'metadata_json',
        'created_by',
        'updated_by',
    ];

    public function getPlatformValuesByType(int $typeId): array
    {
        return $this->where('type_id', $typeId)
                    ->where('scope_type', 'platform')
                    ->where('status', 'active')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('label', 'ASC')
                    ->findAll();
    }

    public function getTenantValuesByType(int $typeId, int $tenantId): array
    {
        return $this->where('type_id', $typeId)
                    ->where('scope_type', 'tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('label', 'ASC')
                    ->findAll();
    }

    public function codeExistsForScope(int $typeId, string $scopeType, string $code, ?int $tenantId = null, ?int $ignoreId = null): bool
    {
        $builder = $this->where('type_id', $typeId)
                        ->where('scope_type', $scopeType)
                        ->where('code', $code);

        if ($scopeType === 'tenant') {
            $builder->where('tenant_id', $tenantId);
        } else {
            $builder->where('tenant_id', null);
        }

        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->countAllResults() > 0;
    }

    public function findTenantValue(int $valueId, int $tenantId): ?object
    {
        return $this->where('id', $valueId)
                    ->where('scope_type', 'tenant')
                    ->where('tenant_id', $tenantId)
                    ->first();
    }
}
