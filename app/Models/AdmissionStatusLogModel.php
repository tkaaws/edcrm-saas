<?php

namespace App\Models;

class AdmissionStatusLogModel extends BaseModel
{
    protected $table      = 'admission_status_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'admission_id',
        'old_status',
        'new_status',
        'reason',
        'remarks',
        'changed_by',
        'created_by',
        'updated_by',
    ];
}
