<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantMasterDataOverrides extends Migration
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
            'master_data_value_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'is_visible' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'sort_order_override' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'label_override' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'master_data_value_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('master_data_value_id', 'master_data_values', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tenant_master_data_overrides', true);
    }

    public function down()
    {
        $this->forge->dropTable('tenant_master_data_overrides', true);
    }
}
