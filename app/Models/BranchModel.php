<?php

namespace App\Models;

/**
 * BranchModel
 *
 * Tenant-scoped. All queries automatically filter by current tenant_id.
 */
class BranchModel extends BaseModel
{
    protected $table      = 'tenant_branches';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
        'name',
        'code',
        'type',
        'country_code',
        'state_code',
        'city',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'timezone',
        'currency_code',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $validationRules = [
        'name'   => 'required|min_length[2]|max_length[255]',
        'code'   => 'required|min_length[1]|max_length[50]',
        'status' => 'required|in_list[active,inactive]',
    ];

    public function getActiveBranches(): array
    {
        return $this->where('status', 'active')->findAll();
    }

    public function findByCode(string $code): ?object
    {
        return $this->where('code', $code)->first();
    }

    /**
     * Return tenant branches for the admin grid.
     *
     * @return array<int, object>
     */
    public function getAdminGrid(int $tenantId): array
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    public function codeExistsForTenant(string $code, int $tenantId, ?int $ignoreBranchId = null): bool
    {
        $builder = $this->withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->where('code', $code);

        if ($ignoreBranchId !== null) {
            $builder->where('id !=', $ignoreBranchId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Resolve effective timezone for a branch.
     * Falls back to tenant default if branch has no override.
     */
    public function resolveTimezone(object $branch, object $tenant): string
    {
        return $branch->timezone ?? $tenant->default_timezone ?? 'UTC';
    }

    /**
     * Resolve effective currency for a branch.
     * Falls back to tenant default if branch has no override.
     */
    public function resolveCurrency(object $branch, object $tenant): string
    {
        return $branch->currency_code ?? $tenant->default_currency_code ?? 'USD';
    }
}
