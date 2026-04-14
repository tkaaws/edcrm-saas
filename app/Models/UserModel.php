<?php

namespace App\Models;

/**
 * UserModel
 *
 * Tenant-scoped. Manages users within a tenant.
 */
class UserModel extends BaseModel
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'tenant_id',
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
        'allow_impersonation',
        'password_hash',
        'is_active',
        'must_reset_password',
        'last_login_at',
        'last_login_ip',
        'created_by',
        'updated_by',
    ];

    protected $hiddenFields = ['password_hash'];

    protected $validationRules = [
        'email'      => 'required|valid_email',
        'username'   => 'required|min_length[3]|max_length[100]',
        'first_name' => 'required|min_length[1]|max_length[150]',
        'role_id'    => 'required|integer',
    ];

    public function findByEmail(string $email): ?object
    {
        return $this->where('email', $email)
                    ->where('is_active', 1)
                    ->first();
    }

    public function findByEmailForTenant(string $email, int $tenantId): ?object
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('email', $email)
                    ->first();
    }

    public function getBranches(int $userId): array
    {
        return $this->db->table('user_branches ub')
                        ->join('tenant_branches b', 'b.id = ub.branch_id')
                        ->where('ub.user_id', $userId)
                        ->where('b.status', 'active')
                        ->select('b.*, ub.is_primary')
                        ->orderBy('ub.is_primary', 'DESC')
                        ->get()
                        ->getResultArray();
    }

    public function getPrimaryBranch(int $userId): ?object
    {
        return (object) $this->db->table('user_branches ub')
                        ->join('tenant_branches b', 'b.id = ub.branch_id')
                        ->where('ub.user_id', $userId)
                        ->where('ub.is_primary', 1)
                        ->where('b.status', 'active')
                        ->select('b.*, ub.is_primary')
                        ->get()
                        ->getRowArray() ?: null;
    }

    public function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    public function recordLogin(int $userId, string $ip): void
    {
        $this->db->table('users')->where('id', $userId)->update([
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
        ]);
    }

    public function countActiveForTenant(int $tenantId): int
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', 1)
                    ->countAllResults();
    }

    public function assignToBranch(int $userId, int $branchId, bool $isPrimary = false): void
    {
        if ($isPrimary) {
            $this->db->table('user_branches')
                     ->where('user_id', $userId)
                     ->update(['is_primary' => 0]);
        }

        $existing = $this->db->table('user_branches')
                             ->where('user_id', $userId)
                             ->where('branch_id', $branchId)
                             ->countAllResults();

        if ($existing) {
            $this->db->table('user_branches')
                     ->where('user_id', $userId)
                     ->where('branch_id', $branchId)
                     ->update(['is_primary' => (int) $isPrimary]);
        } else {
            $this->db->table('user_branches')->insert([
                'user_id'    => $userId,
                'branch_id'  => $branchId,
                'is_primary' => (int) $isPrimary,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getAdminGrid(int $tenantId): array
    {
        return $this->withoutTenantScope()
                    ->select('users.*, user_roles.name as role_name, user_roles.code as role_code, tenant_branches.name as primary_branch_name')
                    ->join('user_roles', 'user_roles.id = users.role_id', 'left')
                    ->join('user_branches', 'user_branches.user_id = users.id AND user_branches.is_primary = 1', 'left')
                    ->join('tenant_branches', 'tenant_branches.id = user_branches.branch_id', 'left')
                    ->where('users.tenant_id', $tenantId)
                    ->orderBy('users.first_name', 'ASC')
                    ->findAll();
    }

    public function emailExistsPlatformWide(string $email): bool
    {
        return $this->withoutTenantScope()
                    ->where('email', $email)
                    ->countAllResults() > 0;
    }

    public function usernameExistsPlatformWide(string $username): bool
    {
        return $this->withoutTenantScope()
                    ->where('username', $username)
                    ->countAllResults() > 0;
    }

    public function emailExistsForTenant(string $email, int $tenantId, ?int $ignoreUserId = null): bool
    {
        $builder = $this->withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->where('email', $email);

        if ($ignoreUserId !== null) {
            $builder->where('id !=', $ignoreUserId);
        }

        return $builder->countAllResults() > 0;
    }

    public function usernameExistsForTenant(string $username, int $tenantId, ?int $ignoreUserId = null): bool
    {
        $builder = $this->withoutTenantScope()
                        ->where('tenant_id', $tenantId)
                        ->where('username', $username);

        if ($ignoreUserId !== null) {
            $builder->where('id !=', $ignoreUserId);
        }

        return $builder->countAllResults() > 0;
    }

    public function countActiveUsersByRole(int $tenantId, string $roleCode, ?int $ignoreUserId = null): int
    {
        $builder = $this->withoutTenantScope()
                        ->join('user_roles', 'user_roles.id = users.role_id')
                        ->where('users.tenant_id', $tenantId)
                        ->where('users.is_active', 1)
                        ->where('user_roles.code', $roleCode);

        if ($ignoreUserId !== null) {
            $builder->where('users.id !=', $ignoreUserId);
        }

        return $builder->countAllResults();
    }

    public function removeFromBranch(int $userId, int $branchId): void
    {
        $this->db->table('user_branches')
                 ->where('user_id', $userId)
                 ->where('branch_id', $branchId)
                 ->delete();
    }

    /**
     * @return array<int, object>
     */
    public function getManagerOptions(int $tenantId, ?int $ignoreUserId = null): array
    {
        $users = $this->withoutTenantScope()
                      ->where('tenant_id', $tenantId)
                      ->where('is_active', 1)
                      ->orderBy('first_name', 'ASC')
                      ->orderBy('last_name', 'ASC')
                      ->findAll();

        if ($ignoreUserId === null) {
            return $users;
        }

        return array_values(array_filter(
            $users,
            static fn(object $user): bool => (int) $user->id !== $ignoreUserId
        ));
    }
}
