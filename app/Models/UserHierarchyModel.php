<?php

namespace App\Models;

class UserHierarchyModel extends BaseModel
{
    protected $table      = 'user_hierarchy';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'user_id',
        'manager_user_id',
        'acting_manager_user_id',
        'created_by',
        'updated_by',
    ];

    public function findByUser(int $userId): ?object
    {
        return $this->where('user_id', $userId)->first();
    }

    public function upsertForUser(int $tenantId, int $userId, ?int $managerUserId, ?int $actingManagerUserId = null): void
    {
        $existing = $this->findByUser($userId);

        $data = [
            'tenant_id'              => $tenantId,
            'user_id'                => $userId,
            'manager_user_id'        => $managerUserId,
            'acting_manager_user_id' => $actingManagerUserId,
        ];

        if ($existing) {
            $this->updateWithActor((int) $existing->id, $data);
            return;
        }

        $this->insertWithActor($data);
    }
}
