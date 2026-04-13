<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantSettingValues extends Migration
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
        $this->forge->addUniqueKey(['tenant_id', 'key']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tenant_setting_values', true);
    }

    public function down()
    {
        $this->forge->dropTable('tenant_setting_values', true);
    }
}
