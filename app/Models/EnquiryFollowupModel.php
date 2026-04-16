<?php

namespace App\Models;

class EnquiryFollowupModel extends BaseModel
{
    protected $table      = 'enquiry_followups';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'enquiry_id',
        'branch_id',
        'owner_user_id',
        'communication_type_id',
        'followup_outcome_id',
        'remarks',
        'next_followup_at',
        'is_system_generated',
        'created_by',
        'updated_by',
    ];
}
