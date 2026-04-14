<?php

namespace App\Models;

/**
 * RoleModel
 *
 * Tenant-scoped. Manages tenant-owned roles.
 */
class RoleModel extends BaseModel
{
    protected $table      = 'user_roles';
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
     * Return tenant roles for the admin grid.
     *
     * @return array<int, object>
     */
    public function getAdminGrid(int $tenantId): array
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->orderBy('is_system', 'DESC')
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    public function codeExistsForTenant(string $code, int $tenantId, ?int $ignoreRoleId = null): bool
    {
        $builder = $this->withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->where('code', $code);

        if ($ignoreRoleId !== null) {
            $builder->where('id !=', $ignoreRoleId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * @param list<int> $privilegeIds
     */
    public function syncPrivileges(int $roleId, array $privilegeIds): void
    {
        $this->db->table('role_privileges')->where('role_id', $roleId)->delete();

        if ($privilegeIds === []) {
            return;
        }

        $rows = [];
        $now = date('Y-m-d H:i:s');

        foreach (array_unique($privilegeIds) as $privilegeId) {
            $rows[] = [
                'role_id'      => $roleId,
                'privilege_id' => $privilegeId,
                'created_at'   => $now,
            ];
        }

        $this->db->table('role_privileges')->insertBatch($rows);
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
