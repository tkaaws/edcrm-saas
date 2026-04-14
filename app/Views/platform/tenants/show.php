<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
    $db          = db_connect();
    $subscription = $db->query("
        SELECT s.*, p.name AS plan_name, p.code AS plan_code
        FROM subscriptions s
        JOIN plans p ON p.id = s.plan_id
        WHERE s.tenant_id = ? AND s.status NOT IN ('cancelled','expired')
        ORDER BY s.id DESC LIMIT 1
    ", [$tenant->id])->getRow();

    $userCount   = $db->table('users')->where('tenant_id', $tenant->id)->countAllResults();
    $branchCount = $db->table('tenant_branches')->where('tenant_id', $tenant->id)->countAllResults();
?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($tenant->name) ?></h2>
            <p class="module-subtitle">Tenant profile, status control, and subscription.</p>
        </div>
        <div style="display:flex;gap:.5rem;">
            <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants/' . $tenant->id . '/edit') ?>">Edit</a>
            <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants') ?>">Back</a>
        </div>
    </div>

    <div class="settings-grid">

        {{-- Profile --}}
        <div class="form-card">
            <div class="module-toolbar" style="margin-bottom:1rem;">
                <h3 class="module-title module-title--small">Profile</h3>
                <span class="status-badge <?= $tenant->status === 'active' ? 'status-badge--good' : ($tenant->status === 'suspended' ? 'status-badge--warm' : 'status-badge--neutral') ?>">
                    <?= esc(ucfirst($tenant->status)) ?>
                </span>
            </div>

            <dl class="context-list context-list--wide">
                <div><dt>Slug</dt><dd><code><?= esc($tenant->slug) ?></code></dd></div>
                <div><dt>Legal name</dt><dd><?= esc($tenant->legal_name ?: '—') ?></dd></div>
                <div><dt>Owner</dt><dd><?= esc($tenant->owner_name ?: '—') ?></dd></div>
                <div><dt>Owner email</dt><dd><?= esc($tenant->owner_email ?: '—') ?></dd></div>
                <div><dt>Owner phone</dt><dd><?= esc($tenant->owner_phone ?: '—') ?></dd></div>
                <div><dt>Timezone</dt><dd><?= esc($tenant->default_timezone ?: '—') ?></dd></div>
                <div><dt>Currency</dt><dd><?= esc($tenant->default_currency_code ?: '—') ?></dd></div>
                <div><dt>Country</dt><dd><?= esc($tenant->country_code ?: '—') ?></dd></div>
                <div><dt>Created</dt><dd><?= esc($tenant->created_at) ?></dd></div>
            </dl>

            <div style="display:flex;gap:.5rem;margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--border);">
                <span class="status-badge status-badge--neutral"><?= $userCount ?> users</span>
                <span class="status-badge status-badge--neutral"><?= $branchCount ?> branches</span>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:1.5rem;">

            {{-- Status control --}}
            <div class="form-card">
                <h3 class="module-title module-title--small" style="margin-bottom:1rem;">Status control</h3>

                <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/status') ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <label class="field field--full">
                            <span>Set status</span>
                            <select name="status">
                                <?php foreach (['active', 'suspended', 'draft', 'cancelled'] as $s): ?>
                                    <option value="<?= esc($s) ?>" <?= $tenant->status === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="form-actions">
                        <button class="shell-button shell-button--primary" type="submit">Update status</button>
                    </div>
                </form>
            </div>

            {{-- Subscription / permissions --}}
            <div class="form-card">
                <h3 class="module-title module-title--small" style="margin-bottom:1rem;">Subscription &amp; permissions</h3>

                <?php if ($subscription): ?>
                    <dl class="context-list context-list--wide">
                        <div><dt>Plan</dt><dd><?= esc($subscription->plan_name) ?> <code style="font-size:.75rem;"><?= esc($subscription->plan_code) ?></code></dd></div>
                        <div><dt>Cycle</dt><dd><?= esc(ucfirst($subscription->billing_cycle)) ?></dd></div>
                        <div><dt>Status</dt>
                            <dd>
                                <span class="status-badge <?= in_array($subscription->status, ['trial','active']) ? 'status-badge--good' : ($subscription->status === 'grace' ? 'status-badge--warm' : 'status-badge--neutral') ?>">
                                    <?= esc(ucfirst($subscription->status)) ?>
                                </span>
                            </dd>
                        </div>
                        <?php if ($subscription->trial_ends_at): ?>
                            <div><dt>Trial ends</dt><dd><?= esc($subscription->trial_ends_at) ?></dd></div>
                        <?php endif; ?>
                        <?php if ($subscription->current_period_ends_at): ?>
                            <div><dt>Renews</dt><dd><?= esc($subscription->current_period_ends_at) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <div class="form-actions">
                        <a href="<?= site_url('platform/subscriptions/' . $subscription->id) ?>" class="shell-button shell-button--primary">Manage subscription &amp; features</a>
                    </div>
                <?php else: ?>
                    <p style="color:var(--muted);margin-bottom:1rem;">No active subscription. Assign a plan to unlock module access.</p>
                    <a href="<?= site_url('platform/subscriptions?tenant_id=' . $tenant->id) ?>" class="shell-button shell-button--primary">Assign subscription</a>
                <?php endif; ?>
            </div>

            {{-- Danger zone --}}
            <div class="form-card form-card--danger">
                <h3 class="module-title module-title--small" style="margin-bottom:.5rem;color:var(--red,#c0392b);">Danger zone</h3>
                <p style="color:var(--muted);margin-bottom:1rem;font-size:.875rem;">Permanently deletes the tenant and all associated data — branches, users, roles, settings. This cannot be undone.</p>

                <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/delete') ?>"
                      onsubmit="return confirm('Delete <?= esc(addslashes($tenant->name)) ?> permanently? This cannot be undone.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="shell-button shell-button--danger">Delete tenant</button>
                </form>
            </div>

        </div>
    </div>
</section>
<?= $this->endSection() ?>
