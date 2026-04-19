<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOperationalAuditTriggers extends Migration
{
    protected bool $skipTriggerStatements = false;

    public function up()
    {
        $this->dropTriggers();
        $this->createUserTriggers();
        $this->createBranchTriggers();
        $this->createCollegeTriggers();
        $this->createUserBranchTriggers();
        $this->createUserHierarchyTriggers();
        $this->createTenantSettingTriggers();
        $this->createBranchSettingTriggers();
        $this->createTenantPolicyTriggers();
        $this->createTenantMasterDataOverrideTriggers();
    }

    public function down()
    {
        $this->dropTriggers();
    }

    protected function dropTriggers(): void
    {
        foreach ([
            'trg_audit_users_insert',
            'trg_audit_users_update',
            'trg_audit_tenant_branches_insert',
            'trg_audit_tenant_branches_update',
            'trg_audit_colleges_insert',
            'trg_audit_colleges_update',
            'trg_audit_colleges_delete',
            'trg_audit_user_branches_insert',
            'trg_audit_user_branches_update',
            'trg_audit_user_branches_delete',
            'trg_audit_user_hierarchy_insert',
            'trg_audit_user_hierarchy_update',
            'trg_audit_user_hierarchy_delete',
            'trg_audit_tenant_setting_values_insert',
            'trg_audit_tenant_setting_values_update',
            'trg_audit_branch_setting_values_insert',
            'trg_audit_branch_setting_values_update',
            'trg_audit_tenant_policy_overrides_insert',
            'trg_audit_tenant_policy_overrides_update',
            'trg_audit_tenant_master_data_overrides_insert',
            'trg_audit_tenant_master_data_overrides_update',
        ] as $trigger) {
            $this->runTriggerStatement("DROP TRIGGER IF EXISTS `{$trigger}`");
        }
    }

    protected function createUserTriggers(): void
    {
        $fields = [
            'role_id',
            'employee_code',
            'username',
            'email',
            'first_name',
            'last_name',
            'mobile_number',
            'whatsapp_number',
            'department',
            'designation',
            'is_active',
            'must_reset_password',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_users_insert`
            AFTER INSERT ON `users`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NEW.created_by, 'user', NEW.id, 'created', 'User created', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_users_update`
            AFTER UPDATE ON `users`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                COALESCE(NEW.updated_by, OLD.updated_by, NEW.created_by, OLD.created_by),
                'user',
                NEW.id,
                'updated',
                'User updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );
    }

    protected function createBranchTriggers(): void
    {
        $fields = [
            'name',
            'code',
            'type',
            'state_code',
            'city',
            'timezone',
            'currency_code',
            'status',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_tenant_branches_insert`
            AFTER INSERT ON `tenant_branches`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NEW.created_by, 'tenant_branch', NEW.id, 'created', 'Branch created', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_tenant_branches_update`
            AFTER UPDATE ON `tenant_branches`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                COALESCE(NEW.updated_by, OLD.updated_by, NEW.created_by, OLD.created_by),
                'tenant_branch',
                NEW.id,
                'updated',
                'Branch updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );
    }

    protected function createCollegeTriggers(): void
    {
        $fields = [
            'name',
            'city_name',
            'state_name',
            'status',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_colleges_insert`
            AFTER INSERT ON `colleges`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NEW.created_by, 'college', NEW.id, 'created', 'College created', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_colleges_update`
            AFTER UPDATE ON `colleges`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                COALESCE(NEW.updated_by, OLD.updated_by, NEW.created_by, OLD.created_by),
                'college',
                NEW.id,
                'updated',
                'College updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_colleges_delete`
            AFTER DELETE ON `colleges`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (OLD.tenant_id, COALESCE(OLD.updated_by, OLD.created_by), 'college', OLD.id, 'deleted', 'College removed', {$oldJson}, NULL, NOW())"
        );
    }

    protected function createUserBranchTriggers(): void
    {
        $fields = [
            'user_id',
            'branch_id',
            'is_primary',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $tenantExprNew = '(SELECT tenant_id FROM users WHERE id = NEW.user_id LIMIT 1)';
        $tenantExprOld = '(SELECT tenant_id FROM users WHERE id = OLD.user_id LIMIT 1)';

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_user_branches_insert`
            AFTER INSERT ON `user_branches`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                ({$tenantExprNew}, NEW.created_by, 'user_branch', NEW.user_id, 'created', 'User branch assigned', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_user_branches_update`
            AFTER UPDATE ON `user_branches`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                {$tenantExprNew},
                COALESCE(NEW.updated_by, OLD.updated_by, NEW.created_by, OLD.created_by),
                'user_branch',
                NEW.user_id,
                'updated',
                'User branch updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_user_branches_delete`
            AFTER DELETE ON `user_branches`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                ({$tenantExprOld}, COALESCE(OLD.updated_by, OLD.created_by), 'user_branch', OLD.user_id, 'deleted', 'User branch removed', {$oldJson}, NULL, NOW())"
        );
    }

    protected function createUserHierarchyTriggers(): void
    {
        $fields = [
            'user_id',
            'manager_user_id',
            'acting_manager_user_id',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_user_hierarchy_insert`
            AFTER INSERT ON `user_hierarchy`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NEW.created_by, 'user_hierarchy', NEW.user_id, 'created', 'Reporting line created', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_user_hierarchy_update`
            AFTER UPDATE ON `user_hierarchy`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                COALESCE(NEW.updated_by, OLD.updated_by, NEW.created_by, OLD.created_by),
                'user_hierarchy',
                NEW.user_id,
                'updated',
                'Reporting line updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_user_hierarchy_delete`
            AFTER DELETE ON `user_hierarchy`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (OLD.tenant_id, COALESCE(OLD.updated_by, OLD.created_by), 'user_hierarchy', OLD.user_id, 'deleted', 'Reporting line removed', {$oldJson}, NULL, NOW())"
        );
    }

    protected function createTenantSettingTriggers(): void
    {
        $fields = [
            'key',
            'value',
            'value_type',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_tenant_setting_values_insert`
            AFTER INSERT ON `tenant_setting_values`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NULL, 'tenant_setting', NEW.id, 'created', 'Company setting created', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_tenant_setting_values_update`
            AFTER UPDATE ON `tenant_setting_values`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                NULL,
                'tenant_setting',
                NEW.id,
                'updated',
                'Company setting updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );
    }

    protected function createBranchSettingTriggers(): void
    {
        $fields = [
            'branch_id',
            'key',
            'value',
            'value_type',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_branch_setting_values_insert`
            AFTER INSERT ON `branch_setting_values`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NEW.created_by, 'branch_setting', NEW.branch_id, 'created', 'Branch setting created', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_branch_setting_values_update`
            AFTER UPDATE ON `branch_setting_values`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                COALESCE(NEW.updated_by, OLD.updated_by, NEW.created_by, OLD.created_by),
                'branch_setting',
                NEW.branch_id,
                'updated',
                'Branch setting updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );
    }

    protected function createTenantPolicyTriggers(): void
    {
        $fields = [
            'key',
            'override_value',
            'value_type',
            'lock_mode',
            'notes',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_tenant_policy_overrides_insert`
            AFTER INSERT ON `tenant_policy_overrides`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NEW.created_by, 'tenant_policy', NEW.id, 'created', 'Policy override created', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_tenant_policy_overrides_update`
            AFTER UPDATE ON `tenant_policy_overrides`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                COALESCE(NEW.updated_by, OLD.updated_by, NEW.created_by, OLD.created_by),
                'tenant_policy',
                NEW.id,
                'updated',
                'Policy override updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );
    }

    protected function createTenantMasterDataOverrideTriggers(): void
    {
        $fields = [
            'master_data_value_id',
            'is_visible',
            'sort_order_override',
            'label_override',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_tenant_master_data_overrides_insert`
            AFTER INSERT ON `tenant_master_data_overrides`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NEW.updated_by, 'tenant_master_data', NEW.id, 'created', 'Business lookup override created', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_tenant_master_data_overrides_update`
            AFTER UPDATE ON `tenant_master_data_overrides`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                COALESCE(NEW.updated_by, OLD.updated_by),
                'tenant_master_data',
                NEW.id,
                'updated',
                'Business lookup override updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );
    }

    protected function buildJsonObject(string $alias, array $fields): string
    {
        $parts = [];

        foreach ($fields as $field) {
            $parts[] = "'" . $field . "'";
            $parts[] = "{$alias}.`{$field}`";
        }

        return 'JSON_OBJECT(' . implode(', ', $parts) . ')';
    }

    protected function buildChangedCondition(array $fields): string
    {
        $comparisons = array_map(
            static fn(string $field): string => "OLD.`{$field}` <=> NEW.`{$field}`",
            $fields
        );

        return implode(' AND ', $comparisons);
    }

    protected function runTriggerStatement(string $sql): void
    {
        if ($this->skipTriggerStatements) {
            return;
        }

        try {
            $this->db->query($sql);
        } catch (\Throwable $exception) {
            if (! $this->shouldSkipTriggers($exception)) {
                throw $exception;
            }

            $this->skipTriggerStatements = true;
            log_message(
                'warning',
                'Skipping operational audit trigger migration because trigger creation is not allowed on this database server: {message}',
                ['message' => $exception->getMessage()]
            );
        }
    }

    protected function shouldSkipTriggers(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'super privilege')
            || str_contains($message, 'log_bin_trust_function_creators')
            || str_contains($message, 'trigger command denied')
            || str_contains($message, 'access denied');
    }
}
