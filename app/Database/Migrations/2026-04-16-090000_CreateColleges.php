<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateColleges extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'city_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'state_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
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
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->addKey(['tenant_id', 'name']);
        $this->forge->addKey(['tenant_id', 'state_name', 'city_name']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('colleges', true);

        $now = date('Y-m-d H:i:s');
        $tenants = $this->db->table('tenants')->select('id')->get()->getResult();
        foreach ($tenants as $tenant) {
            $exists = $this->db->table('colleges')
                ->where('tenant_id', (int) $tenant->id)
                ->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $this->db->table('colleges')->insert([
                'tenant_id'   => (int) $tenant->id,
                'name'        => 'Test College',
                'city_name'   => 'Pune',
                'state_name'  => 'Maharashtra',
                'status'      => 'active',
                'created_by'  => null,
                'updated_by'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('colleges', true);
    }
}
