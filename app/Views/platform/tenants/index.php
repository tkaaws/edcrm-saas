<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Platform tenants</h2>
            <p class="module-subtitle">Provision and manage institute accounts.</p>
        </div>
        <a class="shell-button shell-button--primary" href="<?= site_url('platform/tenants/create') ?>">Add tenant</a>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Institute</th>
                    <th>Slug</th>
                    <th>Owner email</th>
                    <th>Timezone</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tenants === []): ?>
                    <tr>
                        <td colspan="6" class="empty-state">No tenants yet.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($tenants as $tenant): ?>
                    <tr>
                        <td>
                            <div class="entity-cell">
                                <strong><?= esc($tenant->name) ?></strong>
                                <span><?= esc($tenant->legal_name ?: '—') ?></span>
                            </div>
                        </td>
                        <td><code><?= esc($tenant->slug) ?></code></td>
                        <td><?= esc($tenant->owner_email ?: '—') ?></td>
                        <td><?= esc($tenant->default_timezone ?: 'UTC') ?></td>
                        <td>
                            <span class="status-badge <?= $tenant->status === 'active' ? 'status-badge--good' : ($tenant->status === 'suspended' ? 'status-badge--warm' : 'status-badge--neutral') ?>">
                                <?= esc(ucfirst($tenant->status)) ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <a href="<?= site_url('platform/tenants/' . $tenant->id) ?>" class="shell-button shell-button--ghost shell-button--sm">View</a>
                            <a href="<?= site_url('platform/tenants/' . $tenant->id . '/edit') ?>" class="shell-button shell-button--ghost shell-button--sm">Edit</a>
                            <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/delete') ?>" style="display:inline;"
                                  onsubmit="return confirm('Delete <?= esc(addslashes($tenant->name)) ?> permanently?\nThis removes all users, branches and data.')">
                                <?= csrf_field() ?>
                                <button type="submit" class="shell-button shell-button--danger shell-button--sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
