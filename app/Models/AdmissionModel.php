<?php

namespace App\Models;

class AdmissionModel extends BaseModel
{
    protected $table      = 'admissions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'enquiry_id',
        'admission_number',
        'student_name',
        'email',
        'mobile',
        'whatsapp_number',
        'gender',
        'city',
        'college_id',
        'course_id',
        'assigned_user_id',
        'mode_of_class',
        'admission_date',
        'status',
        'remarks',
        'last_followup_at',
        'next_followup_at',
        'current_batch_id',
        'created_by',
        'updated_by',
    ];

    public function findByEnquiryId(int $tenantId, int $enquiryId): ?object
    {
        return $this->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('enquiry_id', $enquiryId)
            ->first();
    }
}
