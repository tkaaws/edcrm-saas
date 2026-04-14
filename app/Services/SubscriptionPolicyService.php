<?php

namespace App\Services;

use App\Models\SubscriptionModel;
use App\Models\PlanModel;
use CodeIgniter\Database\BaseConnection;

/**
 * SubscriptionPolicyService
 *
 * Owns the subscription state machine.
 * Answers: what is the tenant's subscription status right now?
 *
 * Status values (matches subscriptions.status column):
 *   trial      — within trial period, full access
 *   active     — paid and current
 *   grace      — expired but within grace window, read access + warnings
 *   suspended  — past grace, write-blocked
 *   cancelled  — tenant cancelled, service until term end then expired
 *   expired    — no active service period
 *   none       — no subscription record exists
 *
 * State transitions:
 *   trial     → active     (payment received)
 *   trial     → expired    (trial_ends_at passed, no payment)
 *   active    → grace      (expires_at passed, grace_ends_at set)
 *   active    → cancelled  (tenant requests cancellation)
 *   grace     → suspended  (grace_ends_at passed)
 *   grace     → active     (payment received within grace)
 *   suspended → active     (payment received)
 *   cancelled → expired    (term end reached)
 */
class SubscriptionPolicyService
{
    const STATUS_TRIAL     = 'trial';
    const STATUS_ACTIVE    = 'active';
    const STATUS_GRACE     = 'grace';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED   = 'expired';
    const STATUS_NONE      = 'none';

    const GRACE_PERIOD_DAYS = 7;

    protected SubscriptionModel $subscriptionModel;
    protected PlanModel $planModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
        $this->planModel         = new PlanModel();
        $this->db                = db_connect();
    }

    // ---------------------------------------------------------------
    // STATUS RESOLUTION
    // ---------------------------------------------------------------

    /**
     * Get the effective status for a tenant.
     *
     * This method applies time-based rules on top of the stored status:
     * - If stored = trial but trial_ends_at has passed → expired
     * - If stored = active but expires_at has passed and within grace → grace
     * - If stored = active/grace but grace_ends_at has passed → suspended
     *
     * Does NOT write back to DB — call checkAndAdvanceStatus() for that.
     */
    public function getStatus(int $tenantId): string
    {
        $subscription = $this->subscriptionModel->getActiveForTenant($tenantId);

        if (! $subscription) {
            return self::STATUS_NONE;
        }

        $now = time();

        return match ($subscription->status) {
            self::STATUS_TRIAL => $this->resolveTrialStatus($subscription, $now),
            self::STATUS_ACTIVE => $this->resolveActiveStatus($subscription, $now),
            self::STATUS_GRACE => $this->resolveGraceStatus($subscription, $now),
            default => $subscription->status,
        };
    }

    /**
     * Get the active subscription record for a tenant.
     * Returns null if none exists.
     */
    public function getActiveSubscription(int $tenantId): ?object
    {
        return $this->subscriptionModel->getActiveForTenant($tenantId);
    }

    /**
     * Check if the tenant can access the system at all.
     * trial, active, grace, cancelled (within term) = allowed.
     * suspended, expired, none = blocked.
     */
    public function isAccessAllowed(int $tenantId): bool
    {
        return in_array($this->getStatus($tenantId), [
            self::STATUS_TRIAL,
            self::STATUS_ACTIVE,
            self::STATUS_GRACE,
            self::STATUS_CANCELLED, // cancelled but still within paid term
        ]);
    }

    /**
     * Check if the tenant is in grace period.
     */
    public function isInGrace(int $tenantId): bool
    {
        return $this->getStatus($tenantId) === self::STATUS_GRACE;
    }

    /**
     * Check if the tenant is suspended.
     */
    public function isSuspended(int $tenantId): bool
    {
        return $this->getStatus($tenantId) === self::STATUS_SUSPENDED;
    }

    // ---------------------------------------------------------------
    // STATE TRANSITIONS
    // ---------------------------------------------------------------

    /**
     * Manually transition a subscription to a new status.
     * Writes to DB and logs a billing event.
     *
     * @param int    $subscriptionId
     * @param string $newStatus       One of the STATUS_* constants
     * @param int|null $performedBy   User ID triggering the change (null = system/cron)
     * @param string|null $summary    Optional note for billing event log
     */
    public function transitionTo(int $subscriptionId, string $newStatus, ?int $performedBy = null, ?string $summary = null): bool
    {
        $subscription = $this->subscriptionModel->find($subscriptionId);

        if (! $subscription) {
            return false;
        }

        $oldStatus = $subscription->status;

        if ($oldStatus === $newStatus) {
            return true; // already in target status
        }

        $updates = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];

        // Apply side effects for specific transitions
        switch ($newStatus) {
            case self::STATUS_GRACE:
                if (! $subscription->grace_ends_at) {
                    $updates['grace_ends_at'] = date('Y-m-d H:i:s', strtotime('+' . self::GRACE_PERIOD_DAYS . ' days'));
                }
                break;

            case self::STATUS_CANCELLED:
                $updates['cancelled_at'] = date('Y-m-d H:i:s');
                break;

            case self::STATUS_ACTIVE:
                // Clear grace flag on reactivation
                $updates['grace_ends_at'] = null;
                break;
        }

        $this->subscriptionModel->update($subscriptionId, $updates);

        $this->logBillingEvent(
            tenantId:       (int) $subscription->tenant_id,
            subscriptionId: $subscriptionId,
            eventType:      'status_changed',
            fromStatus:     $oldStatus,
            toStatus:       $newStatus,
            performedBy:    $performedBy,
            summary:        $summary ?? "Status changed from {$oldStatus} to {$newStatus}",
        );

        return true;
    }

    /**
     * Check stored status against current time and advance if needed.
     * Call this from a scheduled cron job (Phase 1C) or on request.
     * Returns the new effective status.
     */
    public function checkAndAdvanceStatus(int $tenantId): string
    {
        $subscription = $this->subscriptionModel->getActiveForTenant($tenantId);

        if (! $subscription) {
            return self::STATUS_NONE;
        }

        $now       = time();
        $effective = $this->getStatus($tenantId);

        // Advance if effective differs from stored
        if ($effective !== $subscription->status) {
            $this->transitionTo((int) $subscription->id, $effective, null, 'Auto-advanced by time check');
        }

        return $effective;
    }

    // ---------------------------------------------------------------
    // PROVISIONING
    // ---------------------------------------------------------------

    /**
     * Create a trial subscription for a newly provisioned tenant.
     * Called by TenantProvisioningService after tenant is created.
     */
    public function createTrialSubscription(int $tenantId, int $planId, int $trialDays = 14): object
    {
        $now        = date('Y-m-d H:i:s');
        $trialEnds  = date('Y-m-d H:i:s', strtotime("+{$trialDays} days"));

        $id = $this->subscriptionModel->insert([
            'tenant_id'     => $tenantId,
            'plan_id'       => $planId,
            'billing_cycle' => 'monthly',
            'status'        => self::STATUS_TRIAL,
            'starts_at'     => $now,
            'trial_ends_at' => $trialEnds,
            'expires_at'    => $trialEnds,
        ]);

        $this->logBillingEvent(
            tenantId:       $tenantId,
            subscriptionId: (int) $id,
            eventType:      'subscription_created',
            fromStatus:     null,
            toStatus:       self::STATUS_TRIAL,
            performedBy:    null,
            summary:        "Trial subscription created on plan_id={$planId} for {$trialDays} days",
        );

        return $this->subscriptionModel->find($id);
    }

    /**
     * Replace the tenant's current subscription with a newly assigned plan.
     * Used by platform admins when upgrading or downgrading a tenant.
     */
    public function replaceSubscription(
        int $tenantId,
        int $planId,
        string $billingCycle = 'monthly',
        string $activationMode = 'trial',
        int $trialDays = 14,
        ?int $performedBy = null,
        ?string $summary = null
    ): object {
        $billingCycle  = in_array($billingCycle, ['monthly', 'quarterly', 'yearly'], true) ? $billingCycle : 'monthly';
        $activationMode = $activationMode === 'active' ? 'active' : 'trial';
        $trialDays      = max(0, $trialDays);

        $existing = $this->subscriptionModel->getActiveForTenant($tenantId);

        if ($existing) {
            $this->transitionTo(
                (int) $existing->id,
                self::STATUS_CANCELLED,
                $performedBy,
                $summary ?: 'Superseded by platform plan change'
            );
        }

        $now = date('Y-m-d H:i:s');

        if ($activationMode === 'active') {
            $periodMonths = match ($billingCycle) {
                'yearly'    => 12,
                'quarterly' => 3,
                default     => 1,
            };
            $renewsAt     = date('Y-m-d H:i:s', strtotime("+{$periodMonths} months"));

            $id = $this->subscriptionModel->insert([
                'tenant_id'     => $tenantId,
                'plan_id'       => $planId,
                'billing_cycle' => $billingCycle,
                'status'        => self::STATUS_ACTIVE,
                'starts_at'     => $now,
                'renews_at'     => $renewsAt,
                'expires_at'    => $renewsAt,
                'trial_ends_at' => null,
                'grace_ends_at' => null,
                'cancelled_at'  => null,
            ]);

            $this->logBillingEvent(
                tenantId:       $tenantId,
                subscriptionId: (int) $id,
                eventType:      'subscription_created',
                fromStatus:     null,
                toStatus:       self::STATUS_ACTIVE,
                performedBy:    $performedBy,
                summary:        $summary ?: "Active subscription created on plan_id={$planId}",
            );

            return $this->subscriptionModel->find($id);
        }

        $subscription = $this->createTrialSubscription($tenantId, $planId, $trialDays);

        $this->subscriptionModel->update((int) $subscription->id, [
            'billing_cycle' => $billingCycle,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        return $this->subscriptionModel->find((int) $subscription->id);
    }

    /**
     * Activate a subscription (e.g. after payment confirmed).
     */
    public function activate(int $subscriptionId, string $billingCycle, int $periodMonths, ?int $performedBy = null): bool
    {
        $now      = date('Y-m-d H:i:s');
        $renewsAt = date('Y-m-d H:i:s', strtotime("+{$periodMonths} months"));

        $this->subscriptionModel->update($subscriptionId, [
            'billing_cycle' => $billingCycle,
            'starts_at'     => $now,
            'renews_at'     => $renewsAt,
            'expires_at'    => $renewsAt,
            'trial_ends_at' => null,
            'grace_ends_at' => null,
        ]);

        return $this->transitionTo($subscriptionId, self::STATUS_ACTIVE, $performedBy, 'Subscription activated');
    }

    // ---------------------------------------------------------------
    // HELPERS
    // ---------------------------------------------------------------

    protected function resolveTrialStatus(object $subscription, int $now): string
    {
        if ($subscription->trial_ends_at && strtotime($subscription->trial_ends_at) < $now) {
            return self::STATUS_EXPIRED;
        }
        return self::STATUS_TRIAL;
    }

    protected function resolveActiveStatus(object $subscription, int $now): string
    {
        if (! $subscription->expires_at) {
            return self::STATUS_ACTIVE;
        }

        $expiresAt = strtotime($subscription->expires_at);

        if ($expiresAt >= $now) {
            return self::STATUS_ACTIVE;
        }

        // Expired — check if grace period applies
        $graceEndsAt = $subscription->grace_ends_at
            ? strtotime($subscription->grace_ends_at)
            : $expiresAt + (self::GRACE_PERIOD_DAYS * 86400);

        if ($now <= $graceEndsAt) {
            return self::STATUS_GRACE;
        }

        return self::STATUS_SUSPENDED;
    }

    protected function resolveGraceStatus(object $subscription, int $now): string
    {
        if ($subscription->grace_ends_at && strtotime($subscription->grace_ends_at) < $now) {
            return self::STATUS_SUSPENDED;
        }
        return self::STATUS_GRACE;
    }

    protected function logBillingEvent(
        int $tenantId,
        ?int $subscriptionId,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?int $performedBy,
        string $summary
    ): void {
        $this->db->table('billing_events')->insert([
            'tenant_id'       => $tenantId,
            'subscription_id' => $subscriptionId,
            'event_type'      => $eventType,
            'from_status'     => $fromStatus,
            'to_status'       => $toStatus,
            'summary'         => $summary,
            'performed_by'    => $performedBy,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
