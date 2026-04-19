<?php

namespace App\Models;

class AdmissionFollowupModel extends BaseModel
{
    protected $table      = 'admission_followups';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'admission_id',
        'followup_status_id',
        'communication_mode_id',
        'remarks',
        'next_followup_at',
        'is_system_generated',
        'created_by',
        'updated_by',
    ];
}
