<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateBranches = in_array('branches.create', $codes, true); ?>
    <?php $canEditBranches = in_array('branches.edit', $codes, true); ?>
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Branches</h2>
            <p class="module-subtitle">Manage company locations and decide which branches are available.</p>
        </div>
        <?php if ($canCreateBranches): ?>
            <a class="shell-button shell-button--primary" href="<?= site_url('branches/create') ?>">Add branch</a>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Branch code</th>
                        <th>City</th>
                        <th>Timezone</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th class="data-table__actions">Quick actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($branches === []): ?>
                        <tr>
                            <td colspan="7" class="empty-state">No branches yet. Add the first branch for this company.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td data-label="Branch">
                                <div class="entity-cell">
                                    <strong><?= esc($branch->name) ?></strong>
                                    <span><?= esc($branch->type ?: 'General branch') ?></span>
                                </div>
                            </td>
                            <td data-label="Branch code"><?= esc($branch->code) ?></td>
                            <td data-label="City"><?= esc($branch->city ?: 'Not set') ?></td>
                            <td data-label="Timezone"><?= esc($branch->timezone ?: 'Company default') ?></td>
                            <td data-label="Currency"><?= esc($branch->currency_code ?: 'Company default') ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?= $branch->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= esc(ucfirst($branch->status)) ?>
                                </span>
                            </td>
                            <td class="data-table__actions" data-label="Actions">
                                <div class="table-actions">
                                    <?php if ($canEditBranches): ?>
                                        <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('branches/' . $branch->id . '/settings') ?>">Settings</a>
                                        <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('branches/' . $branch->id . '/edit') ?>">Edit</a>
                                        <form method="post" action="<?= site_url('branches/' . $branch->id . '/status') ?>">
                                            <?= csrf_field() ?>
                                            <button class="shell-button shell-button--soft shell-button--sm" type="submit">
                                                <?= $branch->status === 'active' ? 'Disable' : 'Enable' ?>
                                            </button>
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
