<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DeactivateEnquiryAssignmentSettings extends Migration
{
    public function up()
    {
        $this->db->table('setting_definitions')
            ->whereIn('key', [
                'enquiry.assignment.mode',
                'enquiry.assignment.reassign_allowed',
            ])
            ->update([
                'is_active'  => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down()
    {
        $this->db->table('setting_definitions')
            ->whereIn('key', [
                'enquiry.assignment.mode',
                'enquiry.assignment.reassign_allowed',
            ])
            ->update([
                'is_active'  => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
