<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActivityReportPrivileges extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $privileges = [
            ['code' => 'reports.activity_self', 'name' => 'View My Activity Report', 'module' => 'reports'],
            ['code' => 'reports.activity_team', 'name' => 'View Team Activity Report', 'module' => 'reports'],
        ];

        foreach ($privileges as $privilege) {
            $existing = $this->db->table('privileges')->where('code', $privilege['code'])->get()->getRow();
            if ($existing) {
                continue;
            }

            $this->db->table('privileges')->insert($privilege + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $codeToId = [];
        $rows = $this->db->table('privileges')
            ->select('id, code')
            ->whereIn('code', ['reports.view', 'reports.advanced', 'reports.activity_self', 'reports.activity_team'])
            ->get()
            ->getResult();

        foreach ($rows as $row) {
            $codeToId[$row->code] = (int) $row->id;
        }

        if (isset($codeToId['reports.view'], $codeToId['reports.activity_self'])) {
            $roles = $this->db->table('role_privileges')
                ->select('role_id')
                ->where('privilege_id', $codeToId['reports.view'])
                ->get()
                ->getResult();

            foreach ($roles as $role) {
                $exists = $this->db->table('role_privileges')
                    ->where('role_id', (int) $role->role_id)
                    ->where('privilege_id', $codeToId['reports.activity_self'])
                    ->countAllResults();

                if (! $exists) {
                    $this->db->table('role_privileges')->insert([
                        'role_id'      => (int) $role->role_id,
                        'privilege_id' => $codeToId['reports.activity_self'],
                        'created_at'   => $now,
                    ]);
                }
            }
        }

        if (isset($codeToId['reports.advanced'], $codeToId['reports.activity_team'])) {
            $roles = $this->db->table('role_privileges')
                ->select('role_id')
                ->where('privilege_id', $codeToId['reports.advanced'])
                ->get()
                ->getResult();

            foreach ($roles as $role) {
                $exists = $this->db->table('role_privileges')
                    ->where('role_id', (int) $role->role_id)
                    ->where('privilege_id', $codeToId['reports.activity_team'])
                    ->countAllResults();

                if (! $exists) {
                    $this->db->table('role_privileges')->insert([
                        'role_id'      => (int) $role->role_id,
                        'privilege_id' => $codeToId['reports.activity_team'],
                        'created_at'   => $now,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $codes = ['reports.activity_self', 'reports.activity_team'];
        $privilegeRows = $this->db->table('privileges')->select('id')->whereIn('code', $codes)->get()->getResult();

        foreach ($privilegeRows as $row) {
            $this->db->table('role_privileges')->where('privilege_id', (int) $row->id)->delete();
        }

        $this->db->table('privileges')->whereIn('code', $codes)->delete();
    }
}
