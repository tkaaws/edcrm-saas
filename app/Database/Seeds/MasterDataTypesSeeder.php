<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MasterDataTypesSeeder extends Seeder
{
    public function run()
    {
        $rows = [
            ['code' => 'enquiry_source', 'name' => 'Enquiry Source', 'module_code' => 'enquiries', 'allow_tenant_entries' => 1, 'allow_tenant_hide_platform_values' => 1, 'strict_reporting_catalog' => 0, 'supports_hierarchy' => 0, 'sort_order' => 10],
            ['code' => 'lead_qualification', 'name' => 'Lead Qualification', 'module_code' => 'enquiries', 'allow_tenant_entries' => 1, 'allow_tenant_hide_platform_values' => 1, 'strict_reporting_catalog' => 0, 'supports_hierarchy' => 0, 'sort_order' => 20],
            ['code' => 'followup_status', 'name' => 'Follow-up Status', 'module_code' => 'enquiries', 'allow_tenant_entries' => 1, 'allow_tenant_hide_platform_values' => 1, 'strict_reporting_catalog' => 0, 'supports_hierarchy' => 0, 'sort_order' => 30],
            ['code' => 'mode_of_communication', 'name' => 'Mode of Communication', 'module_code' => 'enquiries', 'allow_tenant_entries' => 1, 'allow_tenant_hide_platform_values' => 1, 'strict_reporting_catalog' => 0, 'supports_hierarchy' => 0, 'sort_order' => 40],
            ['code' => 'enquiry_lost_reason', 'name' => 'Enquiry Lost Reason', 'module_code' => 'enquiries', 'allow_tenant_entries' => 1, 'allow_tenant_hide_platform_values' => 1, 'strict_reporting_catalog' => 0, 'supports_hierarchy' => 0, 'sort_order' => 50],
            ['code' => 'enquiry_closure_reason', 'name' => 'Enquiry Closure Reason', 'module_code' => 'enquiries', 'allow_tenant_entries' => 1, 'allow_tenant_hide_platform_values' => 0, 'strict_reporting_catalog' => 0, 'supports_hierarchy' => 0, 'sort_order' => 60],
            ['code' => 'purpose_category', 'name' => 'Purpose Category', 'module_code' => 'enquiries', 'allow_tenant_entries' => 1, 'allow_tenant_hide_platform_values' => 1, 'strict_reporting_catalog' => 0, 'supports_hierarchy' => 0, 'sort_order' => 70],
            ['code' => 'course', 'name' => 'Course', 'module_code' => 'crm_core', 'allow_tenant_entries' => 1, 'allow_tenant_hide_platform_values' => 0, 'strict_reporting_catalog' => 0, 'supports_hierarchy' => 0, 'sort_order' => 80],
        ];

        foreach ($rows as $row) {
            $existing = $this->db->table('master_data_types')
                                 ->where('code', $row['code'])
                                 ->get()
                                 ->getRow();

            if ($existing) {
                $this->db->table('master_data_types')
                         ->where('id', $existing->id)
                         ->update($row + ['updated_at' => date('Y-m-d H:i:s')]);
                continue;
            }

            $this->db->table('master_data_types')->insert($row + [
                'status' => 'active',
                'allow_platform_entries' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
