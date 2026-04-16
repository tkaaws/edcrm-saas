<?php

namespace App\Services;

use App\Models\PrivilegeModel;
use App\Models\RoleModel;

/**
 * DelegationGuardService
 *
 * Restricts what the current actor can grant to others.
 *
 * Rules:
 * - privilege must belong to a module enabled for the tenant plan
 * - privilege must already be held by the current actor
 * - assignable roles are roles whose privilege set is a subset of assignable privileges
 */
class DelegationGuardService
{
    /**
     * Map privilege.module values to billing feature_catalog codes.
     *
     * @var array<string, string>
     */
    protected array $moduleFeatureMap = [
        'users'      => 'crm_core',
        'branches'   => 'crm_core',
        'roles'      => 'crm_core',
        'colleges'   => 'crm_core',
        'settings'   => 'crm_core',
        'billing'    => 'crm_core',
        'enquiries'  => 'crm_core',
        'followups'  => 'crm_core',
        'admissions' => 'admissions',
        'fees'       => 'admissions',
        'tickets'    => 'service_tickets',
        'placement'  => 'placement',
        'batches'    => 'batch_management',
        'students'   => 'batch_management',
        'reports'    => 'advanced_reports',
        'whatsapp'   => 'whatsapp',
        'audit'      => 'advanced_reports',
    ];

    protected PrivilegeModel $privilegeModel;
    protected RoleModel $roleModel;
    protected PermissionService $permissionService;
    protected FeatureGateService $featureGateService;

    /**
     * @var array<int, array<int, object>>
     */
    protected array $assignablePrivilegeCache = [];

    /**
     * @var array<int, array<int, object>>
     */
    protected array $assignableRoleCache = [];

    public function __construct()
    {
        $this->privilegeModel      = new PrivilegeModel();
        $this->roleModel           = new RoleModel();
        $this->permissionService   = new PermissionService();
        $this->featureGateService  = service('featureGate');
    }

    /**
     * @return array<string, array<int, object>>
     */
    public function getGroupedAssignablePrivilegesForTenant(int $tenantId): array
    {
        $grouped = [];

        foreach ($this->getAssignablePrivilegesForTenant($tenantId) as $privilege) {
            $grouped[$privilege->module][] = $privilege;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @return array<int, object>
     */
    public function getAssignablePrivilegesForTenant(int $tenantId): array
    {
        if (isset($this->assignablePrivilegeCache[$tenantId])) {
            return $this->assignablePrivilegeCache[$tenantId];
        }

        $actorCodes = $this->permissionService->getCodes();

        if ($actorCodes === []) {
            return $this->assignablePrivilegeCache[$tenantId] = [];
        }

        $assignable = [];
        foreach ($this->privilegeModel->orderBy('module', 'ASC')->orderBy('code', 'ASC')->findAll() as $privilege) {
            if (! in_array($privilege->code, $actorCodes, true)) {
                continue;
            }

            if (! $this->isPrivilegeEnabledForTenant($tenantId, $privilege)) {
                continue;
            }

            $assignable[] = $privilege;
        }

        return $this->assignablePrivilegeCache[$tenantId] = $assignable;
    }

    /**
     * @return list<int>
     */
    public function getAssignablePrivilegeIdsForTenant(int $tenantId): array
    {
        return array_map(
            static fn(object $privilege): int => (int) $privilege->id,
            $this->getAssignablePrivilegesForTenant($tenantId)
        );
    }

    /**
     * @param list<int> $submittedPrivilegeIds
     * @return list<int>
     */
    public function getInvalidPrivilegeIdsForTenant(int $tenantId, array $submittedPrivilegeIds): array
    {
        $allowed = array_flip($this->getAssignablePrivilegeIdsForTenant($tenantId));
        $invalid = [];

        foreach ($submittedPrivilegeIds as $privilegeId) {
            if (! isset($allowed[(int) $privilegeId])) {
                $invalid[] = (int) $privilegeId;
            }
        }

        return $invalid;
    }

    /**
     * @return array<int, object>
     */
    public function getAssignableRolesForTenant(int $tenantId): array
    {
        if (isset($this->assignableRoleCache[$tenantId])) {
            return $this->assignableRoleCache[$tenantId];
        }

        $allowedPrivilegeIds = array_flip($this->getAssignablePrivilegeIdsForTenant($tenantId));
        $roles = $this->roleModel->withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('is_system', 'DESC')
            ->orderBy('name', 'ASC')
            ->findAll();

        $assignableRoles = [];
        foreach ($roles as $role) {
            $rolePrivilegeIds = $this->privilegeModel->getPrivilegeIdsForRole((int) $role->id);

            if ($rolePrivilegeIds === []) {
                $assignableRoles[] = $role;
                continue;
            }

            $allAllowed = true;
            foreach ($rolePrivilegeIds as $privilegeId) {
                if (! isset($allowedPrivilegeIds[(int) $privilegeId])) {
                    $allAllowed = false;
                    break;
                }
            }

            if ($allAllowed) {
                $assignableRoles[] = $role;
            }
        }

        return $this->assignableRoleCache[$tenantId] = $assignableRoles;
    }

    public function canAssignRoleForTenant(int $tenantId, int $roleId): bool
    {
        foreach ($this->getAssignableRolesForTenant($tenantId) as $role) {
            if ((int) $role->id === $roleId) {
                return true;
            }
        }

        return false;
    }

    protected function isPrivilegeEnabledForTenant(int $tenantId, object $privilege): bool
    {
        $featureCode = $this->getFeatureCodeForPrivilegeModule((string) $privilege->module);

        if ($featureCode === null) {
            return false;
        }

        return $this->featureGateService->isEnabled($tenantId, $featureCode);
    }

    protected function getFeatureCodeForPrivilegeModule(string $module): ?string
    {
        return $this->moduleFeatureMap[$module] ?? null;
    }
}
