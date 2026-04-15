<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterDataTypeModel extends Model
{
    protected $table      = 'master_data_types';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'code',
        'name',
        'description',
        'module_code',
        'status',
        'allow_platform_entries',
        'allow_tenant_entries',
        'allow_tenant_hide_platform_values',
        'strict_reporting_catalog',
        'supports_hierarchy',
        'sort_order',
    ];

    public function findByCode(string $code): ?object
    {
        return $this->where('code', $code)->first();
    }

    public function getActiveTypes(): array
    {
        return $this->where('status', 'active')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }
}
