<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Your Subscription</h2>
            <p class="module-subtitle">Current plan, module access, and capacity usage for your account.</p>
        </div>
    </div>

    <?php if (! $subscription): ?>
        <div class="shell-alert shell-alert--warning">
            No active subscription found. Contact your platform administrator to set up a subscription.
        </div>
    <?php else: ?>

    <?php
        $statusClass = match($effectiveStatus) {
            'active'    => 'status-badge--good',
            'trial'     => 'status-badge--info',
            'grace'     => 'status-badge--warning',
            'suspended' => 'status-badge--warning',
            default     => 'status-badge--neutral',
        };
        $statusLabel = match($effectiveStatus) {
            'trial'     => 'Trial',
            'active'    => 'Active',
            'grace'     => 'Grace Period',
            'suspended' => 'Suspended',
            'cancelled' => 'Cancelled',
            'expired'   => 'Expired',
            default     => ucfirst($effectiveStatus),
        };
    ?>

    <div class="detail-grid">

        <!-- SUBSCRIPTION STATUS CARD -->
        <div class="detail-card">
            <h3 class="detail-card__title">Plan</h3>
            <dl class="context-list">
                <div>
                    <dt>Status</dt>
                    <dd><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></dd>
                </div>
                <div>
                    <dt>Plan</dt>
                    <dd><strong><?= esc($plan?->name ?? '—') ?></strong></dd>
                </div>
                <div>
                    <dt>Billing cycle</dt>
                    <dd><?= esc(ucfirst($subscription->billing_cycle)) ?></dd>
                </div>

                <?php if ($effectiveStatus === 'trial' && $subscription->trial_ends_at): ?>
                    <div>
                        <dt>Trial ends</dt>
                        <dd><?= date('d M Y', strtotime($subscription->trial_ends_at)) ?></dd>
                    </div>
                    <div>
                        <dt>Days remaining</dt>
                        <dd>
                            <?php
                                $daysLeft = (int) ceil((strtotime($subscription->trial_ends_at) - time()) / 86400);
                                $daysLeft = max(0, $daysLeft);
                            ?>
                            <strong><?= $daysLeft ?></strong> day<?= $daysLeft !== 1 ? 's' : '' ?>
                        </dd>
                    </div>
                <?php elseif ($effectiveStatus === 'active' && $subscription->renews_at): ?>
                    <div>
                        <dt>Next renewal</dt>
                        <dd><?= date('d M Y', strtotime($subscription->renews_at)) ?></dd>
                    </div>
                <?php elseif ($effectiveStatus === 'grace' && $subscription->grace_ends_at): ?>
                    <div>
                        <dt>Grace period ends</dt>
                        <dd><strong class="text-warning"><?= date('d M Y', strtotime($subscription->grace_ends_at)) ?></strong></dd>
                    </div>
                <?php elseif ($subscription->expires_at): ?>
                    <div>
                        <dt>Expires</dt>
                        <dd><?= date('d M Y', strtotime($subscription->expires_at)) ?></dd>
                    </div>
                <?php endif; ?>

                <div>
                    <dt>Started</dt>
                    <dd><?= $subscription->starts_at ? date('d M Y', strtotime($subscription->starts_at)) : '—' ?></dd>
                </div>
            </dl>

            <?php if (in_array($effectiveStatus, ['trial', 'grace', 'expired', 'suspended']) && in_array($roleCode ?? '', ['tenant_owner', 'tenant_admin'])): ?>
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                    <p class="module-subtitle">To upgrade or renew your subscription, contact your account manager or reach out to support.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- CAPACITY USAGE CARD -->
        <div class="detail-card">
            <h3 class="detail-card__title">Capacity Usage</h3>

            <?php
                $renderBar = function(string $label, array $summary): void {
                    $limit   = $summary['limit'];
                    $current = $summary['current'];
                    $over    = $summary['over_limit'];

                    if ($limit === 0) {
                        echo '<div class="detail-card__row"><strong>' . esc($label) . '</strong><span class="text-muted">No plan</span></div>';
                        return;
                    }

                    $pct       = $limit === -1 ? 0 : min(100, (int) round($current / max(1, $limit) * 100));
                    $barClass  = $over ? 'usage-bar__fill--danger' : ($pct >= 80 ? 'usage-bar__fill--warn' : 'usage-bar__fill--ok');
                    $limitText = $limit === -1 ? '∞' : $limit;

                    echo '<div style="margin-bottom:1rem">';
                    echo '<div style="display:flex;justify-content:space-between;margin-bottom:.35rem">';
                    echo '<strong>' . esc($label) . '</strong>';
                    echo '<span>' . $current . ' / ' . $limitText . '</span>';
                    echo '</div>';
                    if ($limit !== -1) {
                        echo '<div class="usage-bar"><div class="usage-bar__fill ' . $barClass . '" style="width:' . $pct . '%"></div></div>';
                    }
                    echo '</div>';
                };
            ?>

            <?php $renderBar('Users', $usersSummary) ?>
            <?php $renderBar('Branches', $branchesSummary) ?>

            <?php if ($usersSummary['over_limit'] || $branchesSummary['over_limit']): ?>
                <p class="shell-alert shell-alert--warning" style="margin-top:.75rem;padding:.5rem .75rem;font-size:.85rem">
                    You have exceeded a capacity limit. New records cannot be created until you upgrade or remove existing entries.
                </p>
            <?php endif; ?>
        </div>

        <!-- MODULE ENTITLEMENTS CARD -->
        <div class="detail-card detail-card--wide">
            <h3 class="detail-card__title">Module Access</h3>
            <p class="module-subtitle" style="margin-bottom:1rem">Modules included in your current plan. Contact support to unlock additional modules.</p>
            <div class="module-grid">
                <?php foreach ($allModules as $mod): ?>
                    <?php $on = in_array($mod->code, $enabledModules, true); ?>
                    <div class="module-tile <?= $on ? 'module-tile--on' : 'module-tile--off' ?>">
                        <div class="module-tile__name"><?= esc($mod->name) ?></div>
                        <div class="module-tile__status">
                            <span class="status-badge <?= $on ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                <?= $on ? 'Included' : 'Not included' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <?php endif; ?>
</section>

<style>
.usage-bar {
    height: 6px;
    background: var(--surface-alt, #f0f0f0);
    border-radius: 3px;
    overflow: hidden;
}
.usage-bar__fill {
    height: 100%;
    border-radius: 3px;
    transition: width .3s;
}
.usage-bar__fill--ok      { background: var(--color-success, #22c55e); }
.usage-bar__fill--warn    { background: var(--color-warning, #f59e0b); }
.usage-bar__fill--danger  { background: var(--color-danger, #ef4444); }

.module-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: .75rem;
}
.module-tile {
    padding: .875rem 1rem;
    border-radius: 6px;
    border: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    gap: .4rem;
}
.module-tile--off {
    opacity: .6;
    background: var(--surface-alt, #fafafa);
}
.module-tile__name {
    font-weight: 600;
    font-size: .875rem;
}
</style>
<?= $this->endSection() ?>
