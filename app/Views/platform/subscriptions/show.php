<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Subscription #<?= $subscription->id ?></h2>
            <p class="module-subtitle"><?= esc($tenant?->name ?? 'Unknown tenant') ?> — <?= esc($plan?->name ?? 'Unknown plan') ?></p>
        </div>
        <a href="<?= site_url('platform/subscriptions') ?>" class="shell-button shell-button--ghost">← All Subscriptions</a>
    </div>

    <div class="detail-grid">

        <!-- SUBSCRIPTION SUMMARY -->
        <div class="detail-card">
            <h3 class="detail-card__title">Details</h3>
            <?php
                $statusClass = match($subscription->status) {
                    'active'    => 'status-badge--good',
                    'trial'     => 'status-badge--info',
                    'grace'     => 'status-badge--warning',
                    'suspended' => 'status-badge--warning',
                    default     => 'status-badge--neutral',
                };
            ?>
            <dl class="context-list">
                <div><dt>Status (stored)</dt><dd><span class="status-badge <?= $statusClass ?>"><?= esc(ucfirst($subscription->status)) ?></span></dd></div>
                <div><dt>Status (effective)</dt><dd><span class="status-badge <?= match($effectiveStatus) { 'active','trial' => 'status-badge--good', 'grace','suspended' => 'status-badge--warning', default => 'status-badge--neutral' } ?>"><?= esc(ucfirst($effectiveStatus)) ?></span></dd></div>
                <div><dt>Plan</dt><dd><?= esc($plan?->name ?? '—') ?></dd></div>
                <div><dt>Billing cycle</dt><dd><?= esc(ucfirst($subscription->billing_cycle)) ?></dd></div>
                <div><dt>Starts</dt><dd><?= $subscription->starts_at ? date('d M Y', strtotime($subscription->starts_at)) : '—' ?></dd></div>
                <div><dt>Trial ends</dt><dd><?= $subscription->trial_ends_at ? date('d M Y', strtotime($subscription->trial_ends_at)) : '—' ?></dd></div>
                <div><dt>Renews</dt><dd><?= $subscription->renews_at ? date('d M Y', strtotime($subscription->renews_at)) : '—' ?></dd></div>
                <div><dt>Expires</dt><dd><?= $subscription->expires_at ? date('d M Y', strtotime($subscription->expires_at)) : '—' ?></dd></div>
                <div><dt>Grace ends</dt><dd><?= $subscription->grace_ends_at ? date('d M Y', strtotime($subscription->grace_ends_at)) : '—' ?></dd></div>
                <div><dt>Cancelled</dt><dd><?= $subscription->cancelled_at ? date('d M Y', strtotime($subscription->cancelled_at)) : '—' ?></dd></div>
            </dl>
        </div>

        <!-- TENANT LINK -->
        <?php if ($tenant): ?>
        <div class="detail-card">
            <h3 class="detail-card__title">Tenant</h3>
            <dl class="context-list">
                <div><dt>Name</dt><dd><?= esc($tenant->name) ?></dd></div>
                <div><dt>Slug</dt><dd><code><?= esc($tenant->slug) ?></code></dd></div>
                <div><dt>Status</dt><dd>
                    <span class="status-badge <?= $tenant->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                        <?= esc(ucfirst($tenant->status)) ?>
                    </span>
                </dd></div>
            </dl>
            <div style="margin-top:1rem;">
                <a href="<?= site_url('platform/tenants/' . $tenant->id) ?>" class="shell-button shell-button--ghost shell-button--sm">View tenant</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- CHANGE STATUS -->
        <div class="detail-card">
            <h3 class="detail-card__title">Change Status</h3>
            <form method="post" action="<?= site_url("platform/subscriptions/{$subscription->id}/status") ?>">
                <?= csrf_field() ?>
                <div style="margin-bottom:.75rem">
                    <label class="shell-label">New status</label>
                    <select name="status" class="shell-input" required>
                        <?php foreach (['trial','active','grace','suspended','cancelled','expired'] as $s): ?>
                            <option value="<?= $s ?>" <?= $subscription->status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom:.75rem">
                    <label class="shell-label">Note (optional)</label>
                    <input type="text" name="note" class="shell-input" placeholder="Reason for change">
                </div>
                <button type="submit" class="shell-button">Apply</button>
            </form>
        </div>

        <!-- FEATURE OVERRIDES -->
        <div class="detail-card detail-card--wide">
            <h3 class="detail-card__title">Feature Overrides</h3>
            <p class="module-subtitle" style="margin-bottom:1rem">Per-subscription overrides supersede plan-level entitlements.</p>

            <?php if (! empty($overrides)): ?>
                <table class="data-table" style="margin-bottom:1.5rem">
                    <thead>
                        <tr><th>Feature code</th><th>Enabled</th><th>Limit value</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overrides as $ov): ?>
                            <tr>
                                <td><code><?= esc($ov->feature_code) ?></code></td>
                                <td>
                                    <?php if ($ov->is_enabled === null): ?>
                                        <span class="text-muted">—</span>
                                    <?php else: ?>
                                        <span class="status-badge <?= $ov->is_enabled ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                            <?= $ov->is_enabled ? 'Yes' : 'No' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $ov->limit_value !== null ? $ov->limit_value : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <form method="post" action="<?= site_url("platform/subscriptions/{$subscription->id}/override") ?>" class="inline-form" style="flex-wrap:wrap;gap:.75rem;align-items:flex-end">
                <?= csrf_field() ?>
                <div>
                    <label class="shell-label">Feature code</label>
                    <select name="feature_code" class="shell-input" style="min-width:180px">
                        <?php foreach ($allFeatures as $feat): ?>
                            <option value="<?= esc($feat->code) ?>"><?= esc($feat->name) ?> (<?= esc($feat->code) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="shell-label">Override enabled</label>
                    <select name="is_enabled" class="shell-input">
                        <option value="">— no override —</option>
                        <option value="1">Yes (force on)</option>
                        <option value="0">No (force off)</option>
                    </select>
                </div>
                <div>
                    <label class="shell-label">Limit value</label>
                    <input type="number" name="limit_value" class="shell-input" placeholder="-1=∞ or blank" style="width:110px" min="-1">
                </div>
                <button type="submit" class="shell-button">Set Override</button>
            </form>
        </div>

        <!-- ADD-ONS -->
        <?php if (! empty($addOns)): ?>
        <div class="detail-card detail-card--wide">
            <h3 class="detail-card__title">Add-ons</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Qty</th><th>Unit price</th><th>Status</th><th>Starts</th><th>Ends</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($addOns as $a): ?>
                        <tr>
                            <td><code><?= esc($a->code) ?></code></td>
                            <td><?= esc($a->name) ?></td>
                            <td><?= $a->quantity ?></td>
                            <td>₹<?= number_format($a->unit_price_amount / 100, 0) ?></td>
                            <td><span class="status-badge <?= $a->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>"><?= esc(ucfirst($a->status)) ?></span></td>
                            <td><?= $a->starts_at ? date('d M Y', strtotime($a->starts_at)) : '—' ?></td>
                            <td><?= $a->ends_at ? date('d M Y', strtotime($a->ends_at)) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- BILLING EVENTS -->
        <div class="detail-card detail-card--wide">
            <h3 class="detail-card__title">Billing Event Log</h3>
            <?php if (empty($events)): ?>
                <p class="text-muted">No events recorded.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>Event</th><th>Transition</th><th>Summary</th><th>By</th><th>When</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): ?>
                            <tr>
                                <td><code><?= esc($ev->event_type) ?></code></td>
                                <td>
                                    <?php if ($ev->from_status || $ev->to_status): ?>
                                        <?= esc($ev->from_status ?? '—') ?> → <?= esc($ev->to_status ?? '—') ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($ev->summary ?? '—') ?></td>
                                <td><?= $ev->performed_by ? '#' . $ev->performed_by : 'System' ?></td>
                                <td><?= date('d M Y H:i', strtotime($ev->created_at)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- DANGER ZONE -->
        <div class="detail-card">
            <h3 class="detail-card__title" style="color:var(--red,#c0392b);">Danger zone</h3>
            <p style="color:var(--muted);font-size:.875rem;margin-bottom:1rem;">Delete this subscription record. Only allowed when status is cancelled or expired.</p>
            <form method="post" action="<?= site_url("platform/subscriptions/{$subscription->id}/delete") ?>"
                  onsubmit="return confirm('Delete subscription #<?= $subscription->id ?> permanently?')">
                <?= csrf_field() ?>
                <button type="submit" class="shell-button shell-button--danger shell-button--sm">Delete subscription</button>
            </form>
        </div>

    </div>
</section>
<?= $this->endSection() ?>
