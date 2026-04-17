<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEnquiryActionPrivileges extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $privileges = [
            'enquiries.update_contact_info' => 'Update Enquiry Contact Info',
            'enquiries.update_college_info' => 'Update Enquiry College Info',
        ];

        $privilegeIds = [];

        foreach ($privileges as $code => $name) {
            $existing = $this->db->table('privileges')->where('code', $code)->get()->getRow();

            if ($existing) {
                $privilegeIds[$code] = (int) $existing->id;
                $this->db->table('privileges')->where('id', (int) $existing->id)->update([
                    'name' => $name,
                    'module' => 'enquiries',
                    'updated_at' => $now,
                ]);
                continue;
            }

            $this->db->table('privileges')->insert([
                'code' => $code,
                'name' => $name,
                'module' => 'enquiries',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $privilegeIds[$code] = (int) $this->db->insertID();
        }

        $editPrivilege = $this->db->table('privileges')->select('id')->where('code', 'enquiries.edit')->get()->getRow();
        if (! $editPrivilege) {
            return;
        }

        $roleIds = $this->db->table('role_privileges')
            ->select('role_id')
            ->where('privilege_id', (int) $editPrivilege->id)
            ->get()
            ->getResult();

        foreach ($roleIds as $role) {
            foreach ($privilegeIds as $privilegeId) {
                $exists = $this->db->table('role_privileges')
                    ->where('role_id', (int) $role->role_id)
                    ->where('privilege_id', $privilegeId)
                    ->countAllResults();

                if ($exists > 0) {
                    continue;
                }

                $this->db->table('role_privileges')->insert([
                    'role_id' => (int) $role->role_id,
                    'privilege_id' => $privilegeId,
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $rows = $this->db->table('privileges')
            ->select('id')
            ->whereIn('code', ['enquiries.update_contact_info', 'enquiries.update_college_info'])
            ->get()
            ->getResult();

        if ($rows === []) {
            return;
        }

        $ids = array_map(static fn($row): int => (int) $row->id, $rows);
        $this->db->table('role_privileges')->whereIn('privilege_id', $ids)->delete();
        $this->db->table('privileges')->whereIn('id', $ids)->delete();
    }
}
