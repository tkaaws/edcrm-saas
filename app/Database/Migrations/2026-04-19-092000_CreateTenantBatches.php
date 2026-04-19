<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantBatches extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tenant_batches')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'branch_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'starts_on' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'ends_on' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'capacity' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'active',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'updated_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
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
        $this->forge->addKey(['tenant_id', 'branch_id', 'status']);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'tenant_branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tenant_batches', true);
    }

    public function down()
    {
        $this->forge->dropTable('tenant_batches', true);
    }
}
