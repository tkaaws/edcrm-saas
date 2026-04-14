<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AllowGlobalUsers extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE users MODIFY tenant_id BIGINT(20) UNSIGNED NULL');
    }

    public function down()
    {
        $this->db->query('DELETE FROM users WHERE tenant_id IS NULL');
        $this->db->query('ALTER TABLE users MODIFY tenant_id BIGINT(20) UNSIGNED NOT NULL');
    }
}
