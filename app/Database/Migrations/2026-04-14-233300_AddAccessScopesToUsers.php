<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccessScopesToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'data_scope' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'self',
                'after'      => 'designation',
            ],
            'manage_scope' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'none',
                'after'      => 'data_scope',
            ],
            'hierarchy_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'hierarchy',
                'after'      => 'manage_scope',
            ],
            'allow_impersonation' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'after'   => 'hierarchy_mode',
            ],
        ];

        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', [
            'data_scope',
            'manage_scope',
            'hierarchy_mode',
            'allow_impersonation',
        ]);
    }
}
