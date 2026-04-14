<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptions extends Migration
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
            'plan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'billing_cycle' => [
                // 'monthly' | 'yearly'
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'monthly',
            ],
            'status' => [
                // trial | active | grace | suspended | cancelled | expired
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'trial',
            ],
            'starts_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'renews_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'grace_ends_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'trial_ends_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'cancelled_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('status');
        $this->forge->createTable('subscriptions', true);
    }

    public function down()
    {
        $this->forge->dropTable('subscriptions', true);
    }
}
