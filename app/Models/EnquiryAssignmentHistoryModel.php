<?php

namespace App\Models;

class EnquiryAssignmentHistoryModel extends BaseModel
{
    protected $table      = 'enquiry_assignment_history';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'enquiry_id',
        'from_branch_id',
        'to_branch_id',
        'from_user_id',
        'to_user_id',
        'assigned_by',
        'assignment_type',
        'reason',
        'bulk_batch_id',
        'assigned_on',
        'created_by',
        'updated_by',
    ];
}
