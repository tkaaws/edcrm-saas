<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantSettings extends Migration
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
            'branding_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'logo_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'favicon_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'default_timezone' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'UTC',
            ],
            'default_currency_code' => [
                'type'       => 'CHAR',
                'constraint' => 3,
                'default'    => 'USD',
            ],
            'locale_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'en',
            ],
            'branch_visibility_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'restricted',
            ],
            'enquiry_visibility_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'restricted',
            ],
            'admission_visibility_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'restricted',
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
        $this->forge->addUniqueKey('tenant_id');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tenant_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('tenant_settings', true);
    }
}
