<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptionFeatureOverrides extends Migration
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
            'feature_code' => [
                // module or limit code being overridden
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'is_enabled' => [
                // for module overrides: 1=force-on, 0=force-off, null=use plan default
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
            ],
            'limit_value' => [
                // for limit overrides: override the plan limit (-1 = unlimited, null = use plan)
                'type'       => 'INT',
                'constraint' => 11,
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
        $this->forge->addKey('subscription_id');
        $this->forge->addUniqueKey(['subscription_id', 'feature_code']);
        $this->forge->createTable('subscription_feature_overrides', true);
    }

    public function down()
    {
        $this->forge->dropTable('subscription_feature_overrides', true);
    }
}
