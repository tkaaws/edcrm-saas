<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($tenant->name) ?></h2>
            <p class="module-subtitle">Tenant profile, status control, and plan assignment.</p>
        </div>
        <div style="display:flex;gap:.5rem;">
            <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants/' . $tenant->id . '/policy') ?>">Policy</a>
            <?php if (! empty($tenantOwnerUser) && (int) ($tenantOwnerUser->allow_impersonation ?? 1) === 1): ?>
                <form method="post" action="<?= site_url('impersonation/start/' . $tenantOwnerUser->id) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="reason" value="Platform tenant support access">
                    <button class="shell-button shell-button--ghost" type="submit">Login as owner</button>
                </form>
            <?php endif; ?>
            <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants/' . $tenant->id . '/edit') ?>">Edit</a>
            <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants') ?>">Back</a>
        </div>
    </div>

    <div class="settings-grid">
        <div class="form-card">
            <div class="module-toolbar" style="margin-bottom:1rem;">
                <h3 class="module-title module-title--small">Profile</h3>
                <span class="status-badge <?= $tenant->status === 'active' ? 'status-badge--good' : ($tenant->status === 'suspended' ? 'status-badge--warm' : 'status-badge--neutral') ?>">
                    <?= esc(ucfirst($tenant->status)) ?>
                </span>
            </div>

            <dl class="context-list context-list--wide">
                <div><dt>Slug</dt><dd><code><?= esc($tenant->slug) ?></code></dd></div>
                <div><dt>Legal name</dt><dd><?= esc($tenant->legal_name ?: '-') ?></dd></div>
                <div><dt>Owner</dt><dd><?= esc($tenant->owner_name ?: '-') ?></dd></div>
                <div><dt>Owner email</dt><dd><?= esc($tenant->owner_email ?: '-') ?></dd></div>
                <div><dt>Owner phone</dt><dd><?= esc($tenant->owner_phone ?: '-') ?></dd></div>
                <div><dt>Timezone</dt><dd><?= esc($tenant->default_timezone ?: '-') ?></dd></div>
                <div><dt>Currency</dt><dd><?= esc($tenant->default_currency_code ?: '-') ?></dd></div>
                <div><dt>Country</dt><dd><?= esc($tenant->country_code ?: '-') ?></dd></div>
                <div><dt>Created</dt><dd><?= esc($tenant->created_at) ?></dd></div>
            </dl>

            <div style="display:flex;gap:.5rem;margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--line);">
                <span class="status-badge status-badge--neutral"><?= esc($userCount) ?> users</span>
                <span class="status-badge status-badge--neutral"><?= esc($branchCount) ?> branches</span>
            </div>
        </div>

        <div class="detail-column">
            <div class="form-card">
                <h3 class="module-title module-title--small" style="margin-bottom:1rem;">Status control</h3>
                <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/status') ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <label class="field field--full">
                            <span>Set status</span>
                            <select name="status">
                                <?php foreach (['active', 'suspended', 'draft', 'cancelled'] as $status): ?>
                                    <option value="<?= esc($status) ?>" <?= $tenant->status === $status ? 'selected' : '' ?>><?= esc(ucfirst($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="form-actions">
                        <button class="shell-button shell-button--primary" type="submit">Update status</button>
                    </div>
                </form>
            </div>

            <div class="form-card">
                <h3 class="module-title module-title--small" style="margin-bottom:1rem;">Subscription and permissions</h3>
                <?php if ($subscription): ?>
                    <dl class="context-list context-list--wide">
                        <div><dt>Current plan</dt><dd><?= esc($subscription->plan_name) ?> <code><?= esc($subscription->plan_code) ?></code></dd></div>
                        <div><dt>Subscription ID</dt><dd>#<?= esc($subscription->id) ?></dd></div>
                        <div><dt>Cycle</dt><dd><?= esc(ucfirst($subscription->billing_cycle)) ?></dd></div>
                        <div><dt>Status</dt><dd><span class="status-badge <?= in_array($subscription->status, ['trial', 'active'], true) ? 'status-badge--good' : ($subscription->status === 'grace' ? 'status-badge--warm' : 'status-badge--neutral') ?>"><?= esc(ucfirst($subscription->status)) ?></span></dd></div>
                        <?php if ($subscription->trial_ends_at): ?>
                            <div><dt>Trial ends</dt><dd><?= esc($subscription->trial_ends_at) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <div class="form-actions">
                        <a href="<?= site_url('platform/subscriptions/' . $subscription->id) ?>" class="shell-button shell-button--ghost">Open subscription workspace</a>
                    </div>
                <?php else: ?>
                    <p class="module-subtitle" style="margin-bottom:1rem;">No active subscription yet. Assign a plan to unlock tenant modules.</p>
                <?php endif; ?>

                <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/plan') ?>" class="stack-form" style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--line);">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label class="shell-label">Plan</label>
                        <select name="plan_id" class="shell-input" required>
                            <option value="">Select plan</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= esc($plan->id) ?>" <?= $subscription && (int) $subscription->plan_id === (int) $plan->id ? 'selected' : '' ?>>
                                    <?= esc($plan->name) ?> (<?= esc($plan->code) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label class="shell-label">Billing cycle</label>
                            <select name="billing_cycle" class="shell-input">
                                <?php foreach (['monthly', 'quarterly', 'yearly'] as $cycle): ?>
                                    <option value="<?= esc($cycle) ?>" <?= ($subscription->billing_cycle ?? 'monthly') === $cycle ? 'selected' : '' ?>>
                                        <?= esc(ucfirst($cycle)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label class="shell-label">Activation mode</label>
                            <select name="activation_mode" class="shell-input">
                                <option value="trial">Start with trial</option>
                                <option value="active">Activate immediately</option>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label class="shell-label">Trial days</label>
                        <input type="number" name="trial_days" class="shell-input" value="14" min="1" max="90">
                    </div>
                    <div class="form-actions">
                        <button class="shell-button shell-button--primary" type="submit">Save tenant plan</button>
                    </div>
                </form>
            </div>

            <div class="form-card form-card--danger">
                <h3 class="module-title module-title--small" style="margin-bottom:.5rem;color:#c0392b;">Danger zone</h3>
                <p style="color:var(--muted);margin-bottom:1rem;font-size:.875rem;">
                    Permanently deletes this tenant and all its data: users, branches, roles, settings. This action cannot be undone.
                </p>
                <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/delete') ?>" onsubmit="return confirm('Delete <?= esc(addslashes($tenant->name)) ?> permanently?\n\nThis will remove all users, branches and tenant data.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="shell-button shell-button--danger">Delete tenant</button>
                </form>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
