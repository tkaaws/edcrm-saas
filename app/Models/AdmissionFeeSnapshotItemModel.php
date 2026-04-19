<?php

namespace App\Models;

class AdmissionFeeSnapshotItemModel extends BaseModel
{
    protected $table      = 'admission_fee_snapshot_items';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'admission_id',
        'snapshot_id',
        'fee_head_name',
        'fee_head_code',
        'amount',
        'allow_discount',
        'display_order',
        'created_by',
        'updated_by',
    ];

    public function getForAdmission(int $admissionId): array
    {
        return $this->where('admission_id', $admissionId)
            ->orderBy('display_order', 'ASC')
            ->findAll();
    }
}
