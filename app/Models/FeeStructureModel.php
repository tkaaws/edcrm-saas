<?php

namespace App\Models;

class FeeStructureModel extends BaseModel
{
    protected $table      = 'fee_structures';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'course_id',
        'name',
        'description',
        'default_installment_count',
        'default_installment_gap_days',
        'total_amount',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getAdminGrid(int $tenantId): array
    {
        return $this->withoutTenantScope()
            ->select('fee_structures.*, course.label AS course_label')
            ->join('master_data_values course', 'course.id = fee_structures.course_id', 'left')
            ->where('fee_structures.tenant_id', $tenantId)
            ->orderBy('course.label', 'ASC')
            ->orderBy('fee_structures.name', 'ASC')
            ->findAll();
    }

    public function findForTenant(int $tenantId, int $id): ?object
    {
        return $this->withoutTenantScope()
            ->select('fee_structures.*, course.label AS course_label')
            ->join('master_data_values course', 'course.id = fee_structures.course_id', 'left')
            ->where('fee_structures.tenant_id', $tenantId)
            ->where('fee_structures.id', $id)
            ->first();
    }

    public function getActiveOptionsForCourse(int $tenantId, int $courseId): array
    {
        return $this->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function nameExistsForTenantCourse(int $tenantId, int $courseId, string $name, ?int $ignoreId = null): bool
    {
        $builder = $this->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('course_id', $courseId)
            ->where('name', $name);

        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->countAllResults() > 0;
    }
}
