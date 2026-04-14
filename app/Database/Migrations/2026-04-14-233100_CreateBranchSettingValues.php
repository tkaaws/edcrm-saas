<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBranchSettingValues extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'branch_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'value_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'string',
            ],
            'created_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
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
        $this->forge->addUniqueKey(['branch_id', 'key']);
        $this->forge->addKey('tenant_id');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'tenant_branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('branch_setting_values', true);
    }

    public function down()
    {
        $this->forge->dropTable('branch_setting_values', true);
    }
}
