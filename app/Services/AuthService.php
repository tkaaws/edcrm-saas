<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\TenantModel;
use CodeIgniter\Database\BaseConnection;

/**
 * AuthService
 *
 * Handles login, logout, forgot password, and reset password.
 *
 * Security enforced:
 * - bcrypt password verification
 * - inactive account blocking
 * - session regeneration on login (prevents session fixation)
 * - full session destroy on logout
 * - login events written to audit_logs
 * - last_login_at and last_login_ip recorded on success
 * - must_reset_password flag enforced before any page access
 * - password reset tokens: expiry, single-use, tenant-scoped
 * - password history: prevents reuse of last 5 passwords
 */
class AuthService
{
    const LOGIN_SUCCESS         = 'success';
    const LOGIN_INVALID         = 'invalid_credentials';
    const LOGIN_INACTIVE        = 'account_inactive';
    const LOGIN_MUST_RESET      = 'must_reset_password';

    const RESET_TOKEN_EXPIRY_MINS = 60;
    const PASSWORD_HISTORY_DEPTH  = 5;

    protected UserModel $userModel;
    protected TenantModel $tenantModel;
    protected TenantResolver $tenantResolver;
    protected BranchContextResolver $branchResolver;
    protected PermissionService $permissionService;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->userModel         = new UserModel();
        $this->tenantModel       = new TenantModel();
        $this->tenantResolver    = new TenantResolver();
        $this->branchResolver    = new BranchContextResolver();
        $this->permissionService = new PermissionService();
        $this->db                = db_connect();
    }

    // ---------------------------------------------------------------
    // LOGIN
    // ---------------------------------------------------------------

    /**
     * Attempt login for a given tenant (by tenant_id).
     *
     * Returns one of the LOGIN_* constants.
     * On success, session is fully populated and ready.
     */
    public function attempt(int $tenantId, string $email, string $password): string
    {
        $user = $this->userModel->findByEmailForTenant($email, $tenantId);

        if (! $user) {
            $this->writeAudit($tenantId, null, 'login_failed', "No user found for email: {$email}");
            return self::LOGIN_INVALID;
        }

        if (! $this->userModel->verifyPassword($password, $user->password_hash)) {
            $this->writeAudit($tenantId, $user->id, 'login_failed', 'Wrong password');
            return self::LOGIN_INVALID;
        }

        if (! $user->is_active) {
            $this->writeAudit($tenantId, $user->id, 'login_blocked', 'Account inactive');
            return self::LOGIN_INACTIVE;
        }

        // All checks passed — build session
        $this->buildSession($user, $tenantId);

        // Record login metadata
        $this->userModel->recordLogin($user->id, $this->getClientIp());

        $this->writeAudit($tenantId, $user->id, 'login_success', 'Login successful');

        if ($user->must_reset_password) {
            return self::LOGIN_MUST_RESET;
        }

        return self::LOGIN_SUCCESS;
    }

    /**
     * Build the full session after successful authentication.
     * Regenerates session ID to prevent session fixation.
     */
    protected function buildSession(object $user, int $tenantId): void
    {
        $session = session();

        // Regenerate session ID — security critical
        $session->regenerate(true);

        // Load role details
        $role = $this->getRoleForUser($user);

        // Load primary branch
        $primaryBranch = $this->userModel->getPrimaryBranch($user->id);

        // Load tenant name for shell display (avoids per-request DB lookup in BaseController)
        $tenant = $this->tenantModel->find($tenantId);

        $session->set([
            'user_id'         => $user->id,
            'tenant_id'       => $tenantId,
            'tenant_name'     => $tenant?->name ?? '',
            'user_role_id'    => $user->role_id,
            'user_role_code'  => $role?->code ?? '',
            'user_role_name'  => $role?->name ?? '',
            'user_first_name' => $user->first_name,
            'user_last_name'  => $user->last_name,
            'user_email'      => $user->email,
            'branch_id'       => $primaryBranch?->id ?? null,
            'branch_name'     => $primaryBranch?->name ?? '',
        ]);

        // Load and cache privilege codes into session
        $this->permissionService->loadForRole($user->role_id);
    }

    // ---------------------------------------------------------------
    // LOGOUT
    // ---------------------------------------------------------------

    /**
     * Log the current user out.
     * Writes audit log entry, then destroys the entire session.
     */
    public function logout(): void
    {
        $userId   = session()->get('user_id');
        $tenantId = session()->get('tenant_id');

        if ($userId && $tenantId) {
            $this->writeAudit($tenantId, $userId, 'logout', 'User logged out');
        }

        session()->destroy();
    }

    // ---------------------------------------------------------------
    // FORGOT PASSWORD
    // ---------------------------------------------------------------

    /**
     * Generate a password reset token for the given email.
     *
     * Always returns true regardless of whether the email exists
     * to prevent user enumeration attacks.
     * Caller should show a generic "if email exists, reset sent" message.
     */
    public function forgotPassword(int $tenantId, string $email): bool
    {
        $user = $this->userModel->findByEmailForTenant($email, $tenantId);

        if (! $user) {
            // Silent — do not disclose whether email exists
            return true;
        }

        if (! $user->is_active) {
            // Silent — do not disclose account status
            return true;
        }

        // Invalidate any existing unused tokens for this user
        $this->db->table('password_reset_tokens')
             ->where('user_id', $user->id)
             ->where('used_at', null)
             ->update(['used_at' => date('Y-m-d H:i:s')]);

        // Generate cryptographically secure token
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash  = hash('sha256', $plainToken);
        $expiresAt  = date('Y-m-d H:i:s', strtotime('+' . self::RESET_TOKEN_EXPIRY_MINS . ' minutes'));

        $this->db->table('password_reset_tokens')->insert([
            'user_id'    => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at'    => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->writeAudit($tenantId, $user->id, 'password_reset_requested', 'Reset token generated');

        // TODO: send reset email via TenantEmailConfig (Phase 1A task 17)
        // For now, log the plain token for local dev testing
        log_message('info', "PASSWORD_RESET_TOKEN for user {$user->id}: {$plainToken}");

        return true;
    }

    // ---------------------------------------------------------------
    // RESET PASSWORD
    // ---------------------------------------------------------------

    /**
     * Validate a reset token.
     *
     * Returns the user object if token is valid.
     * Returns null if invalid, expired, or already used.
     */
    public function validateResetToken(string $plainToken): ?object
    {
        $tokenHash = hash('sha256', $plainToken);

        $record = $this->db->table('password_reset_tokens')
                      ->where('token_hash', $tokenHash)
                      ->where('used_at', null)
                      ->where('expires_at >=', date('Y-m-d H:i:s'))
                      ->get()
                      ->getRow();

        if (! $record) {
            return null;
        }

        return $this->userModel->withoutTenantScope()->find($record->user_id);
    }

    /**
     * Reset a user's password using a valid token.
     *
     * Checks password history to prevent reuse.
     * Marks token as used.
     * Clears must_reset_password flag.
     */
    public function resetPassword(string $plainToken, string $newPassword): bool|string
    {
        $user = $this->validateResetToken($plainToken);

        if (! $user) {
            return 'invalid_token';
        }

        // Check password history — prevent reuse of last N passwords
        if ($this->isPasswordReused($user->id, $newPassword)) {
            return 'password_reused';
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

        // Archive current password before overwriting
        $this->archivePassword($user->id, $user->password_hash);

        // Update password and clear reset flag
        $this->db->table('users')->where('id', $user->id)->update([
            'password_hash'       => $newHash,
            'must_reset_password' => 0,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        // Mark token as used — single-use enforcement
        $tokenHash = hash('sha256', $plainToken);
        $this->db->table('password_reset_tokens')
            ->where('token_hash', $tokenHash)
            ->update(['used_at' => date('Y-m-d H:i:s')]);

        $this->writeAudit($user->tenant_id, $user->id, 'password_reset_completed', 'Password reset successful');

        return true;
    }

    /**
     * Change password for an authenticated user (not via reset token).
     * Used for must_reset_password flow and voluntary password change.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool|string
    {
        $user = $this->userModel->withoutTenantScope()->find($userId);

        if (! $user) {
            return 'user_not_found';
        }

        if (! $this->userModel->verifyPassword($currentPassword, $user->password_hash)) {
            return 'wrong_current_password';
        }

        if ($this->isPasswordReused($userId, $newPassword)) {
            return 'password_reused';
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->archivePassword($userId, $user->password_hash);

        $this->db->table('users')->where('id', $userId)->update([
            'password_hash'       => $newHash,
            'must_reset_password' => 0,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $this->writeAudit($user->tenant_id, $userId, 'password_changed', 'Password changed by user');

        return true;
    }

    // ---------------------------------------------------------------
    // HELPERS
    // ---------------------------------------------------------------

    /**
     * Check if a plain password matches any of the last N archived hashes.
     */
    protected function isPasswordReused(int $userId, string $plainPassword): bool
    {
        $history = $this->db->table('user_password_histories')
                       ->where('user_id', $userId)
                       ->orderBy('created_at', 'DESC')
                       ->limit(self::PASSWORD_HISTORY_DEPTH)
                       ->get()
                       ->getResultArray();

        foreach ($history as $row) {
            if (password_verify($plainPassword, $row['password_hash'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Archive a password hash into password history.
     */
    protected function archivePassword(int $userId, string $hash): void
    {
        $this->db->table('user_password_histories')->insert([
            'user_id'       => $userId,
            'password_hash' => $hash,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Write an entry to audit_logs.
     */
    protected function writeAudit(int $tenantId, ?int $userId, string $action, string $summary): void
    {
        $this->db->table('audit_logs')->insert([
            'tenant_id'   => $tenantId,
            'user_id'     => $userId,
            'entity_type' => 'auth',
            'entity_id'   => $userId,
            'action'      => $action,
            'summary'     => $summary,
            'ip_address'  => $this->getClientIp(),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get client IP, respecting common proxy headers.
     */
    protected function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    /**
     * Get role object for user.
     */
    protected function getRoleForUser(object $user): ?object
    {
        return $this->db->table('tenant_roles')
                   ->where('id', $user->role_id)
                   ->get()
                   ->getRow();
    }
}
