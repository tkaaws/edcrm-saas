<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillingEvents extends Migration
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
            'subscription_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'event_type' => [
                // subscription_created | status_changed | plan_changed | add_on_added | override_set
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'from_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'to_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'performed_by' => [
                // user_id who triggered the event (null = system/cron)
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('subscription_id');
        $this->forge->createTable('billing_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('billing_events', true);
    }
}
