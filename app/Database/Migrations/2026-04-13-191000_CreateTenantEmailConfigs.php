<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantEmailConfigs extends Migration
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
            'from_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'from_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'host' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'port' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'password_encrypted' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'encryption' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
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
        $this->forge->createTable('tenant_email_configs', true);
    }

    public function down()
    {
        $this->forge->dropTable('tenant_email_configs', true);
    }
}
