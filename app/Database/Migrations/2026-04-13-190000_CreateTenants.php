<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenants extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'draft',
            ],
            'legal_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'owner_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'owner_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'owner_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
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
            'country_code' => [
                'type'       => 'CHAR',
                'constraint' => 2,
                'null'       => true,
            ],
            'locale_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'en',
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
        $this->forge->addUniqueKey('slug');
        $this->forge->addUniqueKey('owner_email');
        $this->forge->createTable('tenants', true);
    }

    public function down()
    {
        $this->forge->dropTable('tenants', true);
    }
}
