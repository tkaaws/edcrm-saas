<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantWhatsappConfigs extends Migration
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
            'provider_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'api_base_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'api_key_encrypted' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sender_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'is_default' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'active',
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
        $this->forge->addKey('tenant_id');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tenant_whatsapp_configs', true);
    }

    public function down()
    {
        $this->forge->dropTable('tenant_whatsapp_configs', true);
    }
}
