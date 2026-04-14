<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingDefinitionModel extends Model
{
    protected $table      = 'setting_definitions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'key',
        'label',
        'description',
        'scope',
        'category',
        'module_code',
        'value_type',
        'allowed_options_json',
        'default_value_json',
        'is_sensitive',
        'is_active',
        'sort_order',
    ];

    public function findByKey(string $key): ?object
    {
        return $this->where('key', $key)->first();
    }

    /**
     * @return array<string, array<int, object>>
     */
    public function getGroupedByScopeAndCategory(string $scope): array
    {
        $rows = $this->where('scope', $scope)
                     ->where('is_active', 1)
                     ->orderBy('category', 'ASC')
                     ->orderBy('sort_order', 'ASC')
                     ->orderBy('label', 'ASC')
                     ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->category][] = $row;
        }

        return $grouped;
    }
}
