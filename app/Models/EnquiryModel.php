<?php

namespace App\Models;

class EnquiryModel extends BaseModel
{
    protected $table      = 'enquiries';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
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
        'created_by',
        'updated_by',
    ];

    protected $beforeUpdate = ['captureAuditSnapshot'];
    protected $afterUpdate  = ['writeAuditSnapshot'];

    protected array $auditSnapshots = [];
    protected array $auditableFields = [
        'branch_id',
        'owner_user_id',
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

    protected function captureAuditSnapshot(array $data): array
    {
        if (is_cli() || empty($data['id']) || ! is_array($data['id'])) {
            return $data;
        }

        foreach ($data['id'] as $id) {
            $row = $this->withoutTenantScope()->find((int) $id);
            if ($row) {
                $this->auditSnapshots[(int) $id] = clone $row;
            }
        }

        return $data;
    }

    protected function writeAuditSnapshot(array $data): array
    {
        if (is_cli() || empty($data['id']) || ! is_array($data['id'])) {
            return $data;
        }

        $actorId = session()->get('user_id') ?: null;

        foreach ($data['id'] as $id) {
            $id = (int) $id;
            $old = $this->auditSnapshots[$id] ?? null;
            $new = $this->withoutTenantScope()->find($id);

            if (! $old || ! $new) {
                continue;
            }

            $oldValues = [];
            $newValues = [];
            foreach ($this->auditableFields as $field) {
                $oldValue = $old->{$field} ?? null;
                $newValue = $new->{$field} ?? null;
                if ((string) $oldValue === (string) $newValue) {
                    continue;
                }

                $oldValues[$field] = $oldValue;
                $newValues[$field] = $newValue;
            }

            if ($oldValues === []) {
                continue;
            }

            $changedFields = implode(', ', array_keys($newValues));
            db_connect()->table('audit_logs')->insert([
                'tenant_id' => $new->tenant_id ?? null,
                'user_id' => $actorId,
                'entity_type' => 'enquiry',
                'entity_id' => $id,
                'action' => 'updated',
                'summary' => 'Enquiry updated: ' . $changedFields,
                'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode($newValues, JSON_UNESCAPED_UNICODE),
                'ip_address' => service('request')->getIPAddress(),
                'user_agent' => substr((string) service('request')->getUserAgent(), 0, 1000),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $data;
    }
}
