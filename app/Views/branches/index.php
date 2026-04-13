<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Tenant branches</h2>
            <p class="module-subtitle">Manage institute locations, region-specific settings, and branch availability.</p>
        </div>
        <a class="shell-button shell-button--primary" href="<?= site_url('branches/create') ?>">Create branch</a>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Code</th>
                    <th>City</th>
                    <th>Timezone</th>
                    <th>Currency</th>
                    <th>Status</th>
                    <th class="data-table__actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($branches === []): ?>
                    <tr>
                        <td colspan="7" class="empty-state">No branches yet. Create the first branch for this tenant.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($branches as $branch): ?>
                    <tr>
                        <td>
                            <div class="entity-cell">
                                <strong><?= esc($branch->name) ?></strong>
                                <span><?= esc($branch->type ?: 'General branch') ?></span>
                            </div>
                        </td>
                        <td><?= esc($branch->code) ?></td>
                        <td><?= esc($branch->city ?: 'Not set') ?></td>
                        <td><?= esc($branch->timezone ?: 'Tenant default') ?></td>
                        <td><?= esc($branch->currency_code ?: 'Tenant default') ?></td>
                        <td>
                            <span class="status-badge <?= $branch->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                <?= esc(ucfirst($branch->status)) ?>
                            </span>
                        </td>
                        <td class="data-table__actions">
                            <div class="table-actions">
                                <a class="shell-button shell-button--ghost" href="<?= site_url('branches/' . $branch->id . '/edit') ?>">Edit</a>
                                <form method="post" action="<?= site_url('branches/' . $branch->id . '/status') ?>">
                                    <?= csrf_field() ?>
                                    <button class="shell-button shell-button--soft" type="submit">
                                        <?= $branch->status === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
