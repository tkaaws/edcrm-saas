<?php

namespace App\Models;

class CollegeModel extends BaseModel
{
    protected $table      = 'colleges';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'name',
        'city_name',
        'state_name',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $validationRules = [
        'name'       => 'required|min_length[2]|max_length[255]',
        'city_name'  => 'required|min_length[2]|max_length[150]',
        'state_name' => 'required|min_length[2]|max_length[150]',
        'status'     => 'required|in_list[active,inactive]',
    ];

    public function getAdminGrid(int $tenantId): array
    {
        return $this->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function getActiveOptions(int $tenantId, string $search = '', int $limit = 20): array
    {
        $builder = $this->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if ($search !== '') {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('city_name', $search)
                ->orLike('state_name', $search)
                ->groupEnd();
        }

        return $builder
            ->orderBy('name', 'ASC')
            ->findAll($limit);
    }

    public function nameExistsForTenant(string $name, int $tenantId, ?int $ignoreId = null): bool
    {
        $builder = $this->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('name', $name);

        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        return $builder->countAllResults() > 0;
    }
}
