<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillImpersonationPrivilegeAndAccessScopes extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $privilege = $this->db->table('privileges')
            ->where('code', 'users.impersonate')
            ->get()
            ->getRow();

        if (! $privilege) {
            $this->db->table('privileges')->insert([
                'code'       => 'users.impersonate',
                'name'       => 'Impersonate Users',
                'module'     => 'users',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $privilegeId = (int) $this->db->insertID();
        } else {
            $privilegeId = (int) $privilege->id;
        }

        $roles = $this->db->table('user_roles')
            ->select('id, code')
            ->whereIn('code', ['tenant_owner', 'tenant_admin', 'branch_manager'])
            ->get()
            ->getResult();

        foreach ($roles as $role) {
            $exists = $this->db->table('role_privileges')
                ->where('role_id', (int) $role->id)
                ->where('privilege_id', $privilegeId)
                ->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $this->db->table('role_privileges')->insert([
                'role_id'      => (int) $role->id,
                'privilege_id' => $privilegeId,
                'created_at'   => $now,
            ]);
        }

        $scopeMap = [
            'tenant_owner' => ['data_scope' => 'tenant', 'manage_scope' => 'tenant', 'hierarchy_mode' => 'branch_flat'],
            'tenant_admin' => ['data_scope' => 'tenant', 'manage_scope' => 'tenant', 'hierarchy_mode' => 'branch_flat'],
            'branch_manager' => ['data_scope' => 'branch', 'manage_scope' => 'branch', 'hierarchy_mode' => 'hierarchy'],
            'operations' => ['data_scope' => 'branch', 'manage_scope' => 'none', 'hierarchy_mode' => 'branch_flat'],
            'accounts' => ['data_scope' => 'branch', 'manage_scope' => 'none', 'hierarchy_mode' => 'branch_flat'],
            'placement' => ['data_scope' => 'branch', 'manage_scope' => 'none', 'hierarchy_mode' => 'branch_flat'],
            'faculty' => ['data_scope' => 'branch', 'manage_scope' => 'none', 'hierarchy_mode' => 'branch_flat'],
            'support_agent' => ['data_scope' => 'branch', 'manage_scope' => 'none', 'hierarchy_mode' => 'branch_flat'],
            'counsellor' => ['data_scope' => 'self', 'manage_scope' => 'none', 'hierarchy_mode' => 'hierarchy'],
        ];

        foreach ($scopeMap as $roleCode => $scope) {
            $this->db->query(
                'UPDATE users u
                 INNER JOIN user_roles r ON r.id = u.role_id
                 SET u.data_scope = ?, u.manage_scope = ?, u.hierarchy_mode = ?, u.allow_impersonation = 1
                 WHERE r.code = ? AND (u.data_scope IS NULL OR u.data_scope = \'self\') AND (u.manage_scope IS NULL OR u.manage_scope = \'none\')',
                [$scope['data_scope'], $scope['manage_scope'], $scope['hierarchy_mode'], $roleCode]
            );
        }
    }

    public function down()
    {
        $privilege = $this->db->table('privileges')
            ->where('code', 'users.impersonate')
            ->get()
            ->getRow();

        if ($privilege) {
            $this->db->table('role_privileges')->where('privilege_id', (int) $privilege->id)->delete();
            $this->db->table('privileges')->where('id', (int) $privilege->id)->delete();
        }
    }
}
