<?php

namespace App\Models;

class FeeStructureItemModel extends BaseModel
{
    protected $table      = 'fee_structure_items';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'fee_structure_id',
        'fee_head_name',
        'fee_head_code',
        'amount',
        'allow_discount',
        'display_order',
        'created_by',
        'updated_by',
    ];

    public function getForStructure(int $structureId): array
    {
        return $this->where('fee_structure_id', $structureId)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * @param array<int, int> $structureIds
     * @return array<int, array<int, object>>
     */
    public function getGroupedForStructures(array $structureIds): array
    {
        if ($structureIds === []) {
            return [];
        }

        $rows = $this->whereIn('fee_structure_id', $structureIds)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->fee_structure_id][] = $row;
        }

        return $grouped;
    }
}
