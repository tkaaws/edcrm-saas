<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlanFeatures extends Migration
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
            'feature_code' => [
                // references feature_catalog.code (category = 'module')
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'is_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->addUniqueKey(['plan_id', 'feature_code']);
        $this->forge->createTable('plan_features', true);
    }

    public function down()
    {
        $this->forge->dropTable('plan_features', true);
    }
}
