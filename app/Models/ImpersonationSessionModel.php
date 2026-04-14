<?php

namespace App\Models;

use CodeIgniter\Model;

class ImpersonationSessionModel extends Model
{
    protected $table      = 'impersonation_sessions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'tenant_id',
        'actor_user_id',
        'target_user_id',
        'reason',
        'started_at',
        'ended_at',
        'actor_ip',
        'actor_user_agent',
    ];
}
