<?php

namespace App\Services;

use App\Models\PrivilegeModel;

/**
 * PermissionService
 *
 * Checks whether the current user has a given privilege.
 * Privilege codes are loaded from DB once and cached in session
 * for the duration of the login session.
 *
 * Effective access = tenant entitlement AND user privilege.
 * This service handles only the user privilege side.
 * Entitlement (plan/subscription) is handled by FeatureGateService (Phase 1B).
 */
class PermissionService
{
    protected PrivilegeModel $privilegeModel;
    protected ?array $cachedCodes = null;

    // Session key for cached privilege codes
    const SESSION_KEY = 'user_privilege_codes';

    public function __construct()
    {
        $this->privilegeModel = new PrivilegeModel();
    }

    /**
     * Check if current user has a specific privilege.
     */
    public function has(string $code): bool
    {
        return in_array($code, $this->getCodes(), true);
    }

    /**
     * Check if current user has ALL of the given privileges.
     */
    public function hasAll(array $codes): bool
    {
        $userCodes = $this->getCodes();
        foreach ($codes as $code) {
            if (! in_array($code, $userCodes, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if current user has ANY of the given privileges.
     */
    public function hasAny(array $codes): bool
    {
        $userCodes = $this->getCodes();
        foreach ($codes as $code) {
            if (in_array($code, $userCodes, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if current user has privileges for a whole module.
     * e.g. hasModule('enquiries') = has at least one enquiries.* privilege
     */
    public function hasModule(string $module): bool
    {
        foreach ($this->getCodes() as $code) {
            if (str_starts_with($code, $module . '.')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all privilege codes for the current user.
     * Loads from session cache first, then DB.
     */
    public function getCodes(): array
    {
        if ($this->cachedCodes !== null) {
            return $this->cachedCodes;
        }

        // Try session cache first
        $cached = session()->get(self::SESSION_KEY);
        if (is_array($cached)) {
            $this->cachedCodes = $cached;
            return $this->cachedCodes;
        }

        // Load from DB
        $roleId = session()->get('user_role_id');
        if (! $roleId) {
            $this->cachedCodes = [];
            return [];
        }

        $codes = $this->privilegeModel->getPrivilegeCodesForRole((int) $roleId);
        $this->cachedCodes = $codes;

        // Cache in session for this login session
        session()->set(self::SESSION_KEY, $codes);

        return $this->cachedCodes;
    }

    /**
     * Load and cache privilege codes for a role at login.
     * Called by AuthService after successful login.
     */
    public function loadForRole(int $roleId): void
    {
        $codes = $this->privilegeModel->getPrivilegeCodesForRole($roleId);
        $this->cachedCodes = $codes;
        session()->set(self::SESSION_KEY, $codes);
    }

    /**
     * Clear privilege cache from session on logout or role change.
     */
    public function clearCache(): void
    {
        $this->cachedCodes = null;
        session()->remove(self::SESSION_KEY);
    }

    /**
     * Flush cache and reload from DB.
     * Use after role-privilege changes take effect.
     */
    public function refreshCache(): void
    {
        $this->clearCache();
        $this->getCodes();
    }
}
