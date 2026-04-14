<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanFeatureModel extends Model
{
    protected $table      = 'plan_features';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = ['plan_id', 'feature_code', 'is_enabled'];

    /**
     * Get all features for a plan keyed by feature_code => is_enabled.
     */
    public function getForPlan(int $planId): array
    {
        $rows = $this->where('plan_id', $planId)->findAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->feature_code] = (bool) $row->is_enabled;
        }
        return $result;
    }
}
