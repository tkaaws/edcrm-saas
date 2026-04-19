<?php

namespace App\Models;

class AdmissionFeeSnapshotModel extends BaseModel
{
    protected $table      = 'admission_fee_snapshots';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'admission_id',
        'fee_structure_id',
        'fee_plan_label',
        'gross_amount',
        'discount_amount',
        'net_amount',
        'paid_amount',
        'balance_amount',
        'created_by',
        'updated_by',
    ];

    public function findForAdmission(int $admissionId): ?object
    {
        return $this->where('admission_id', $admissionId)->first();
    }
}
