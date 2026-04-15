<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantMasterDataOverrideModel extends Model
{
    protected $table      = 'tenant_master_data_overrides';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'tenant_id',
        'master_data_value_id',
        'is_visible',
        'sort_order_override',
        'label_override',
        'updated_by',
        'updated_at',
    ];

    public function getOverrideMapForTenant(int $tenantId, array $valueIds): array
    {
        if ($valueIds === []) {
            return [];
        }

        $rows = $this->where('tenant_id', $tenantId)
                     ->whereIn('master_data_value_id', $valueIds)
                     ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->master_data_value_id] = $row;
        }

        return $map;
    }

    public function upsertVisibility(int $tenantId, int $valueId, bool $isVisible, ?int $updatedBy = null): bool
    {
        $existing = $this->where('tenant_id', $tenantId)
                         ->where('master_data_value_id', $valueId)
                         ->first();

        $payload = [
            'tenant_id'            => $tenantId,
            'master_data_value_id' => $valueId,
            'is_visible'           => $isVisible ? 1 : 0,
            'updated_by'           => $updatedBy,
            'updated_at'           => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            return $this->update((int) $existing->id, $payload);
        }

        return (bool) $this->insert($payload);
    }
}
