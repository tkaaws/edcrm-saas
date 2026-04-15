<?php

namespace App\Services;

use App\Models\ImpersonationSessionModel;
use App\Models\UserModel;
use RuntimeException;

class ImpersonationService
{
    protected const MAX_DEPTH = 4;

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
        $stack = $this->getStack();

        if ($actorId < 1) {
            throw new RuntimeException('Only authenticated users can impersonate.');
        }

        if (count($stack) >= self::MAX_DEPTH) {
            throw new RuntimeException('Maximum support depth reached. Return one level before continuing.');
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
                throw new RuntimeException('This user is outside your access boundary.');
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

        $actorName = $this->getCurrentUserDisplayName();
        $targetName = $this->formatUserDisplayName($target);
        $parentFrame = $stack === [] ? null : $stack[array_key_last($stack)];
        $rootActorId = $stack === [] ? $actorId : (int) ($stack[0]['root_actor_user_id'] ?? $stack[0]['actor_user_id']);
        $depth = count($stack) + 1;

        $sessionRowId = $this->sessionModel->insert([
            'tenant_id'           => $target->tenant_id !== null ? (int) $target->tenant_id : null,
            'actor_user_id'       => $actorId,
            'target_user_id'      => (int) $target->id,
            'parent_session_id'   => $parentFrame['session_id'] ?? null,
            'root_actor_user_id'  => $rootActorId,
            'depth'               => $depth,
            'reason'              => trim((string) $reason) ?: null,
            'started_at'          => date('Y-m-d H:i:s'),
            'actor_ip'            => $_SERVER['REMOTE_ADDR'] ?? null,
            'actor_user_agent'    => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000),
        ], true);

        $stack[] = [
            'session_id'          => (int) $sessionRowId,
            'actor_user_id'       => $actorId,
            'actor_name'          => $actorName,
            'actor_role_code'     => $actorRoleCode,
            'actor_tenant_id'     => session()->get('tenant_id'),
            'actor_branch_id'     => session()->get('branch_id'),
            'actor_email'         => (string) session()->get('user_email'),
            'target_user_id'      => (int) $target->id,
            'target_name'         => $targetName,
            'reason'              => trim((string) $reason),
            'started_at'          => date('Y-m-d H:i:s'),
            'root_actor_user_id'  => $rootActorId,
            'depth'               => $depth,
        ];

        $this->authService->establishSessionForUserId((int) $target->id);
        $this->syncSessionState($stack);
    }

    public function stop(): void
    {
        $stack = $this->getStack();
        if ($stack === []) {
            throw new RuntimeException('No active impersonation session.');
        }

        $currentFrame = array_pop($stack);
        $this->closeSessionRecord((int) ($currentFrame['session_id'] ?? 0));

        $restoreUserId = (int) ($currentFrame['actor_user_id'] ?? 0);
        if ($restoreUserId < 1) {
            throw new RuntimeException('Unable to restore the previous account.');
        }

        $this->authService->establishSessionForUserId($restoreUserId);
        $this->syncSessionState($stack);
    }

    public function stopAll(): void
    {
        $stack = $this->getStack();
        if ($stack === []) {
            throw new RuntimeException('No active impersonation session.');
        }

        $rootUserId = (int) ($stack[0]['actor_user_id'] ?? 0);
        if ($rootUserId < 1) {
            throw new RuntimeException('Unable to restore the original account.');
        }

        foreach (array_reverse($stack) as $frame) {
            $this->closeSessionRecord((int) ($frame['session_id'] ?? 0));
        }

        $this->authService->establishSessionForUserId($rootUserId);
        $this->syncSessionState([]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function getStack(): array
    {
        $stack = session()->get('impersonation_stack') ?? [];
        return is_array($stack) ? array_values($stack) : [];
    }

    /**
     * @param list<array<string, mixed>> $stack
     */
    protected function syncSessionState(array $stack): void
    {
        if ($stack === []) {
            session()->remove([
                'impersonation_active',
                'impersonation_stack',
                'impersonation_level',
                'impersonation_max_depth',
                'impersonation_reason',
                'impersonation_actor_name',
                'impersonation_path',
                'impersonation_current_session_id',
                'original_user_id',
                'original_role_code',
                'original_tenant_id',
                'original_branch_id',
                'original_user_email',
                'original_user_name',
            ]);

            return;
        }

        $rootFrame = $stack[0];
        $currentFrame = $stack[array_key_last($stack)];
        $path = [(string) ($rootFrame['actor_name'] ?? 'Original account')];
        foreach ($stack as $frame) {
            $path[] = (string) ($frame['target_name'] ?? 'User');
        }

        session()->set([
            'impersonation_active'     => true,
            'impersonation_stack'      => $stack,
            'impersonation_level'      => count($stack),
            'impersonation_max_depth'  => self::MAX_DEPTH,
            'impersonation_reason'     => (string) ($currentFrame['reason'] ?? ''),
            'impersonation_actor_name' => (string) ($rootFrame['actor_name'] ?? 'Original account'),
            'impersonation_path'       => $path,
            'impersonation_current_session_id' => (int) ($currentFrame['session_id'] ?? 0),
            'original_user_id'         => (int) ($rootFrame['actor_user_id'] ?? 0),
            'original_role_code'       => (string) ($rootFrame['actor_role_code'] ?? ''),
            'original_tenant_id'       => $rootFrame['actor_tenant_id'] ?? null,
            'original_branch_id'       => $rootFrame['actor_branch_id'] ?? null,
            'original_user_email'      => (string) ($rootFrame['actor_email'] ?? ''),
            'original_user_name'       => (string) ($rootFrame['actor_name'] ?? 'Original account'),
        ]);
    }

    protected function closeSessionRecord(int $sessionId): void
    {
        if ($sessionId < 1) {
            return;
        }

        $this->sessionModel->update($sessionId, [
            'ended_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function getCurrentUserDisplayName(): string
    {
        $name = trim((string) session()->get('user_first_name') . ' ' . (string) session()->get('user_last_name'));
        if ($name !== '') {
            return $name;
        }

        return (string) session()->get('user_email') ?: 'User';
    }

    protected function formatUserDisplayName(object $user): string
    {
        $name = trim((string) ($user->first_name ?? '') . ' ' . (string) ($user->last_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return (string) ($user->email ?? 'User');
    }
}
