<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * SubscriptionModel
 *
 * Not tenant-scoped via BaseModel — subscriptions are queried
 * by explicit tenant_id in the service layer.
 */
class SubscriptionModel extends Model
{
    protected $table      = 'subscriptions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'tenant_id', 'plan_id', 'billing_cycle', 'status',
        'starts_at', 'renews_at', 'expires_at', 'grace_ends_at',
        'trial_ends_at', 'cancelled_at',
    ];

    /**
     * Get the current active subscription for a tenant.
     * "Active" = not cancelled or expired — includes trial, active, grace, suspended.
     */
    public function getActiveForTenant(int $tenantId): ?object
    {
        return $this->where('tenant_id', $tenantId)
                    ->whereNotIn('status', ['cancelled', 'expired'])
                    ->orderBy('id', 'DESC')
                    ->first();
    }

    /**
     * Get all subscriptions for a tenant (history).
     */
    public function getAllForTenant(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
                    ->orderBy('id', 'DESC')
                    ->findAll();
    }
}
