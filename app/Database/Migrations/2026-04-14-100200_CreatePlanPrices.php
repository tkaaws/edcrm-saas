<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlanPrices extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'plan_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'billing_cycle' => [
                // 'monthly' | 'yearly'
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'currency_code' => [
                'type'       => 'CHAR',
                'constraint' => 3,
                'default'    => 'INR',
            ],
            'price_amount' => [
                // stored in paise (INR) or smallest unit — multiply by 100
                'type'       => 'BIGINT',
                'constraint' => 20,
                'default'    => 0,
            ],
            'billing_period_months' => [
                // 1 for monthly, 12 for yearly
                'type'       => 'TINYINT',
                'constraint' => 3,
                'default'    => 1,
            ],
            'status' => [
                // 'active' | 'archived'
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
        $this->forge->addKey('plan_id');
        $this->forge->addUniqueKey(['plan_id', 'billing_cycle', 'currency_code']);
        $this->forge->createTable('plan_prices', true);
    }

    public function down()
    {
        $this->forge->dropTable('plan_prices', true);
    }
}
