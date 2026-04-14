<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table      = 'plans';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'code', 'name', 'description', 'status', 'is_public', 'sort_order',
    ];

    public function findByCode(string $code): ?object
    {
        return $this->where('code', $code)->first();
    }

    public function getPublicPlans(): array
    {
        return $this->where('status', 'active')
                    ->where('is_public', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();
    }

    public function getAllActivePlans(): array
    {
        return $this->where('status', 'active')
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();
    }

    /**
     * Get plan with its prices, features, and limits in one shot.
     */
    public function getPlanDetail(int $planId): ?object
    {
        $plan = $this->find($planId);
        if (! $plan) return null;

        $db = db_connect();

        $plan->prices   = $db->table('plan_prices')
                             ->where('plan_id', $planId)
                             ->where('status', 'active')
                             ->get()->getResult();

        $plan->features = $db->table('plan_features')
                             ->where('plan_id', $planId)
                             ->get()->getResult();

        $plan->limits   = $db->table('plan_limits')
                             ->where('plan_id', $planId)
                             ->get()->getResult();

        return $plan;
    }
}
