<?php

namespace App\Models;

class AdmissionPaymentModel extends BaseModel
{
    protected $table      = 'admission_payments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'admission_id',
        'receipt_number',
        'payment_kind',
        'amount',
        'payment_date',
        'payment_mode',
        'transaction_reference',
        'remarks',
        'received_by',
        'is_cancelled',
        'cancelled_by',
        'cancelled_at',
        'created_by',
        'updated_by',
    ];

    public function getForAdmission(int $admissionId): array
    {
        return $this->where('admission_id', $admissionId)
            ->orderBy('payment_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}
