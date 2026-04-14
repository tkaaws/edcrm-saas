<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlanLimits extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'plan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'limit_code' => [
                // references feature_catalog.code (category = 'limit')
                // e.g. 'max_users', 'max_branches'
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'limit_value' => [
                // -1 = unlimited
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => -1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('plan_id');
        $this->forge->addUniqueKey(['plan_id', 'limit_code']);
        $this->forge->createTable('plan_limits', true);
    }

    public function down()
    {
        $this->forge->dropTable('plan_limits', true);
    }
}
