<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTransactionalAuditTriggers extends Migration
{
    protected bool $skipTriggerStatements = false;

    public function up()
    {
        $this->dropTriggers();
        $this->createEnquiryTriggers();
        $this->createEnquiryFollowupTriggers();
        $this->createEnquiryAssignmentTriggers();
        $this->createEnquiryStatusTriggers();
    }

    public function down()
    {
        $this->dropTriggers();
    }

    protected function dropTriggers(): void
    {
        foreach ([
            'trg_audit_enquiries_insert',
            'trg_audit_enquiries_update',
            'trg_audit_enquiry_followups_insert',
            'trg_audit_enquiry_followups_update',
            'trg_audit_enquiry_followups_delete',
            'trg_audit_enquiry_assignment_history_insert',
            'trg_audit_enquiry_status_logs_insert',
        ] as $trigger) {
            $this->runTriggerStatement("DROP TRIGGER IF EXISTS `{$trigger}`");
        }
    }

    protected function createEnquiryTriggers(): void
    {
        $fields = [
            'branch_id',
            'owner_user_id',
            'assigned_on',
            'student_name',
            'email',
            'mobile',
            'whatsapp_number',
            'source_id',
            'college_id',
            'qualification_id',
            'primary_course_id',
            'city',
            'notes',
            'lifecycle_status',
            'closed_reason_id',
            'closed_remarks',
            'last_followup_at',
            'next_followup_at',
            'closed_at',
            'closed_by',
            'admitted_at',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_enquiries_insert`
            AFTER INSERT ON `enquiries`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NEW.created_by, 'enquiry', NEW.id, 'created', 'Enquiry created', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_enquiries_update`
            AFTER UPDATE ON `enquiries`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                COALESCE(NEW.updated_by, OLD.updated_by, NEW.created_by, OLD.created_by),
                'enquiry',
                NEW.id,
                'updated',
                'Enquiry updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );
    }

    protected function createEnquiryFollowupTriggers(): void
    {
        $fields = [
            'branch_id',
            'owner_user_id',
            'communication_type_id',
            'followup_outcome_id',
            'remarks',
            'next_followup_at',
            'is_system_generated',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);
        $oldJson = $this->buildJsonObject('OLD', $fields);
        $changedCondition = $this->buildChangedCondition($fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_enquiry_followups_insert`
            AFTER INSERT ON `enquiry_followups`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, NEW.created_by, 'enquiry_followup', NEW.enquiry_id, 'created', 'Follow-up added', NULL, {$newJson}, NOW())"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_enquiry_followups_update`
            AFTER UPDATE ON `enquiry_followups`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            SELECT
                NEW.tenant_id,
                COALESCE(NEW.updated_by, OLD.updated_by, NEW.created_by, OLD.created_by),
                'enquiry_followup',
                NEW.enquiry_id,
                'updated',
                'Follow-up updated',
                {$oldJson},
                {$newJson},
                NOW()
            FROM DUAL
            WHERE NOT ({$changedCondition})"
        );

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_enquiry_followups_delete`
            AFTER DELETE ON `enquiry_followups`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (OLD.tenant_id, COALESCE(OLD.updated_by, OLD.created_by), 'enquiry_followup', OLD.enquiry_id, 'deleted', 'Follow-up deleted', {$oldJson}, NULL, NOW())"
        );
    }

    protected function createEnquiryAssignmentTriggers(): void
    {
        $fields = [
            'from_branch_id',
            'to_branch_id',
            'from_user_id',
            'to_user_id',
            'assignment_type',
            'reason',
            'bulk_batch_id',
            'assigned_on',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_enquiry_assignment_history_insert`
            AFTER INSERT ON `enquiry_assignment_history`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, COALESCE(NEW.assigned_by, NEW.created_by), 'enquiry_assignment', NEW.enquiry_id, 'created', 'Enquiry reassigned', NULL, {$newJson}, NOW())"
        );
    }

    protected function createEnquiryStatusTriggers(): void
    {
        $fields = [
            'from_status',
            'to_status',
            'reason',
        ];

        $newJson = $this->buildJsonObject('NEW', $fields);

        $this->runTriggerStatement(
            "CREATE TRIGGER `trg_audit_enquiry_status_logs_insert`
            AFTER INSERT ON `enquiry_status_logs`
            FOR EACH ROW
            INSERT INTO `audit_logs`
                (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `action`, `summary`, `old_values`, `new_values`, `created_at`)
            VALUES
                (NEW.tenant_id, COALESCE(NEW.changed_by, NEW.created_by), 'enquiry_status', NEW.enquiry_id, 'created', 'Enquiry status changed', NULL, {$newJson}, NOW())"
        );
    }

    /**
     * @param list<string> $fields
     */
    protected function buildJsonObject(string $alias, array $fields): string
    {
        $parts = [];

        foreach ($fields as $field) {
            $parts[] = "'" . $field . "'";
            $parts[] = "{$alias}.`{$field}`";
        }

        return 'JSON_OBJECT(' . implode(', ', $parts) . ')';
    }

    /**
     * @param list<string> $fields
     */
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
                'Skipping transactional audit trigger migration because trigger creation is not allowed on this database server: {message}',
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
