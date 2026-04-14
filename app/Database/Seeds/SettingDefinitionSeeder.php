<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingDefinitionSeeder extends Seeder
{
    public function run()
    {
        $rows = [
            ['key' => 'tenant.profile.display_name', 'label' => 'Institute Display Name', 'scope' => 'tenant', 'category' => 'profile', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"EDCRM Tenant"', 'sort_order' => 10],
            ['key' => 'tenant.profile.legal_name', 'label' => 'Legal Name', 'scope' => 'tenant', 'category' => 'profile', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => null, 'sort_order' => 20],
            ['key' => 'tenant.profile.support_email', 'label' => 'Support Email', 'scope' => 'tenant', 'category' => 'profile', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => null, 'sort_order' => 30],

            ['key' => 'tenant.regional.timezone', 'label' => 'Timezone', 'scope' => 'tenant', 'category' => 'regional', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"UTC"', 'sort_order' => 10],
            ['key' => 'tenant.regional.currency', 'label' => 'Currency', 'scope' => 'tenant', 'category' => 'regional', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"USD"', 'sort_order' => 20],
            ['key' => 'tenant.regional.locale', 'label' => 'Locale', 'scope' => 'tenant', 'category' => 'regional', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"en"', 'sort_order' => 30],
            ['key' => 'tenant.regional.week_start_day', 'label' => 'Week Start Day', 'scope' => 'tenant', 'category' => 'regional', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"monday"', 'sort_order' => 40],

            ['key' => 'tenant.visibility.branch_mode', 'label' => 'Branch Visibility Mode', 'scope' => 'tenant', 'category' => 'visibility', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"restricted"', 'allowed_options_json' => '["isolated","shared_read","shared_manage","restricted"]', 'sort_order' => 10],
            ['key' => 'tenant.visibility.enquiry_mode', 'label' => 'Enquiry Visibility Mode', 'scope' => 'tenant', 'category' => 'visibility', 'module_code' => 'enquiries', 'value_type' => 'string', 'default_value_json' => '"branch_only"', 'allowed_options_json' => '["own_only","team","branch_only","tenant_wide"]', 'sort_order' => 20],
            ['key' => 'tenant.visibility.expired_enquiry_mode', 'label' => 'Expired Enquiry Visibility Mode', 'scope' => 'tenant', 'category' => 'visibility', 'module_code' => 'enquiries', 'value_type' => 'string', 'default_value_json' => '"tenant_admin_only"', 'allowed_options_json' => '["hidden","branch_admin_only","tenant_admin_only","assigned_users"]', 'sort_order' => 30],

            ['key' => 'tenant.security.password_min_length', 'label' => 'Password Minimum Length', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '8', 'sort_order' => 10],
            ['key' => 'tenant.security.password_history_count', 'label' => 'Password History Count', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '5', 'sort_order' => 20],
            ['key' => 'tenant.security.session_timeout_minutes', 'label' => 'Session Timeout Minutes', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '120', 'sort_order' => 30],
            ['key' => 'tenant.security.allow_impersonation', 'label' => 'Allow Tenant Impersonation', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'sort_order' => 40],
            ['key' => 'tenant.security.require_impersonation_reason', 'label' => 'Require Impersonation Reason', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'sort_order' => 50],

            ['key' => 'enquiry.policy.expiry_days', 'label' => 'Enquiry Expiry Days', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'enquiries', 'value_type' => 'int', 'default_value_json' => '30', 'sort_order' => 10],
            ['key' => 'enquiry.policy.auto_close_inactive_days', 'label' => 'Auto Close Inactive Days', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'enquiries', 'value_type' => 'int', 'default_value_json' => '45', 'sort_order' => 20],
            ['key' => 'enquiry.policy.duplicate_scope', 'label' => 'Duplicate Scope', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'enquiries', 'value_type' => 'string', 'default_value_json' => '"tenant"', 'allowed_options_json' => '["tenant","branch"]', 'sort_order' => 30],
            ['key' => 'enquiry.policy.duplicate_action', 'label' => 'Duplicate Action', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'enquiries', 'value_type' => 'string', 'default_value_json' => '"warn"', 'allowed_options_json' => '["warn","block","merge_suggest"]', 'sort_order' => 40],
            ['key' => 'enquiry.policy.assignment_mode', 'label' => 'Assignment Mode', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'enquiries', 'value_type' => 'string', 'default_value_json' => '"manual"', 'allowed_options_json' => '["manual","round_robin","source_based","branch_queue"]', 'sort_order' => 50],
            ['key' => 'enquiry.policy.exclude_sources_from_expiry', 'label' => 'Exclude Sources From Expiry', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'enquiries', 'value_type' => 'json', 'default_value_json' => '[]', 'sort_order' => 60],

            ['key' => 'branch.regional.inherit_tenant_defaults', 'label' => 'Inherit Tenant Regional Defaults', 'scope' => 'branch', 'category' => 'regional', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'sort_order' => 10],
            ['key' => 'branch.operations.assignment_mode', 'label' => 'Branch Assignment Mode', 'scope' => 'branch', 'category' => 'operations', 'module_code' => 'enquiries', 'value_type' => 'string', 'default_value_json' => '"inherit"', 'allowed_options_json' => '["inherit","manual","round_robin","source_based","branch_queue"]', 'sort_order' => 10],
            ['key' => 'branch.enquiry.expiry_days_override', 'label' => 'Branch Enquiry Expiry Days Override', 'scope' => 'branch', 'category' => 'enquiry', 'module_code' => 'enquiries', 'value_type' => 'int', 'default_value_json' => '0', 'sort_order' => 10],
            ['key' => 'branch.enquiry.duplicate_scope_override', 'label' => 'Branch Duplicate Scope Override', 'scope' => 'branch', 'category' => 'enquiry', 'module_code' => 'enquiries', 'value_type' => 'string', 'default_value_json' => '"inherit"', 'allowed_options_json' => '["inherit","tenant","branch"]', 'sort_order' => 20],

            ['key' => 'platform.support.allow_impersonation', 'label' => 'Allow Platform Impersonation', 'scope' => 'platform_policy', 'category' => 'support_access', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'sort_order' => 10],
            ['key' => 'platform.support.notify_tenant_owner_on_impersonation', 'label' => 'Notify Tenant Owner On Impersonation', 'scope' => 'platform_policy', 'category' => 'support_access', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'sort_order' => 20],
            ['key' => 'platform.support.impersonation_session_timeout_minutes', 'label' => 'Impersonation Session Timeout Minutes', 'scope' => 'platform_policy', 'category' => 'support_access', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '30', 'sort_order' => 30],
        ];

        foreach ($rows as $row) {
            $existing = $this->db->table('setting_definitions')
                                 ->where('key', $row['key'])
                                 ->get()
                                 ->getRow();

            if ($existing) {
                $this->db->table('setting_definitions')
                         ->where('id', $existing->id)
                         ->update($row + ['updated_at' => date('Y-m-d H:i:s')]);
                continue;
            }

            $this->db->table('setting_definitions')->insert($row + [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
