<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettingDefinitions extends Migration
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
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'scope' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'tenant',
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'module_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'crm_core',
            ],
            'value_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'string',
            ],
            'allowed_options_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'default_value_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_sensitive' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
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
        $this->forge->addUniqueKey('key');
        $this->forge->addKey(['scope', 'category']);
        $this->forge->createTable('setting_definitions', true);
    }

    public function down()
    {
        $this->forge->dropTable('setting_definitions', true);
    }
}
