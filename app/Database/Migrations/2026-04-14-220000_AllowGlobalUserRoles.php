<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AllowGlobalUserRoles extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE user_roles MODIFY tenant_id BIGINT(20) UNSIGNED NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE user_roles MODIFY tenant_id BIGINT(20) UNSIGNED NOT NULL');
    }
}
