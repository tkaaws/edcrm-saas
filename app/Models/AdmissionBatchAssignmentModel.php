<?php

namespace App\Models;

class AdmissionBatchAssignmentModel extends BaseModel
{
    protected $table      = 'admission_batch_assignments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'admission_id',
        'batch_id',
        'status',
        'assigned_on',
        'assigned_by',
        'created_by',
        'updated_by',
    ];
}
