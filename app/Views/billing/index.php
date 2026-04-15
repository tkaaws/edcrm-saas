<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Plan and usage</h2>
            <p class="module-subtitle">See your current plan, included features, and current usage.</p>
        </div>
    </div>

    <?php if (! $subscription): ?>
        <div class="shell-alert shell-alert--warning">
            No active plan was found. Contact the EDCRM team to activate this company.
        </div>
    <?php else: ?>
        <?php
            $statusClass = match ($effectiveStatus) {
                'active'    => 'status-badge--good',
                'trial'     => 'status-badge--info',
                'grace'     => 'status-badge--warning',
                'suspended' => 'status-badge--warning',
                default     => 'status-badge--neutral',
            };

            $statusLabel = match ($effectiveStatus) {
                'trial'     => 'Trial',
                'active'    => 'Active',
                'grace'     => 'Grace Period',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
                'expired'   => 'Expired',
                default     => ucfirst($effectiveStatus),
            };

            $planName = $plan?->name ?? '-';
            $cycleLabel = ucfirst($subscription->billing_cycle);
            $timelineLabel = 'Started';
            $timelineValue = $subscription->starts_at ? date('d M Y', strtotime($subscription->starts_at)) : '-';
            $daysLeft = null;

            if ($effectiveStatus === 'trial' && $subscription->trial_ends_at) {
                $timelineLabel = 'Trial ends';
                $timelineValue = date('d M Y', strtotime($subscription->trial_ends_at));
                $daysLeft = max(0, (int) ceil((strtotime($subscription->trial_ends_at) - time()) / 86400));
            } elseif ($effectiveStatus === 'active' && $subscription->renews_at) {
                $timelineLabel = 'Next renewal';
                $timelineValue = date('d M Y', strtotime($subscription->renews_at));
            } elseif ($effectiveStatus === 'grace' && $subscription->grace_ends_at) {
                $timelineLabel = 'Grace ends';
                $timelineValue = date('d M Y', strtotime($subscription->grace_ends_at));
            } elseif ($subscription->expires_at) {
                $timelineLabel = 'Expires';
                $timelineValue = date('d M Y', strtotime($subscription->expires_at));
            }

            $renderBar = static function (string $label, array $summary): void {
                $limit   = $summary['limit'];
                $current = $summary['current'];
                $over    = $summary['over_limit'];

                if ($limit === 0) {
                    echo '<div class="usage-item">';
                    echo '<div class="usage-item__top">';
                    echo '<strong>' . esc($label) . '</strong><span class="text-muted">No entitlement</span>';
                    echo '</div>';
                    echo '<p class="usage-item__hint">This plan does not include ' . esc(strtolower($label)) . ' access.</p>';
                    echo '</div>';
                    return;
                }

                $pct       = $limit === -1 ? 0 : min(100, (int) round(($current / max(1, $limit)) * 100));
                $barClass  = $over ? 'usage-bar__fill--danger' : ($pct >= 80 ? 'usage-bar__fill--warn' : 'usage-bar__fill--ok');
                $limitText = $limit === -1 ? 'Unlimited' : (string) $limit;

                echo '<div class="usage-item">';
                echo '<div class="usage-item__top">';
                echo '<strong>' . esc($label) . '</strong><span>' . esc((string) $current) . ' / ' . esc($limitText) . '</span>';
                echo '</div>';
                if ($limit !== -1) {
                    echo '<div class="usage-bar"><div class="usage-bar__fill ' . $barClass . '" style="width:' . $pct . '%"></div></div>';
                    echo '<p class="usage-item__hint">' . esc((string) $pct) . '% of your included capacity is currently in use.</p>';
                } else {
                    echo '<p class="usage-item__hint">This item is available without a fixed limit on your current plan.</p>';
                }
                echo '</div>';
            };
        ?>

        <div class="billing-overview">
            <article class="billing-kpi">
                <span class="billing-kpi__label">Status</span>
                <div class="billing-kpi__value"><span class="status-badge <?= $statusClass ?>"><?= esc($statusLabel) ?></span></div>
                <p class="billing-kpi__meta">Your company billing state right now.</p>
            </article>
            <article class="billing-kpi">
                <span class="billing-kpi__label">Plan</span>
                <div class="billing-kpi__value"><?= esc($planName) ?></div>
                <p class="billing-kpi__meta">Features and limits come from this plan.</p>
            </article>
            <article class="billing-kpi">
                <span class="billing-kpi__label">Billing cycle</span>
                <div class="billing-kpi__value"><?= esc($cycleLabel) ?></div>
                <p class="billing-kpi__meta">How often billing and renewals are scheduled.</p>
            </article>
            <article class="billing-kpi">
                <span class="billing-kpi__label"><?= esc($timelineLabel) ?></span>
                <div class="billing-kpi__value"><?= esc($timelineValue) ?></div>
                <p class="billing-kpi__meta">
                    <?php if ($daysLeft !== null): ?>
                        <?= esc((string) $daysLeft) ?> day<?= $daysLeft === 1 ? '' : 's' ?> remaining in trial.
                    <?php else: ?>
                        Keep this date in mind for billing follow-up.
                    <?php endif; ?>
                </p>
            </article>
        </div>

        <div class="detail-grid">
            <article class="detail-card">
                <div class="detail-card__header">
                    <div>
                        <h3 class="detail-card__title">Current plan</h3>
                        <p class="detail-card__subtitle">A quick view of your company subscription details.</p>
                    </div>
                </div>
                <dl class="context-list billing-summary">
                    <div><dt>Status</dt><dd><span class="status-badge <?= $statusClass ?>"><?= esc($statusLabel) ?></span></dd></div>
                    <div><dt>Plan</dt><dd><strong><?= esc($planName) ?></strong></dd></div>
                    <div><dt>Billing cycle</dt><dd><?= esc($cycleLabel) ?></dd></div>
                    <?php if ($effectiveStatus === 'trial' && $subscription->trial_ends_at): ?>
                        <div><dt>Trial ends</dt><dd><?= esc(date('d M Y', strtotime($subscription->trial_ends_at))) ?></dd></div>
                        <div>
                            <dt>Days remaining</dt>
                            <dd>
                                <?php $daysLeft = max(0, (int) ceil((strtotime($subscription->trial_ends_at) - time()) / 86400)); ?>
                                <?= esc((string) $daysLeft) ?> day<?= $daysLeft === 1 ? '' : 's' ?>
                            </dd>
                        </div>
                    <?php elseif ($effectiveStatus === 'active' && $subscription->renews_at): ?>
                        <div><dt>Next renewal</dt><dd><?= esc(date('d M Y', strtotime($subscription->renews_at))) ?></dd></div>
                    <?php elseif ($effectiveStatus === 'grace' && $subscription->grace_ends_at): ?>
                        <div><dt>Grace ends</dt><dd><?= esc(date('d M Y', strtotime($subscription->grace_ends_at))) ?></dd></div>
                    <?php elseif ($subscription->expires_at): ?>
                        <div><dt>Expires</dt><dd><?= esc(date('d M Y', strtotime($subscription->expires_at))) ?></dd></div>
                    <?php endif; ?>
                    <div><dt>Started</dt><dd><?= $subscription->starts_at ? esc(date('d M Y', strtotime($subscription->starts_at))) : '-' ?></dd></div>
                </dl>

                <?php if (in_array($roleCode ?? '', ['tenant_owner', 'tenant_admin'], true)): ?>
                    <div class="billing-note">
                        <strong>Need a plan change?</strong>
                        <p>Plan upgrades, renewals, and feature changes are handled by the EDCRM team.</p>
                    </div>
                <?php endif; ?>
            </article>

            <article class="detail-card">
                <div class="detail-card__header">
                    <div>
                        <h3 class="detail-card__title">Capacity usage</h3>
                        <p class="detail-card__subtitle">Track where your company stands against plan limits.</p>
                    </div>
                </div>
                <?php $renderBar('Users', $usersSummary); ?>
                <?php $renderBar('Branches', $branchesSummary); ?>

                <?php if ($usersSummary['over_limit'] || $branchesSummary['over_limit']): ?>
                    <div class="shell-alert shell-alert--warning billing-alert">
                        One or more plan limits have been exceeded. Ask the EDCRM team to upgrade the company plan or reduce records.
                    </div>
                <?php endif; ?>
            </article>

            <article class="detail-card detail-card--wide">
                <div class="detail-card__header">
                    <div>
                        <h3 class="detail-card__title">Included features</h3>
                        <p class="detail-card__subtitle">Only features included in your plan are available in this workspace.</p>
                    </div>
                </div>
                <div class="module-grid">
                    <?php foreach ($allModules as $module): ?>
                        <?php $enabled = in_array($module->code, $enabledModules, true); ?>
                        <div class="module-tile <?= $enabled ? 'module-tile--on' : 'module-tile--off' ?>">
                            <div class="module-tile__name"><?= esc($module->name) ?></div>
                            <div>
                                <span class="status-badge <?= $enabled ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= $enabled ? 'Included' : 'Not included' ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
