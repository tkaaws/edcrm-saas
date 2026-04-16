<?php

namespace App\Models;

class EnquiryModel extends BaseModel
{
    protected $table      = 'enquiries';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'owner_user_id',
        'assigned_on',
        'student_name',
        'email',
        'mobile',
        'whatsapp_number',
        'source_id',
        'college_id',
        'qualification_id',
        'primary_course_id',
        'city',
        'notes',
        'lifecycle_status',
        'closed_reason_id',
        'closed_remarks',
        'last_followup_at',
        'next_followup_at',
        'closed_at',
        'closed_by',
        'admitted_at',
        'created_by',
        'updated_by',
    ];
}
