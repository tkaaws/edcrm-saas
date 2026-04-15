<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMasterDataTypes extends Migration
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
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'module_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
            ],
            'allow_platform_entries' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'allow_tenant_entries' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'allow_tenant_hide_platform_values' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'strict_reporting_catalog' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'supports_hierarchy' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
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
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('module_code');
        $this->forge->addKey('status');
        $this->forge->createTable('master_data_types', true);
    }

    public function down()
    {
        $this->forge->dropTable('master_data_types', true);
    }
}
