<?php

namespace App\Models;

class BatchModel extends BaseModel
{
    protected $table      = 'tenant_batches';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'name',
        'code',
        'starts_on',
        'ends_on',
        'capacity',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function getAdminGrid(int $tenantId): array
    {
        return $this->withoutTenantScope()
            ->select('tenant_batches.*, tenant_branches.name AS branch_name')
            ->join('tenant_branches', 'tenant_branches.id = tenant_batches.branch_id', 'left')
            ->where('tenant_batches.tenant_id', $tenantId)
            ->orderBy('tenant_batches.name', 'ASC')
            ->findAll();
    }

    public function getActiveOptions(int $tenantId, ?int $branchId = null): array
    {
        $builder = $this->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if ($branchId !== null && $branchId > 0) {
            $builder->where('branch_id', $branchId);
        }

        return $builder->orderBy('name', 'ASC')->findAll();
    }

    public function countAssignedAdmissions(int $batchId): int
    {
        return $this->db->table('admissions')
            ->where('current_batch_id', $batchId)
            ->countAllResults();
    }
}
