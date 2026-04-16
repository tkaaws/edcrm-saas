<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillCollegeAndEnquiryPrivileges extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $privileges = [
            ['code' => 'colleges.view', 'name' => 'View Colleges', 'module' => 'colleges'],
            ['code' => 'colleges.create', 'name' => 'Create Colleges', 'module' => 'colleges'],
            ['code' => 'colleges.edit', 'name' => 'Edit Colleges', 'module' => 'colleges'],
            ['code' => 'colleges.delete', 'name' => 'Delete Colleges', 'module' => 'colleges'],
            ['code' => 'enquiries.view_mobile_number', 'name' => 'View Enquiry Mobile Numbers', 'module' => 'enquiries'],
            ['code' => 'enquiries.close', 'name' => 'Close Enquiries', 'module' => 'enquiries'],
            ['code' => 'enquiries.reopen', 'name' => 'Reopen Enquiries', 'module' => 'enquiries'],
            ['code' => 'enquiries.convert_to_admission', 'name' => 'Convert Enquiries to Admission', 'module' => 'enquiries'],
            ['code' => 'enquiries.view_created_on', 'name' => 'View Enquiry Created On', 'module' => 'enquiries'],
            ['code' => 'enquiries.view_modified_on', 'name' => 'View Enquiry Modified On', 'module' => 'enquiries'],
            ['code' => 'enquiries.view_created_by', 'name' => 'View Enquiry Created By', 'module' => 'enquiries'],
            ['code' => 'enquiries.view_modified_by', 'name' => 'View Enquiry Modified By', 'module' => 'enquiries'],
            ['code' => 'enquiries.reassign_in_edit', 'name' => 'Reassign Enquiries in Edit', 'module' => 'enquiries'],
            ['code' => 'enquiries.expired_assign', 'name' => 'Assign Expired Enquiries', 'module' => 'enquiries'],
            ['code' => 'enquiries.closed_assign', 'name' => 'Assign Closed Enquiries', 'module' => 'enquiries'],
            ['code' => 'enquiries.assignment_history_view', 'name' => 'View Enquiry Assignment History', 'module' => 'enquiries'],
            ['code' => 'enquiries.activity_view', 'name' => 'View Enquiry Activity', 'module' => 'enquiries'],
        ];

        foreach ($privileges as $privilege) {
            $existing = $this->db->table('privileges')->where('code', $privilege['code'])->get()->getRow();

            if ($existing) {
                $this->db->table('privileges')
                    ->where('id', (int) $existing->id)
                    ->update($privilege + ['updated_at' => $now]);
                continue;
            }

            $this->db->table('privileges')->insert($privilege + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->attachSystemRolePrivileges($now);
    }

    public function down()
    {
        $codes = [
            'colleges.view',
            'colleges.create',
            'colleges.edit',
            'colleges.delete',
            'enquiries.view_mobile_number',
            'enquiries.close',
            'enquiries.reopen',
            'enquiries.convert_to_admission',
            'enquiries.view_created_on',
            'enquiries.view_modified_on',
            'enquiries.view_created_by',
            'enquiries.view_modified_by',
            'enquiries.reassign_in_edit',
            'enquiries.expired_assign',
            'enquiries.closed_assign',
            'enquiries.assignment_history_view',
            'enquiries.activity_view',
        ];

        $ids = $this->db->table('privileges')->select('id')->whereIn('code', $codes)->get()->getResultArray();
        if ($ids === []) {
            return;
        }

        $privilegeIds = array_map(static fn(array $row): int => (int) $row['id'], $ids);
        $this->db->table('role_privileges')->whereIn('privilege_id', $privilegeIds)->delete();
        $this->db->table('privileges')->whereIn('id', $privilegeIds)->delete();
    }

    protected function attachSystemRolePrivileges(string $now): void
    {
        $roleCodes = [
            'tenant_owner' => [
                'colleges.view', 'colleges.create', 'colleges.edit', 'colleges.delete',
                'enquiries.view_mobile_number', 'enquiries.close', 'enquiries.reopen',
                'enquiries.convert_to_admission', 'enquiries.view_created_on',
                'enquiries.view_modified_on', 'enquiries.view_created_by',
                'enquiries.view_modified_by', 'enquiries.reassign_in_edit',
                'enquiries.expired_assign', 'enquiries.closed_assign',
                'enquiries.assignment_history_view', 'enquiries.activity_view',
            ],
            'tenant_admin' => [
                'colleges.view', 'colleges.create', 'colleges.edit', 'colleges.delete',
                'enquiries.view_mobile_number', 'enquiries.close', 'enquiries.reopen',
                'enquiries.convert_to_admission', 'enquiries.view_created_on',
                'enquiries.view_modified_on', 'enquiries.view_created_by',
                'enquiries.view_modified_by', 'enquiries.reassign_in_edit',
                'enquiries.expired_assign', 'enquiries.closed_assign',
                'enquiries.assignment_history_view', 'enquiries.activity_view',
            ],
            'branch_manager' => [
                'colleges.view', 'colleges.create', 'colleges.edit',
                'enquiries.view_mobile_number', 'enquiries.close', 'enquiries.reopen',
                'enquiries.convert_to_admission', 'enquiries.view_created_on',
                'enquiries.view_modified_on', 'enquiries.view_created_by',
                'enquiries.view_modified_by', 'enquiries.reassign_in_edit',
                'enquiries.expired_assign', 'enquiries.closed_assign',
                'enquiries.assignment_history_view', 'enquiries.activity_view',
            ],
            'counsellor' => [
                'enquiries.view_mobile_number',
                'enquiries.close',
                'enquiries.convert_to_admission',
            ],
        ];

        $roles = $this->db->table('user_roles')
            ->select('id, code')
            ->whereIn('code', array_keys($roleCodes))
            ->get()
            ->getResult();

        $codes = array_values(array_unique(array_merge(...array_values($roleCodes))));
        $privileges = $this->db->table('privileges')
            ->select('id, code')
            ->whereIn('code', $codes)
            ->get()
            ->getResult();

        $privilegeMap = [];
        foreach ($privileges as $privilege) {
            $privilegeMap[$privilege->code] = (int) $privilege->id;
        }

        foreach ($roles as $role) {
            foreach ($roleCodes[$role->code] as $code) {
                $privilegeId = $privilegeMap[$code] ?? null;
                if (! $privilegeId) {
                    continue;
                }

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
        }
    }
}
