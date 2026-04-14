<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanLimitModel extends Model
{
    protected $table      = 'plan_limits';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = ['plan_id', 'limit_code', 'limit_value'];

    /**
     * Get all limits for a plan keyed by limit_code => limit_value.
     */
    public function getForPlan(int $planId): array
    {
        $rows = $this->where('plan_id', $planId)->findAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->limit_code] = (int) $row->limit_value;
        }
        return $result;
    }
}
