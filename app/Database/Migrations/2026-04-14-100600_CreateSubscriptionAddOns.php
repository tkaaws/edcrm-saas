<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptionAddOns extends Migration
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
            'subscription_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'code' => [
                // feature_code or limit_code being added on top of base plan
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'quantity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'unit_price_amount' => [
                // stored in smallest currency unit (paise)
                'type'       => 'BIGINT',
                'constraint' => 20,
                'default'    => 0,
            ],
            'currency_code' => [
                'type'       => 'CHAR',
                'constraint' => 3,
                'default'    => 'INR',
            ],
            'status' => [
                // 'active' | 'cancelled'
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'active',
            ],
            'starts_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ends_at' => [
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
        $this->forge->addKey('subscription_id');
        $this->forge->createTable('subscription_add_ons', true);
    }

    public function down()
    {
        $this->forge->dropTable('subscription_add_ons', true);
    }
}
