<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Platform tenants</h2>
            <p class="module-subtitle">Provision institutes, assign plans, and manage tenant lifecycle.</p>
        </div>
        <a class="shell-button shell-button--primary" href="<?= site_url('platform/tenants/create') ?>">Add tenant</a>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Institute</th>
                        <th>Slug</th>
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
                            <td colspan="7" class="empty-state">No tenants yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($tenants as $tenant): ?>
                        <tr>
                            <td>
                                <div class="entity-cell">
                                    <strong><?= esc($tenant->name) ?></strong>
                                    <span><?= esc($tenant->legal_name ?: '-') ?></span>
                                </div>
                            </td>
                            <td><code><?= esc($tenant->slug) ?></code></td>
                            <td>
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
                            <td><?= esc($tenant->owner_email ?: '-') ?></td>
                            <td><?= esc($tenant->default_timezone ?: 'UTC') ?></td>
                            <td>
                                <span class="status-badge <?= $tenant->status === 'active' ? 'status-badge--good' : ($tenant->status === 'suspended' ? 'status-badge--warm' : 'status-badge--neutral') ?>">
                                    <?= esc(ucfirst($tenant->status)) ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <a href="<?= site_url('platform/tenants/' . $tenant->id) ?>" class="shell-button shell-button--ghost shell-button--sm">View</a>
                                <a href="<?= site_url('platform/tenants/' . $tenant->id . '/edit') ?>" class="shell-button shell-button--ghost shell-button--sm">Edit</a>
                                <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/delete') ?>" onsubmit="return confirm('Delete <?= esc(addslashes($tenant->name)) ?> permanently?\nThis removes all users, branches and data.')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="shell-button shell-button--danger shell-button--sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="data-table__subrow">
                            <td colspan="7">
                                <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/plan') ?>" class="inline-form inline-form--compact">
                                    <?= csrf_field() ?>
                                    <div class="field">
                                        <label class="shell-label">Plan</label>
                                        <select name="plan_id" class="shell-input shell-input--sm" required>
                                            <option value="">Select plan</option>
                                            <?php foreach ($plans as $plan): ?>
                                                <option value="<?= esc($plan->id) ?>" <?= (int) ($tenant->current_plan_id ?? 0) === (int) $plan->id ? 'selected' : '' ?>>
                                                    <?= esc($plan->name) ?> (<?= esc($plan->code) ?>)
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
