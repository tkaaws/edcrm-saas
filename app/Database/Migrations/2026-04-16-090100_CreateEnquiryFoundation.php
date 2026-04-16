<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEnquiryFoundation extends Migration
{
    public function up()
    {
        $this->createEnquiriesTable();
        $this->createEnquiryFollowupsTable();
        $this->createEnquiryAssignmentHistoryTable();
        $this->createEnquiryStatusLogsTable();
    }

    public function down()
    {
        $this->forge->dropTable('enquiry_status_logs', true);
        $this->forge->dropTable('enquiry_assignment_history', true);
        $this->forge->dropTable('enquiry_followups', true);
        $this->forge->dropTable('enquiries', true);
    }

    protected function createEnquiriesTable(): void
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
            'branch_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'owner_user_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'assigned_on' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'student_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'mobile' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'whatsapp_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'source_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'college_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'qualification_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'primary_course_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'lifecycle_status' => [
                'type'       => 'ENUM',
                'constraint' => ['new', 'active', 'closed', 'admitted'],
                'default'    => 'new',
            ],
            'closed_reason_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'closed_remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'last_followup_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'next_followup_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'closed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'closed_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'admitted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
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
        $this->forge->addKey(['tenant_id', 'lifecycle_status']);
        $this->forge->addKey(['tenant_id', 'branch_id', 'owner_user_id']);
        $this->forge->addKey(['tenant_id', 'next_followup_at']);
        $this->forge->addKey(['tenant_id', 'mobile']);
        $this->forge->addKey(['tenant_id', 'college_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'tenant_branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('owner_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('source_id', 'master_data_values', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('college_id', 'colleges', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('qualification_id', 'master_data_values', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('primary_course_id', 'master_data_values', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('closed_reason_id', 'master_data_values', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('closed_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('enquiries', true);
    }

    protected function createEnquiryFollowupsTable(): void
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
            'enquiry_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'branch_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'owner_user_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'communication_type_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'followup_outcome_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'next_followup_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_system_generated' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'created_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
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
        $this->forge->addKey(['tenant_id', 'enquiry_id', 'created_at']);
        $this->forge->addKey(['tenant_id', 'next_followup_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('enquiry_id', 'enquiries', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'tenant_branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('owner_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('communication_type_id', 'master_data_values', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('followup_outcome_id', 'master_data_values', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('enquiry_followups', true);
    }

    protected function createEnquiryAssignmentHistoryTable(): void
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
            'enquiry_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'from_branch_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'to_branch_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'from_user_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'to_user_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'assigned_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'assignment_type' => [
                'type'       => 'ENUM',
                'constraint' => ['manual', 'bulk_manual', 'system'],
                'default'    => 'manual',
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'bulk_batch_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'assigned_on' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
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
        $this->forge->addKey(['tenant_id', 'enquiry_id', 'created_at']);
        $this->forge->addKey(['tenant_id', 'bulk_batch_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('enquiry_id', 'enquiries', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('from_branch_id', 'tenant_branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('to_branch_id', 'tenant_branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('from_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('to_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('assigned_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('enquiry_assignment_history', true);
    }

    protected function createEnquiryStatusLogsTable(): void
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
            'enquiry_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'from_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'to_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'changed_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
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
        $this->forge->addKey(['tenant_id', 'enquiry_id', 'created_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('enquiry_id', 'enquiries', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('changed_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('enquiry_status_logs', true);
    }
}
