<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * BaseModel
 *
 * All tenant-owned models extend this.
 * Automatically scopes every query to the current tenant_id.
 *
 * Models that are NOT tenant-scoped (e.g. PrivilegeModel) extend
 * CodeIgniter\Model directly instead.
 */
abstract class BaseModel extends Model
{
    protected $tenantScoped = true;
    protected $currentTenantId = null;

    // Standard audit fields present on all tenant-owned tables
    protected $useTimestamps  = true;
    protected $dateFormat     = 'datetime';
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    public function __construct()
    {
        parent::__construct();

        if ($this->tenantScoped) {
            $this->currentTenantId = $this->resolveTenantId();

            if ($this->currentTenantId) {
                $this->where($this->table . '.tenant_id', $this->currentTenantId);
            }
        }
    }

    /**
     * Resolve tenant ID from session.
     * Returns null if running in CLI context (migrations, seeders, spark commands).
     */
    protected function resolveTenantId(): ?int
    {
        if (is_cli()) {
            return null;
        }

        $session = session();
        $tenantId = $session->get('tenant_id');

        return $tenantId ? (int) $tenantId : null;
    }

    /**
     * Explicitly set tenant scope — used by platform admin
     * and service layer when operating outside normal session context.
     */
    public function forTenant(int $tenantId): static
    {
        $this->currentTenantId = $tenantId;
        $this->where($this->table . '.tenant_id', $tenantId);
        return $this;
    }

    /**
     * Remove tenant scope — ONLY for platform admin use.
     * Never call this in tenant-facing code paths.
     */
    public function withoutTenantScope(): static
    {
        $this->currentTenantId = null;
        $this->resetQuery();
        return $this;
    }

    /**
     * Enforce tenant ownership before returning a single record.
     * Prevents direct ID lookups from leaking cross-tenant data.
     */
    public function findForTenant(int $id): ?object
    {
        if (! $this->currentTenantId) {
            return $this->find($id);
        }

        return $this->where($this->table . '.tenant_id', $this->currentTenantId)
                    ->find($id);
    }

    /**
     * Set created_by and updated_by from session user.
     */
    protected function setActorFields(array &$data, bool $isInsert = true): void
    {
        if (is_cli()) return;

        $userId = session()->get('user_id');
        if (! $userId) return;

        if ($isInsert) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;
    }

    /**
     * Insert with automatic actor fields.
     */
    public function insertWithActor(array $data): bool|int
    {
        $this->setActorFields($data, true);
        return $this->insert($data);
    }

    /**
     * Update with automatic actor fields.
     */
    public function updateWithActor(int $id, array $data): bool
    {
        $this->setActorFields($data, false);
        return $this->update($id, $data);
    }
}
