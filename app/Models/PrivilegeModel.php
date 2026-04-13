<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PrivilegeModel
 *
 * Platform-level. No tenant scope — privileges are global.
 */
class PrivilegeModel extends Model
{
    protected $table      = 'privileges';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'code',
        'name',
        'module',
        'description',
    ];

    /**
     * Get all privileges grouped by module.
     * Used for role management UI.
     */
    public function getAllGroupedByModule(): array
    {
        $privileges = $this->orderBy('module')->orderBy('code')->findAll();
        $grouped = [];
        foreach ($privileges as $p) {
            $grouped[$p->module][] = $p;
        }
        return $grouped;
    }

    /**
     * Get privilege IDs assigned to a role.
     */
    public function getPrivilegeIdsForRole(int $roleId): array
    {
        $rows = $this->db->table('role_privileges')
                         ->where('role_id', $roleId)
                         ->get()
                         ->getResultArray();
        return array_column($rows, 'privilege_id');
    }

    /**
     * Get privilege codes assigned to a role.
     * Used by PermissionService for fast in-memory checks.
     */
    public function getPrivilegeCodesForRole(int $roleId): array
    {
        $rows = $this->db->table('role_privileges rp')
                         ->join('privileges p', 'p.id = rp.privilege_id')
                         ->where('rp.role_id', $roleId)
                         ->select('p.code')
                         ->get()
                         ->getResultArray();
        return array_column($rows, 'code');
    }
}
