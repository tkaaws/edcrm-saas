<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\UserModel;
use RuntimeException;

/**
 * BranchContextResolver
 *
 * Resolves the currently active branch for the logged-in user.
 * Handles branch switching for multi-branch users.
 * Resolves effective timezone and currency (branch > tenant fallback).
 */
class BranchContextResolver
{
    protected BranchModel $branchModel;
    protected UserModel $userModel;
    protected ?object $resolvedBranch = null;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
        $this->userModel   = new UserModel();
    }

    /**
     * Resolve the currently active branch from session.
     * Throws if no branch in session.
     */
    public function resolve(): object
    {
        if ($this->resolvedBranch !== null) {
            return $this->resolvedBranch;
        }

        $branchId = session()->get('branch_id');

        if (! $branchId) {
            throw new RuntimeException('No branch in session.');
        }

        $branch = $this->branchModel->findForTenant((int) $branchId);

        if (! $branch) {
            throw new RuntimeException("Branch [{$branchId}] not found or not accessible.");
        }

        $this->resolvedBranch = $branch;
        return $this->resolvedBranch;
    }

    /**
     * Return current branch without throwing.
     */
    public function tryResolve(): ?object
    {
        try {
            return $this->resolve();
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * Switch the active branch for the current user.
     * Validates the user is actually assigned to the branch.
     */
    public function switchBranch(int $userId, int $branchId): void
    {
        $assigned = $this->userModel->getBranches($userId);
        $assignedIds = array_column($assigned, 'id');

        if (! in_array($branchId, $assignedIds)) {
            throw new RuntimeException("User [{$userId}] is not assigned to branch [{$branchId}].");
        }

        session()->set('branch_id', $branchId);
        $this->resolvedBranch = null;
    }

    /**
     * Set branch in session — called at login using user's primary branch.
     */
    public function setSession(int $branchId): void
    {
        session()->set('branch_id', $branchId);
        $this->resolvedBranch = null;
    }

    /**
     * Clear branch from session on logout.
     */
    public function clearSession(): void
    {
        session()->remove('branch_id');
        $this->resolvedBranch = null;
    }

    /**
     * Resolve effective timezone.
     * Branch timezone overrides tenant default. Falls back to UTC.
     */
    public function resolveTimezone(?object $branch, ?object $tenant): string
    {
        if ($branch && ! empty($branch->timezone)) {
            return $branch->timezone;
        }

        if ($tenant && ! empty($tenant->default_timezone)) {
            return $tenant->default_timezone;
        }

        return 'UTC';
    }

    /**
     * Resolve effective currency.
     * Branch currency overrides tenant default. Falls back to USD.
     */
    public function resolveCurrency(?object $branch, ?object $tenant): string
    {
        if ($branch && ! empty($branch->currency_code)) {
            return $branch->currency_code;
        }

        if ($tenant && ! empty($tenant->default_currency_code)) {
            return $tenant->default_currency_code;
        }

        return 'USD';
    }
}
