<?php

namespace App\Models;

class AdmissionInstallmentModel extends BaseModel
{
    protected $table      = 'admission_installments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'admission_id',
        'installment_number',
        'due_date',
        'due_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    public function getForAdmission(int $admissionId): array
    {
        return $this->where('admission_id', $admissionId)
            ->orderBy('installment_number', 'ASC')
            ->findAll();
    }
}
