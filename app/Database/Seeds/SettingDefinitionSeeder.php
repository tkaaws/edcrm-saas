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

            ['key' => 'tenant.visibility.branch_mode', 'label' => 'Branch Visibility Mode', 'description' => 'Choose whether branches stay separate, share read access, or share management access.', 'scope' => 'tenant', 'category' => 'visibility', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"restricted"', 'allowed_options_json' => '["isolated","shared_read","shared_manage","restricted"]', 'is_active' => 1, 'sort_order' => 10],
            ['key' => 'tenant.visibility.enquiry_mode', 'label' => 'Legacy Enquiry Visibility Mode', 'scope' => 'tenant', 'category' => 'visibility', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"restricted"', 'allowed_options_json' => '["own","restricted","all"]', 'is_active' => 0, 'sort_order' => 20],
            ['key' => 'tenant.visibility.expired_enquiry_mode', 'label' => 'Legacy Expired Enquiry Visibility Mode', 'scope' => 'tenant', 'category' => 'visibility', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"restricted"', 'allowed_options_json' => '["own","restricted","all"]', 'is_active' => 0, 'sort_order' => 30],

            ['key' => 'tenant.security.password_min_length', 'label' => 'Password Minimum Length', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '8', 'sort_order' => 10],
            ['key' => 'tenant.security.password_history_count', 'label' => 'Password History Count', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '5', 'sort_order' => 20],
            ['key' => 'tenant.security.session_timeout_minutes', 'label' => 'Session Timeout Minutes', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '120', 'sort_order' => 30],
            ['key' => 'tenant.security.allow_impersonation', 'label' => 'Allow Tenant Impersonation', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'sort_order' => 40],
            ['key' => 'tenant.security.require_impersonation_reason', 'label' => 'Require Impersonation Reason', 'scope' => 'tenant', 'category' => 'security', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'sort_order' => 50],

            ['key' => 'enquiry.policy.expiry_days', 'label' => 'Legacy Enquiry Expiry Days', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '30', 'is_active' => 0, 'sort_order' => 10],
            ['key' => 'enquiry.policy.auto_close_inactive_days', 'label' => 'Legacy Auto Close Inactive Days', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '45', 'is_active' => 0, 'sort_order' => 20],
            ['key' => 'enquiry.policy.duplicate_scope', 'label' => 'Legacy Duplicate Scope', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"tenant"', 'allowed_options_json' => '["tenant","branch"]', 'is_active' => 0, 'sort_order' => 30],
            ['key' => 'enquiry.policy.duplicate_action', 'label' => 'Legacy Duplicate Action', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"warn"', 'allowed_options_json' => '["warn","block","merge_suggest"]', 'is_active' => 0, 'sort_order' => 40],
            ['key' => 'enquiry.policy.assignment_mode', 'label' => 'Legacy Assignment Mode', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"manual"', 'allowed_options_json' => '["manual","round_robin","source_based","branch_queue"]', 'is_active' => 0, 'sort_order' => 50],
            ['key' => 'enquiry.policy.exclude_sources_from_expiry', 'label' => 'Legacy Exclude Sources From Expiry', 'scope' => 'tenant', 'category' => 'enquiry', 'module_code' => 'crm_core', 'value_type' => 'json', 'default_value_json' => '[]', 'is_active' => 0, 'sort_order' => 60],

            ['key' => 'enquiry.visibility.mode', 'label' => 'Who can see enquiries', 'description' => 'Keep enquiry access simple: only the owner, assigned branches, or the full company.', 'scope' => 'tenant', 'category' => 'enquiry_visibility', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"assigned_branches"', 'allowed_options_json' => '["self","assigned_branches","company"]', 'is_active' => 1, 'sort_order' => 10],
            ['key' => 'enquiry.visibility.allow_cross_branch_transfer', 'label' => 'Allow moving enquiries across branches', 'description' => 'Turn this off when branches should not pass enquiries to each other.', 'scope' => 'tenant', 'category' => 'enquiry_visibility', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'is_active' => 0, 'sort_order' => 20],
            ['key' => 'enquiry.visibility.show_closed_to_all', 'label' => 'Keep closed enquiries visible based on normal access rules', 'description' => 'When on, closed enquiries follow the same visibility rule as open enquiries.', 'scope' => 'tenant', 'category' => 'enquiry_visibility', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'is_active' => 1, 'sort_order' => 30],
            ['key' => 'enquiry.visibility.show_expired_to_all', 'label' => 'Keep expired enquiries visible based on normal access rules', 'description' => 'When on, expired enquiries follow the same visibility rule as open enquiries.', 'scope' => 'tenant', 'category' => 'enquiry_visibility', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'is_active' => 1, 'sort_order' => 40],

            ['key' => 'enquiry.duplicate.match_mode', 'label' => 'How duplicates are matched', 'description' => 'Choose whether duplicate enquiries are matched by email, mobile, or both.', 'scope' => 'tenant', 'category' => 'enquiry_duplicate', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"email_or_mobile"', 'allowed_options_json' => '["email_and_mobile","email_only","mobile_only","email_or_mobile"]', 'is_active' => 1, 'sort_order' => 10],
            ['key' => 'enquiry.duplicate.scope', 'label' => 'Where duplicates are checked', 'description' => 'Decide whether duplicate checking happens only inside the same branch or across the whole company.', 'scope' => 'tenant', 'category' => 'enquiry_duplicate', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"company"', 'allowed_options_json' => '["same_branch","company"]', 'is_active' => 1, 'sort_order' => 20],
            ['key' => 'enquiry.duplicate.action', 'label' => 'What happens when a duplicate is found', 'description' => 'Choose whether users see a warning or the enquiry is stopped immediately.', 'scope' => 'tenant', 'category' => 'enquiry_duplicate', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"warn"', 'allowed_options_json' => '["warn","block"]', 'is_active' => 1, 'sort_order' => 30],

            ['key' => 'enquiry.assignment.mode', 'label' => 'How enquiries are assigned', 'description' => 'Decide whether the enquiry owner is chosen manually or assigned automatically.', 'scope' => 'tenant', 'category' => 'enquiry_assignment', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"manual"', 'allowed_options_json' => '["manual","branch_round_robin","branch_default_owner"]', 'is_active' => 1, 'sort_order' => 10],
            ['key' => 'enquiry.assignment.reassign_allowed', 'label' => 'Allow reassignment after creation', 'description' => 'Turn this off when the first enquiry owner should remain fixed unless changed by admins.', 'scope' => 'tenant', 'category' => 'enquiry_assignment', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'is_active' => 1, 'sort_order' => 20],

            ['key' => 'enquiry.lifecycle.expiry_days', 'label' => 'Days before an enquiry expires', 'description' => 'After this many inactive days, the enquiry moves to expired.', 'scope' => 'tenant', 'category' => 'enquiry_lifecycle', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '30', 'is_active' => 1, 'sort_order' => 10],
            ['key' => 'enquiry.lifecycle.auto_close_days', 'label' => 'Days before an enquiry closes automatically', 'description' => 'After this many inactive days, the enquiry is closed automatically.', 'scope' => 'tenant', 'category' => 'enquiry_lifecycle', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '60', 'is_active' => 1, 'sort_order' => 20],
            ['key' => 'enquiry.lifecycle.reopen_expired_allowed', 'label' => 'Allow reopening expired enquiries', 'description' => 'Turn this off if expired enquiries should stay locked.', 'scope' => 'tenant', 'category' => 'enquiry_lifecycle', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'is_active' => 0, 'sort_order' => 30],
            ['key' => 'enquiry.lifecycle.reopen_closed_allowed', 'label' => 'Allow reopening closed enquiries', 'description' => 'Turn this on if closed enquiries can come back into the active pipeline.', 'scope' => 'tenant', 'category' => 'enquiry_lifecycle', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'false', 'is_active' => 0, 'sort_order' => 40],

            ['key' => 'branch.regional.inherit_tenant_defaults', 'label' => 'Inherit Tenant Regional Defaults', 'scope' => 'branch', 'category' => 'regional', 'module_code' => 'crm_core', 'value_type' => 'bool', 'default_value_json' => 'true', 'sort_order' => 10],
            ['key' => 'branch.operations.assignment_mode', 'label' => 'Branch Assignment Mode', 'scope' => 'branch', 'category' => 'operations', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"inherit"', 'allowed_options_json' => '["inherit","manual","branch_round_robin","branch_default_owner"]', 'is_active' => 1, 'sort_order' => 10],
            ['key' => 'branch.enquiry.expiry_days_override', 'label' => 'Legacy Branch Enquiry Expiry Days Override', 'scope' => 'branch', 'category' => 'enquiry', 'module_code' => 'crm_core', 'value_type' => 'int', 'default_value_json' => '0', 'is_active' => 0, 'sort_order' => 10],
            ['key' => 'branch.enquiry.duplicate_scope_override', 'label' => 'Legacy Branch Duplicate Scope Override', 'scope' => 'branch', 'category' => 'enquiry', 'module_code' => 'crm_core', 'value_type' => 'string', 'default_value_json' => '"inherit"', 'allowed_options_json' => '["inherit","tenant","branch"]', 'is_active' => 0, 'sort_order' => 20],

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
