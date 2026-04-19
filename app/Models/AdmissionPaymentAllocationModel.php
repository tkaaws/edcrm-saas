<?php

namespace App\Models;

class AdmissionPaymentAllocationModel extends BaseModel
{
    protected $table      = 'admission_payment_allocations';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'admission_id',
        'payment_id',
        'installment_id',
        'fee_snapshot_item_id',
        'allocated_amount',
        'created_by',
        'updated_by',
    ];
}
