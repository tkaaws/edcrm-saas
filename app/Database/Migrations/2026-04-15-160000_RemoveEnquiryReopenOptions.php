<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveEnquiryReopenOptions extends Migration
{
    public function up()
    {
        $this->db->table('setting_definitions')
            ->whereIn('key', [
                'enquiry.lifecycle.reopen_expired_allowed',
                'enquiry.lifecycle.reopen_closed_allowed',
            ])
            ->update([
                'is_active' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down()
    {
        $this->db->table('setting_definitions')
            ->whereIn('key', [
                'enquiry.lifecycle.reopen_expired_allowed',
                'enquiry.lifecycle.reopen_closed_allowed',
            ])
            ->update([
                'is_active' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
