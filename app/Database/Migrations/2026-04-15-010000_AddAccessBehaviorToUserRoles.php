<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccessBehaviorToUserRoles extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('access_behavior', 'user_roles')) {
            $this->forge->addColumn('user_roles', [
                'access_behavior' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'hierarchy',
                    'after'      => 'code',
                ],
            ]);
        }

        $behaviorMap = [
            'platform_admin'  => 'tenant',
            'tenant_owner'    => 'tenant',
            'tenant_admin'    => 'tenant',
            'branch_manager'  => 'branch',
            'operations'      => 'branch',
            'accounts'        => 'branch',
            'placement'       => 'branch',
            'support_agent'   => 'branch',
            'counsellor'      => 'hierarchy',
            'faculty'         => 'hierarchy',
        ];

        foreach ($behaviorMap as $roleCode => $behavior) {
            $this->db->table('user_roles')
                ->where('code', $roleCode)
                ->set('access_behavior', $behavior)
                ->update();
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('access_behavior', 'user_roles')) {
            $this->forge->dropColumn('user_roles', 'access_behavior');
        }
    }
}
