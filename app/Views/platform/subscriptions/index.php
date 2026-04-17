<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Manage active, trial, and cancelled company subscriptions.</p>
        </div>
    </div>

    <div class="detail-card">
        <div class="form-section-header">
            <h3 class="detail-card__title">Attach subscription</h3>
            <p class="detail-card__subtitle">Assign a plan and start billing for a company.</p>
        </div>
        <form method="post" action="<?= site_url('platform/subscriptions/attach') ?>" class="inline-form inline-form--compact">
            <?= csrf_field() ?>
            <select name="tenant_id" class="shell-input input-compact--wide" required>
                <option value="">Select company</option>
                <?php foreach ($tenants as $tenant): ?>
                    <option value="<?= esc($tenant->id) ?>"><?= esc($tenant->name) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="plan_id" class="shell-input input-compact--wide" required>
                <option value="">- Select plan -</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?= esc($plan->id) ?>"><?= esc($plan->name) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="billing_cycle" class="shell-input">
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="yearly">Yearly</option>
            </select>
            <input type="number" name="trial_days" class="shell-input input-compact" value="14" min="0" max="90" placeholder="Trial days">
            <button type="submit" class="shell-button shell-button--primary">Attach</button>
        </form>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Cycle</th>
                        <th>Status</th>
                        <th>Starts</th>
                        <th>Renews / Expires</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscriptions)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">No subscriptions found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($subscriptions as $subscription): ?>
                        <?php
                            $statusClass = match ($subscription->status) {
                                'active'    => 'status-badge--good',
                                'trial'     => 'status-badge--info',
                                'grace'     => 'status-badge--warning',
                                'suspended' => 'status-badge--warning',
                                default     => 'status-badge--neutral',
                            };
                        ?>
                        <tr>
                            <td data-label="Subscription #"><?= esc((string) $subscription->id) ?></td>
                            <td data-label="Company">
                                <div class="entity-cell">
                                    <strong><?= esc($subscription->tenant_name) ?></strong>
                                    <span><?= esc($subscription->tenant_slug) ?></span>
                                </div>
                            </td>
                            <td data-label="Plan"><?= esc($subscription->plan_name) ?> <small class="text-muted">(<?= esc($subscription->plan_code) ?>)</small></td>
                            <td data-label="Cycle"><?= esc(ucfirst($subscription->billing_cycle)) ?></td>
                            <td data-label="Status"><span class="status-badge <?= $statusClass ?>"><?= esc(ucfirst($subscription->status)) ?></span></td>
                            <td data-label="Starts"><?= $subscription->starts_at ? esc(date('d M Y', strtotime($subscription->starts_at))) : '-' ?></td>
                            <td data-label="Renews / Expires">
                                <?php if ($subscription->status === 'trial' && $subscription->trial_ends_at): ?>
                                    Trial ends <?= esc(date('d M Y', strtotime($subscription->trial_ends_at))) ?>
                                <?php elseif ($subscription->renews_at): ?>
                                    <?= esc(date('d M Y', strtotime($subscription->renews_at))) ?>
                                <?php elseif ($subscription->expires_at): ?>
                                    Exp <?= esc(date('d M Y', strtotime($subscription->expires_at))) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="data-table__actions" data-label="Actions">
                                <div class="table-actions">
                                    <a href="<?= site_url('platform/subscriptions/' . $subscription->id) ?>" class="shell-button shell-button--ghost shell-button--sm">View</a>
                                    <?php if (in_array($subscription->status, ['trial', 'active', 'grace', 'suspended'], true)): ?>
                                        <form method="post" action="<?= site_url('platform/subscriptions/' . $subscription->id . '/status') ?>" onsubmit="return confirm('Cancel subscription #<?= esc((string) $subscription->id) ?> for <?= esc(addslashes($subscription->tenant_name)) ?>?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="cancelled">
                                            <input type="hidden" name="note" value="Cancelled from subscriptions list">
                                            <button type="submit" class="shell-button shell-button--soft shell-button--sm">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (in_array($subscription->status, ['cancelled', 'expired'], true)): ?>
                                        <form method="post" action="<?= site_url('platform/subscriptions/' . $subscription->id . '/delete') ?>" onsubmit="return confirm('Delete subscription #<?= esc((string) $subscription->id) ?> permanently?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="shell-button shell-button--danger shell-button--sm">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
