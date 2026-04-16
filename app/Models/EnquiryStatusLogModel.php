<?php

namespace App\Models;

class EnquiryStatusLogModel extends BaseModel
{
    protected $table      = 'enquiry_status_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'enquiry_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
        'created_by',
        'updated_by',
    ];
}
