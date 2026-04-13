<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TenantModel
 *
 * Tenant is a platform-level entity — not scoped to a tenant_id.
 * Extends CodeIgniter\Model directly.
 */
class TenantModel extends Model
{
    protected $table      = 'tenants';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'name',
        'slug',
        'status',
        'legal_name',
        'owner_name',
        'owner_email',
        'owner_phone',
        'default_timezone',
        'default_currency_code',
        'country_code',
        'locale_code',
    ];

    protected $validationRules = [
        'name'        => 'required|min_length[2]|max_length[255]',
        'slug'        => 'required|min_length[2]|max_length[150]|is_unique[tenants.slug,id,{id}]',
        'owner_email' => 'permit_empty|valid_email|is_unique[tenants.owner_email,id,{id}]',
        'status'      => 'required|in_list[draft,active,suspended,cancelled]',
    ];

    public function findBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->first();
    }

    public function ownerEmailExists(string $email): bool
    {
        return $this->where('owner_email', $email)->countAllResults() > 0;
    }

    public function isActive(int $tenantId): bool
    {
        $tenant = $this->find($tenantId);
        return $tenant && $tenant->status === 'active';
    }

    public function activate(int $tenantId): bool
    {
        return $this->update($tenantId, ['status' => 'active']);
    }

    public function suspend(int $tenantId): bool
    {
        return $this->update($tenantId, ['status' => 'suspended']);
    }
}
