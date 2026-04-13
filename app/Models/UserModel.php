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
        'password_hash',
        'is_active',
        'must_reset_password',
        'last_login_at',
        'last_login_ip',
        'created_by',
        'updated_by',
    ];

    // Never return password_hash in results
    protected $hiddenFields = ['password_hash'];

    protected $validationRules = [
        'email'      => 'required|valid_email',
        'username'   => 'required|min_length[3]|max_length[100]',
        'first_name' => 'required|min_length[1]|max_length[150]',
        'role_id'    => 'required|integer',
    ];

    /**
     * Find user by email within current tenant scope.
     * Used for login.
     */
    public function findByEmail(string $email): ?object
    {
        return $this->where('email', $email)
                    ->where('is_active', 1)
                    ->first();
    }

    /**
     * Find user by email for a specific tenant.
     * Used by auth before session tenant is established.
     */
    public function findByEmailForTenant(string $email, int $tenantId): ?object
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('email', $email)
                    ->first();
    }

    /**
     * Get branches assigned to a user.
     */
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

    /**
     * Get the primary branch for a user.
     */
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

    /**
     * Verify password against stored hash.
     */
    public function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    /**
     * Update last login timestamp and IP.
     */
    public function recordLogin(int $userId, string $ip): void
    {
        $this->db->table('users')->where('id', $userId)->update([
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
        ]);
    }

    /**
     * Count active users for a tenant — used by UsageLimitService.
     */
    public function countActiveForTenant(int $tenantId): int
    {
        return $this->withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', 1)
                    ->countAllResults();
    }

    /**
     * Assign user to branch.
     */
    public function assignToBranch(int $userId, int $branchId, bool $isPrimary = false): void
    {
        // If setting as primary, clear existing primary first
        if ($isPrimary) {
            $this->db->table('user_branches')
                     ->where('user_id', $userId)
                     ->update(['is_primary' => 0]);
        }

        // Check if assignment already exists
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

    /**
     * Remove user from branch.
     */
    public function removeFromBranch(int $userId, int $branchId): void
    {
        $this->db->table('user_branches')
                 ->where('user_id', $userId)
                 ->where('branch_id', $branchId)
                 ->delete();
    }
}
