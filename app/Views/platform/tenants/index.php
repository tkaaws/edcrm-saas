<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Platform tenants</h2>
            <p class="module-subtitle">Provision and review institute accounts from the platform administration layer.</p>
        </div>
        <a class="shell-button shell-button--primary" href="<?= site_url('platform/tenants/create') ?>">Create tenant</a>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Institute</th>
                    <th>Slug</th>
                    <th>Owner email</th>
                    <th>Timezone</th>
                    <th>Currency</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tenants === []): ?>
                    <tr>
                        <td colspan="6" class="empty-state">No tenants yet. Create the first institute from platform onboarding.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($tenants as $tenant): ?>
                    <tr>
                        <td>
                            <div class="entity-cell">
                                <strong><?= esc($tenant->name) ?></strong>
                                <span><?= esc($tenant->legal_name ?: 'No legal name set') ?></span>
                            </div>
                        </td>
                        <td><?= esc($tenant->slug) ?></td>
                        <td><?= esc($tenant->owner_email ?: 'Not set') ?></td>
                        <td><?= esc($tenant->default_timezone ?: 'UTC') ?></td>
                        <td><?= esc($tenant->default_currency_code ?: 'USD') ?></td>
                        <td>
                            <span class="status-badge <?= $tenant->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                <?= esc(ucfirst($tenant->status)) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
