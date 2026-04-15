<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Workspace companies</h2>
            <p class="module-subtitle">Create companies, assign plans, and manage lifecycle status.</p>
        </div>
        <a class="shell-button shell-button--primary" href="<?= site_url('platform/tenants/create') ?>">Add company</a>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Company ID</th>
                        <th>Current plan</th>
                        <th>Owner email</th>
                        <th>Timezone</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tenants === []): ?>
                        <tr>
                            <td colspan="7" class="empty-state">No companies yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($tenants as $tenant): ?>
                        <tr>
                            <td data-label="Company">
                                <div class="entity-cell">
                                    <strong><?= esc($tenant->name) ?></strong>
                                    <span><?= esc($tenant->legal_name ?: '-') ?></span>
                                </div>
                            </td>
                            <td data-label="Company ID"><code><?= esc($tenant->slug) ?></code></td>
                            <td data-label="Current plan">
                                <div class="entity-cell">
                                    <strong><?= esc($tenant->current_plan_name ?: 'Unassigned') ?></strong>
                                    <span>
                                        <?php if (! empty($tenant->current_subscription_id)): ?>
                                            <?= esc(ucfirst($tenant->current_subscription_status ?? 'active')) ?>
                                            <?php if (! empty($tenant->current_billing_cycle)): ?>
                                                | <?= esc(ucfirst($tenant->current_billing_cycle)) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            No subscription
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Owner email"><?= esc($tenant->owner_email ?: '-') ?></td>
                            <td data-label="Timezone"><?= esc($tenant->default_timezone ?: 'UTC') ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?= $tenant->status === 'active' ? 'status-badge--good' : ($tenant->status === 'suspended' ? 'status-badge--warm' : 'status-badge--neutral') ?>">
                                    <?= esc(ucfirst($tenant->status)) ?>
                                </span>
                            </td>
                            <td class="data-table__actions" data-label="Actions">
                                <div class="table-actions">
                                    <?php if (! empty($tenant->tenant_owner_user_id) && (int) ($tenant->tenant_owner_allow_impersonation ?? 1) === 1): ?>
                                        <form method="post" action="<?= site_url('impersonation/start/' . $tenant->tenant_owner_user_id) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="reason" value="Platform support access from company list">
                                            <button type="submit" class="shell-button shell-button--soft shell-button--sm">Support login</button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= site_url('platform/tenants/' . $tenant->id) ?>" class="shell-button shell-button--ghost shell-button--sm">Open</a>
                                    <a href="<?= site_url('platform/tenants/' . $tenant->id . '/edit') ?>" class="shell-button shell-button--ghost shell-button--sm">Edit</a>
                                    <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/delete') ?>" onsubmit="return confirm('Delete <?= esc(addslashes($tenant->name)) ?> permanently?\nThis removes all users, branches and data.')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="shell-button shell-button--danger shell-button--sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="data-table__subrow">
                            <td colspan="7" data-label="Plan setup">
                                <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/plan') ?>" class="inline-form inline-form--compact">
                                    <?= csrf_field() ?>
                                    <div class="field">
                                        <label class="shell-label">Plan</label>
                                        <select name="plan_id" class="shell-input shell-input--sm" required>
                                            <option value="">Select plan</option>
                                            <?php foreach ($plans as $plan): ?>
                                                <option value="<?= esc($plan->id) ?>" <?= (int) ($tenant->current_plan_id ?? 0) === (int) $plan->id ? 'selected' : '' ?>>
                                                    <?= esc($plan->name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label class="shell-label">Cycle</label>
                                        <select name="billing_cycle" class="shell-input shell-input--sm">
                                            <?php foreach (['monthly', 'quarterly', 'yearly'] as $cycle): ?>
                                                <option value="<?= esc($cycle) ?>" <?= ($tenant->current_billing_cycle ?? 'monthly') === $cycle ? 'selected' : '' ?>>
                                                    <?= esc(ucfirst($cycle)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label class="shell-label">Activation</label>
                                        <select name="activation_mode" class="shell-input shell-input--sm">
                                            <option value="trial">Start with trial</option>
                                            <option value="active">Activate immediately</option>
                                        </select>
                                    </div>
                                    <div class="field field--trial-days">
                                        <label class="shell-label">Trial days</label>
                                        <input type="number" name="trial_days" class="shell-input shell-input--sm" value="14" min="1" max="90">
                                    </div>
                                    <div class="inline-form__action">
                                        <button type="submit" class="shell-button shell-button--primary shell-button--sm">Save plan</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
