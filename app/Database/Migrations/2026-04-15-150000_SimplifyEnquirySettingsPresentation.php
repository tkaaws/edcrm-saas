<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SimplifyEnquirySettingsPresentation extends Migration
{
    public function up()
    {
        $this->db->table('setting_definitions')
            ->where('key', 'enquiry.visibility.allow_cross_branch_transfer')
            ->update([
                'is_active' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down()
    {
        $this->db->table('setting_definitions')
            ->where('key', 'enquiry.visibility.allow_cross_branch_transfer')
            ->update([
                'is_active' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
