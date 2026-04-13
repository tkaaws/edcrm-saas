<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolePrivileges extends Migration
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
            'role_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'privilege_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['role_id', 'privilege_id']);
        $this->forge->addForeignKey('role_id', 'tenant_roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('privilege_id', 'privileges', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('role_privileges', true);
    }

    public function down()
    {
        $this->forge->dropTable('role_privileges', true);
    }
}
