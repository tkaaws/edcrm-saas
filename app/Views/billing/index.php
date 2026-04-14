<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Subscription workspace</h2>
            <p class="module-subtitle">Your plan, included modules, and current capacity usage.</p>
        </div>
    </div>

    <?php if (! $subscription): ?>
        <div class="shell-alert shell-alert--warning">
            No active subscription found. Contact the platform administrator to activate billing for this tenant.
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

            $renderBar = static function (string $label, array $summary): void {
                $limit   = $summary['limit'];
                $current = $summary['current'];
                $over    = $summary['over_limit'];

                if ($limit === 0) {
                    echo '<div style="margin-bottom:1rem">';
                    echo '<div style="display:flex;justify-content:space-between;margin-bottom:.35rem">';
                    echo '<strong>' . esc($label) . '</strong><span class="text-muted">No entitlement</span>';
                    echo '</div></div>';
                    return;
                }

                $pct       = $limit === -1 ? 0 : min(100, (int) round(($current / max(1, $limit)) * 100));
                $barClass  = $over ? 'usage-bar__fill--danger' : ($pct >= 80 ? 'usage-bar__fill--warn' : 'usage-bar__fill--ok');
                $limitText = $limit === -1 ? 'Unlimited' : (string) $limit;

                echo '<div style="margin-bottom:1rem">';
                echo '<div style="display:flex;justify-content:space-between;margin-bottom:.35rem">';
                echo '<strong>' . esc($label) . '</strong><span>' . esc((string) $current) . ' / ' . esc($limitText) . '</span>';
                echo '</div>';
                if ($limit !== -1) {
                    echo '<div class="usage-bar"><div class="usage-bar__fill ' . $barClass . '" style="width:' . $pct . '%"></div></div>';
                }
                echo '</div>';
            };
        ?>

        <div class="detail-grid">
            <article class="detail-card">
                <h3 class="detail-card__title">Current plan</h3>
                <dl class="context-list">
                    <div><dt>Status</dt><dd><span class="status-badge <?= $statusClass ?>"><?= esc($statusLabel) ?></span></dd></div>
                    <div><dt>Plan</dt><dd><strong><?= esc($plan?->name ?? '-') ?></strong></dd></div>
                    <div><dt>Plan code</dt><dd><code><?= esc($plan?->code ?? '-') ?></code></dd></div>
                    <div><dt>Billing cycle</dt><dd><?= esc(ucfirst($subscription->billing_cycle)) ?></dd></div>
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
                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--line);">
                        <p class="module-subtitle">Plan upgrades, renewals, and module changes are handled by the platform team.</p>
                    </div>
                <?php endif; ?>
            </article>

            <article class="detail-card">
                <h3 class="detail-card__title">Capacity usage</h3>
                <?php $renderBar('Users', $usersSummary); ?>
                <?php $renderBar('Branches', $branchesSummary); ?>

                <?php if ($usersSummary['over_limit'] || $branchesSummary['over_limit']): ?>
                    <div class="shell-alert shell-alert--warning" style="margin-top:.75rem;">
                        One or more plan limits have been exceeded. Ask the platform team to upgrade the tenant plan or reduce records.
                    </div>
                <?php endif; ?>
            </article>

            <article class="detail-card detail-card--wide">
                <h3 class="detail-card__title">Module access</h3>
                <p class="module-subtitle" style="margin-bottom:1rem;">Only modules included in the assigned plan are enabled for this tenant.</p>
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
