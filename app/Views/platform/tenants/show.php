<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($tenant->name) ?></h2>
            <p class="module-subtitle">Tenant detail, status management, and provisioning overview.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants') ?>">Back to tenants</a>
    </div>

    <div class="settings-grid">
        <div class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Profile</h3>
                </div>
                <span class="status-badge <?= $tenant->status === 'active' ? 'status-badge--good' : ($tenant->status === 'suspended' ? 'status-badge--warm' : 'status-badge--neutral') ?>">
                    <?= esc(ucfirst($tenant->status)) ?>
                </span>
            </div>

            <dl class="context-list context-list--wide">
                <div><dt>Slug</dt><dd><?= esc($tenant->slug) ?></dd></div>
                <div><dt>Legal name</dt><dd><?= esc($tenant->legal_name ?: '—') ?></dd></div>
                <div><dt>Owner name</dt><dd><?= esc($tenant->owner_name ?: '—') ?></dd></div>
                <div><dt>Owner email</dt><dd><?= esc($tenant->owner_email ?: '—') ?></dd></div>
                <div><dt>Owner phone</dt><dd><?= esc($tenant->owner_phone ?: '—') ?></dd></div>
                <div><dt>Timezone</dt><dd><?= esc($tenant->default_timezone ?: '—') ?></dd></div>
                <div><dt>Currency</dt><dd><?= esc($tenant->default_currency_code ?: '—') ?></dd></div>
                <div><dt>Country</dt><dd><?= esc($tenant->country_code ?: '—') ?></dd></div>
                <div><dt>Locale</dt><dd><?= esc($tenant->locale_code ?: '—') ?></dd></div>
                <div><dt>Created</dt><dd><?= esc($tenant->created_at) ?></dd></div>
            </dl>
        </div>

        <div class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Status control</h3>
                    <p class="module-subtitle">Change this tenant's operational status. Suspended tenants cannot perform write operations.</p>
                </div>
            </div>

            <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/status') ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="field field--full">
                        <span>New status</span>
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
    </div>
</section>
<?= $this->endSection() ?>
