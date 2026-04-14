<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Subscription #<?= $subscription->id ?></h2>
            <p class="module-subtitle"><?= esc($tenant?->name ?? 'Unknown tenant') ?> - <?= esc($plan?->name ?? 'Unknown plan') ?></p>
        </div>
        <a href="<?= site_url('platform/subscriptions') ?>" class="shell-button shell-button--ghost">Back to subscriptions</a>
    </div>

    <?php
        $storedStatusClass = match ($subscription->status) {
            'active'    => 'status-badge--good',
            'trial'     => 'status-badge--info',
            'grace'     => 'status-badge--warning',
            'suspended' => 'status-badge--warning',
            default     => 'status-badge--neutral',
        };

        $effectiveStatusClass = match ($effectiveStatus) {
            'active', 'trial' => 'status-badge--good',
            'grace', 'suspended' => 'status-badge--warning',
            default => 'status-badge--neutral',
        };
    ?>

    <div class="stats-grid">
        <article class="metric-card">
            <p class="metric-card__eyebrow">Stored status</p>
            <p class="metric-card__value"><span class="status-badge <?= $storedStatusClass ?>"><?= esc(ucfirst($subscription->status)) ?></span></p>
            <p class="metric-card__caption">Saved on the subscription record.</p>
        </article>
        <article class="metric-card">
            <p class="metric-card__eyebrow">Effective status</p>
            <p class="metric-card__value"><span class="status-badge <?= $effectiveStatusClass ?>"><?= esc(ucfirst($effectiveStatus)) ?></span></p>
            <p class="metric-card__caption">Resolved through the billing state machine.</p>
        </article>
        <article class="metric-card">
            <p class="metric-card__eyebrow">Billing cycle</p>
            <p class="metric-card__value"><?= esc(ucfirst($subscription->billing_cycle)) ?></p>
            <p class="metric-card__caption">Current renewal cadence for this tenant.</p>
        </article>
        <article class="metric-card">
            <p class="metric-card__eyebrow">Plan</p>
            <p class="metric-card__value"><?= esc($plan?->name ?? 'Unknown') ?></p>
            <p class="metric-card__caption"><?= esc($tenant?->name ?? 'Unknown tenant') ?></p>
        </article>
    </div>

    <div class="detail-grid">
        <article class="detail-card">
            <div class="detail-card__header">
                <div>
                    <h3 class="detail-card__title">Subscription timeline</h3>
                    <p class="detail-card__subtitle">Critical dates and lifecycle checkpoints.</p>
                </div>
            </div>
            <dl class="context-list">
                <div><dt>Starts</dt><dd><?= $subscription->starts_at ? date('d M Y', strtotime($subscription->starts_at)) : '-' ?></dd></div>
                <div><dt>Trial ends</dt><dd><?= $subscription->trial_ends_at ? date('d M Y', strtotime($subscription->trial_ends_at)) : '-' ?></dd></div>
                <div><dt>Renews</dt><dd><?= $subscription->renews_at ? date('d M Y', strtotime($subscription->renews_at)) : '-' ?></dd></div>
                <div><dt>Expires</dt><dd><?= $subscription->expires_at ? date('d M Y', strtotime($subscription->expires_at)) : '-' ?></dd></div>
                <div><dt>Grace ends</dt><dd><?= $subscription->grace_ends_at ? date('d M Y', strtotime($subscription->grace_ends_at)) : '-' ?></dd></div>
                <div><dt>Cancelled</dt><dd><?= $subscription->cancelled_at ? date('d M Y', strtotime($subscription->cancelled_at)) : '-' ?></dd></div>
            </dl>
        </article>

        <?php if ($tenant): ?>
        <article class="detail-card">
            <div class="detail-card__header">
                <div>
                    <h3 class="detail-card__title">Tenant account</h3>
                    <p class="detail-card__subtitle">Owning institute and linked platform record.</p>
                </div>
            </div>
            <dl class="context-list">
                <div><dt>Name</dt><dd><?= esc($tenant->name) ?></dd></div>
                <div><dt>Slug</dt><dd><code><?= esc($tenant->slug) ?></code></dd></div>
                <div><dt>Status</dt><dd>
                    <span class="status-badge <?= $tenant->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                        <?= esc(ucfirst($tenant->status)) ?>
                    </span>
                </dd></div>
            </dl>
            <div class="detail-card__actions">
                <a href="<?= site_url('platform/tenants/' . $tenant->id) ?>" class="shell-button shell-button--ghost shell-button--sm">View tenant</a>
            </div>
        </article>
        <?php endif; ?>

        <article class="detail-card">
            <div class="detail-card__header">
                <div>
                    <h3 class="detail-card__title">Status control</h3>
                    <p class="detail-card__subtitle">Move the subscription through billing states with an audit note.</p>
                </div>
            </div>
            <form method="post" action="<?= site_url("platform/subscriptions/{$subscription->id}/status") ?>" class="stack-form">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="shell-label">New status</label>
                    <select name="status" class="shell-input" required>
                        <?php foreach (['trial','active','grace','suspended','cancelled','expired'] as $s): ?>
                            <option value="<?= $s ?>" <?= $subscription->status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="shell-label">Note (optional)</label>
                    <input type="text" name="note" class="shell-input" placeholder="Reason for change">
                </div>
                <div class="detail-card__actions">
                    <button type="submit" class="shell-button shell-button--primary">Apply status change</button>
                </div>
            </form>
        </article>

        <article class="detail-card">
            <div class="detail-card__header">
                <div>
                    <h3 class="detail-card__title">Switch plan</h3>
                    <p class="detail-card__subtitle">Replace the current plan assignment while preserving subscription history.</p>
                </div>
            </div>
            <form method="post" action="<?= site_url("platform/subscriptions/{$subscription->id}/switch-plan") ?>" class="stack-form">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="shell-label">Plan</label>
                    <select name="plan_id" class="shell-input" required>
                        <option value="">Select plan</option>
                        <?php foreach ($plans as $candidate): ?>
                            <option value="<?= esc($candidate->id) ?>" <?= (int) $candidate->id === (int) $subscription->plan_id ? 'selected' : '' ?>>
                                <?= esc($candidate->name) ?> (<?= esc($candidate->code) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="shell-label">Billing cycle</label>
                    <select name="billing_cycle" class="shell-input">
                        <?php foreach (['monthly', 'quarterly', 'yearly'] as $cycle): ?>
                            <option value="<?= esc($cycle) ?>" <?= $subscription->billing_cycle === $cycle ? 'selected' : '' ?>>
                                <?= esc(ucfirst($cycle)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="shell-label">Activation mode</label>
                    <select name="activation_mode" class="shell-input">
                        <option value="active">Activate immediately</option>
                        <option value="trial">Start with trial</option>
                    </select>
                </div>
                <div class="field">
                    <label class="shell-label">Trial days</label>
                    <input type="number" name="trial_days" class="shell-input" value="14" min="1" max="90">
                </div>
                <div class="detail-card__actions">
                    <button type="submit" class="shell-button shell-button--primary">Switch plan</button>
                </div>
            </form>
        </article>

        <article class="detail-card detail-card--wide">
            <div class="detail-card__header">
                <div>
                    <h3 class="detail-card__title">Feature overrides</h3>
                    <p class="detail-card__subtitle">Per-subscription controls that supersede plan-level entitlements.</p>
                </div>
            </div>

            <?php if (! empty($overrides)): ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Feature code</th><th>Enabled</th><th>Limit value</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overrides as $ov): ?>
                                <tr>
                                    <td><code><?= esc($ov->feature_code) ?></code></td>
                                    <td>
                                        <?php if ($ov->is_enabled === null): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                            <span class="status-badge <?= $ov->is_enabled ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                                <?= $ov->is_enabled ? 'Yes' : 'No' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $ov->limit_value !== null ? $ov->limit_value : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= site_url("platform/subscriptions/{$subscription->id}/override") ?>" class="inline-form inline-form--subscription">
                <?= csrf_field() ?>
                <div class="field">
                    <label class="shell-label">Feature code</label>
                    <select name="feature_code" class="shell-input">
                        <?php foreach ($allFeatures as $feat): ?>
                            <option value="<?= esc($feat->code) ?>"><?= esc($feat->name) ?> (<?= esc($feat->code) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="shell-label">Override enabled</label>
                    <select name="is_enabled" class="shell-input">
                        <option value="">No enabled override</option>
                        <option value="1">Yes (force on)</option>
                        <option value="0">No (force off)</option>
                    </select>
                </div>
                <div class="field">
                    <label class="shell-label">Limit value</label>
                    <input type="number" name="limit_value" class="shell-input" placeholder="-1 for unlimited or leave blank" min="-1">
                </div>
                <div class="inline-form__action">
                    <button type="submit" class="shell-button shell-button--primary">Save override</button>
                </div>
            </form>
        </article>

        <?php if (! empty($addOns)): ?>
        <article class="detail-card detail-card--wide">
            <div class="detail-card__header">
                <div>
                    <h3 class="detail-card__title">Add-ons</h3>
                    <p class="detail-card__subtitle">Commercial add-ons attached to this subscription.</p>
                </div>
            </div>
            <div class="table-wrap">
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
                                <td>Rs <?= number_format($a->unit_price_amount / 100, 0) ?></td>
                                <td><span class="status-badge <?= $a->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>"><?= esc(ucfirst($a->status)) ?></span></td>
                                <td><?= $a->starts_at ? date('d M Y', strtotime($a->starts_at)) : '-' ?></td>
                                <td><?= $a->ends_at ? date('d M Y', strtotime($a->ends_at)) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
        <?php endif; ?>

        <article class="detail-card detail-card--wide">
            <div class="detail-card__header">
                <div>
                    <h3 class="detail-card__title">Billing event log</h3>
                    <p class="detail-card__subtitle">Historical changes recorded for this subscription.</p>
                </div>
            </div>
            <?php if (empty($events)): ?>
                <p class="text-muted">No events recorded.</p>
            <?php else: ?>
                <div class="table-wrap">
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
                                            <?= esc($ev->from_status ?? '-') ?> to <?= esc($ev->to_status ?? '-') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($ev->summary ?? '-') ?></td>
                                    <td><?= $ev->performed_by ? '#' . $ev->performed_by : 'System' ?></td>
                                    <td><?= date('d M Y H:i', strtotime($ev->created_at)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>

        <article class="detail-card detail-card--danger">
            <div class="detail-card__header">
                <div>
                    <h3 class="detail-card__title detail-card__title--danger">Danger zone</h3>
                    <p class="detail-card__subtitle">Delete this record only after the subscription has been cancelled or expired.</p>
                </div>
            </div>
            <form method="post" action="<?= site_url("platform/subscriptions/{$subscription->id}/delete") ?>"
                  onsubmit="return confirm('Delete subscription #<?= $subscription->id ?> permanently?')">
                <?= csrf_field() ?>
                <button type="submit" class="shell-button shell-button--danger shell-button--sm">Delete subscription</button>
            </form>
        </article>
    </div>
</section>
<?= $this->endSection() ?>
