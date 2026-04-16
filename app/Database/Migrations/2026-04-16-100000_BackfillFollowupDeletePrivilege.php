<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillFollowupDeletePrivilege extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $existing = $this->db->table('privileges')->where('code', 'followups.delete')->get()->getRow();
        if ($existing) {
            $privilegeId = (int) $existing->id;
            $this->db->table('privileges')->where('id', $privilegeId)->update([
                'name' => 'Delete Followups',
                'module' => 'followups',
                'updated_at' => $now,
            ]);
        } else {
            $this->db->table('privileges')->insert([
                'code' => 'followups.delete',
                'name' => 'Delete Followups',
                'module' => 'followups',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $privilegeId = (int) $this->db->insertID();
        }

        $roleCodes = ['tenant_owner', 'tenant_admin', 'branch_manager', 'counsellor'];
        $roles = $this->db->table('user_roles')->select('id, code')->whereIn('code', $roleCodes)->get()->getResult();

        foreach ($roles as $role) {
            $exists = $this->db->table('role_privileges')
                ->where('role_id', (int) $role->id)
                ->where('privilege_id', $privilegeId)
                ->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $this->db->table('role_privileges')->insert([
                'role_id' => (int) $role->id,
                'privilege_id' => $privilegeId,
                'created_at' => $now,
            ]);
        }
    }

    public function down()
    {
        $row = $this->db->table('privileges')->select('id')->where('code', 'followups.delete')->get()->getRow();
        if (! $row) {
            return;
        }

        $privilegeId = (int) $row->id;
        $this->db->table('role_privileges')->where('privilege_id', $privilegeId)->delete();
        $this->db->table('privileges')->where('id', $privilegeId)->delete();
    }
}
