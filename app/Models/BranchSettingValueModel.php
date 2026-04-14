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
}
