<?php

namespace App\Services;

use App\Models\ImpersonationSessionModel;
use App\Models\UserModel;
use RuntimeException;

class ImpersonationService
{
    protected UserModel $userModel;
    protected ImpersonationSessionModel $sessionModel;
    protected UserAccessScopeService $userAccessScope;
    protected AuthService $authService;
    protected SettingsResolverService $settingsResolver;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->sessionModel = new ImpersonationSessionModel();
        $this->userAccessScope = new UserAccessScopeService();
        $this->authService = service('auth');
        $this->settingsResolver = service('settingsResolver');
    }

    public function start(int $targetUserId, ?string $reason = null): void
    {
        $actorId = (int) session()->get('user_id');
        $actorRoleCode = (string) session()->get('user_role_code');

        if ($actorId < 1) {
            throw new RuntimeException('Only authenticated users can impersonate.');
        }

        if (session()->get('impersonation_active')) {
            throw new RuntimeException('Stop the current impersonation session before starting another one.');
        }

        $target = $this->userModel->withoutTenantScope()->find($targetUserId);
        if (! $target || ! (int) $target->is_active) {
            throw new RuntimeException('Target user is not available for impersonation.');
        }

        if ((int) $target->id === $actorId) {
            throw new RuntimeException('You are already logged in as this user.');
        }

        if (! (int) ($target->allow_impersonation ?? 1)) {
            throw new RuntimeException('This user has disabled impersonation.');
        }

        $isPlatformAdmin = $actorRoleCode === 'platform_admin';

        if ($isPlatformAdmin) {
            $tenantId = $target->tenant_id !== null ? (int) $target->tenant_id : 0;
            $allowed = $tenantId > 0
                ? (bool) $this->settingsResolver->getEffectiveSetting($tenantId, null, 'platform.support.allow_impersonation')
                : true;

            if (! $allowed) {
                throw new RuntimeException('Platform support impersonation is disabled for this tenant.');
            }
        } else {
            if (! in_array('users.impersonate', session()->get('user_privilege_codes') ?? [], true)) {
                throw new RuntimeException('You do not have permission to impersonate users.');
            }

            if ((int) session()->get('tenant_id') !== (int) $target->tenant_id) {
                throw new RuntimeException('You can only impersonate users from your own tenant.');
            }

            if (! $this->userAccessScope->canManageTargetUser($target)) {
                throw new RuntimeException('This user is outside your management scope.');
            }

            if (! (bool) $this->settingsResolver->getEffectiveSetting((int) $target->tenant_id, null, 'tenant.security.allow_impersonation')) {
                throw new RuntimeException('Tenant impersonation is disabled.');
            }
        }

        $requireReason = $isPlatformAdmin
            ? true
            : (bool) $this->settingsResolver->getEffectiveSetting((int) $target->tenant_id, null, 'tenant.security.require_impersonation_reason');

        if ($requireReason && trim((string) $reason) === '') {
            throw new RuntimeException('A reason is required to start impersonation.');
        }

        $sessionRowId = $this->sessionModel->insert([
            'tenant_id'         => $target->tenant_id !== null ? (int) $target->tenant_id : null,
            'actor_user_id'     => $actorId,
            'target_user_id'    => (int) $target->id,
            'reason'            => trim((string) $reason) ?: null,
            'started_at'        => date('Y-m-d H:i:s'),
            'actor_ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'actor_user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000),
        ], true);

        $originalState = [
            'original_user_id'       => $actorId,
            'original_role_code'     => $actorRoleCode,
            'original_tenant_id'     => session()->get('tenant_id'),
            'original_branch_id'     => session()->get('branch_id'),
            'original_user_email'    => session()->get('user_email'),
            'original_user_name'     => trim((string) session()->get('user_first_name') . ' ' . (string) session()->get('user_last_name')),
            'impersonation_session_id' => (int) $sessionRowId,
            'impersonation_reason'   => trim((string) $reason),
            'impersonation_started_at' => date('Y-m-d H:i:s'),
        ];

        $this->authService->establishSessionForUserId((int) $target->id);

        session()->set(array_merge($originalState, [
            'impersonation_active'      => true,
            'impersonation_actor_id'    => $actorId,
            'impersonation_target_id'   => (int) $target->id,
            'impersonation_actor_role'  => $actorRoleCode,
            'impersonation_actor_name'  => $originalState['original_user_name'],
        ]));
    }

    public function stop(): void
    {
        if (! session()->get('impersonation_active')) {
            throw new RuntimeException('No active impersonation session.');
        }

        $originalUserId = (int) session()->get('original_user_id');
        $sessionId = (int) session()->get('impersonation_session_id');

        if ($sessionId > 0) {
            $this->sessionModel->update($sessionId, [
                'ended_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->authService->establishSessionForUserId($originalUserId);

        session()->remove([
            'impersonation_active',
            'impersonation_actor_id',
            'impersonation_target_id',
            'impersonation_actor_role',
            'impersonation_actor_name',
            'impersonation_session_id',
            'impersonation_reason',
            'impersonation_started_at',
            'original_user_id',
            'original_role_code',
            'original_tenant_id',
            'original_branch_id',
            'original_user_email',
            'original_user_name',
        ]);
    }
}
