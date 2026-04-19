<?php

namespace App\Models;

class AdmissionBatchAssignmentHistoryModel extends BaseModel
{
    protected $table      = 'admission_batch_assignment_history';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'admission_id',
        'from_batch_id',
        'to_batch_id',
        'reason',
        'moved_by',
        'moved_at',
        'created_by',
        'updated_by',
    ];
}
