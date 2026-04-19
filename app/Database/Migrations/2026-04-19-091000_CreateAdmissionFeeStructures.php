<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdmissionFeeStructures extends Migration
{
    public function up()
    {
        $this->createFeeStructuresTable();
        $this->createFeeStructureItemsTable();
        $this->addFeeStructureIdToSnapshots();
    }

    public function down()
    {
        if ($this->db->tableExists('admission_fee_snapshots') && $this->db->fieldExists('fee_structure_id', 'admission_fee_snapshots')) {
            $this->forge->dropColumn('admission_fee_snapshots', 'fee_structure_id');
        }

        $this->forge->dropTable('fee_structure_items', true);
        $this->forge->dropTable('fee_structures', true);
    }

    protected function createFeeStructuresTable(): void
    {
        if ($this->db->tableExists('fee_structures')) {
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
            'course_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'default_installment_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ],
            'default_installment_gap_days' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 30,
            ],
            'total_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default' => 'active',
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
        $this->forge->addKey(['tenant_id', 'course_id', 'status']);
        $this->forge->addUniqueKey(['tenant_id', 'course_id', 'name']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('course_id', 'master_data_values', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('fee_structures', true);
    }

    protected function createFeeStructureItemsTable(): void
    {
        if ($this->db->tableExists('fee_structure_items')) {
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
            'fee_structure_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'fee_head_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'fee_head_code' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'allow_discount' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'display_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
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
        $this->forge->addKey(['fee_structure_id', 'display_order']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('fee_structure_id', 'fee_structures', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('fee_structure_items', true);
    }

    protected function addFeeStructureIdToSnapshots(): void
    {
        if (! $this->db->tableExists('admission_fee_snapshots') || $this->db->fieldExists('fee_structure_id', 'admission_fee_snapshots')) {
            return;
        }

        $this->forge->addColumn('admission_fee_snapshots', [
            'fee_structure_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'after' => 'admission_id',
            ],
        ]);
    }
}
