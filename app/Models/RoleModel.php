<?php

namespace App\Models;

/**
 * RoleModel
 *
 * Tenant-scoped. Manages tenant-owned roles.
 */
class RoleModel extends BaseModel
{
    protected $table      = 'tenant_roles';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'name',
        'code',
        'is_system',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $validationRules = [
        'name'   => 'required|min_length[2]|max_length[255]',
        'code'   => 'required|min_length[2]|max_length[100]',
        'status' => 'required|in_list[active,inactive]',
    ];

    public function getActiveRoles(): array
    {
        return $this->where('status', 'active')->findAll();
    }

    public function findByCode(string $code): ?object
    {
        return $this->where('code', $code)->first();
    }

    /**
     * System roles cannot be deleted — only deactivated.
     */
    public function canDelete(int $roleId): bool
    {
        $role = $this->find($roleId);
        return $role && ! $role->is_system;
    }
}
