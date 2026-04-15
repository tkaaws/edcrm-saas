<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateRoles = in_array('roles.create', $codes, true); ?>
    <?php $canEditRoles = in_array('roles.edit', $codes, true); ?>
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Access profiles</h2>
            <p class="module-subtitle">Create reusable access profiles for different kinds of team members.</p>
        </div>
        <?php if ($canCreateRoles): ?>
            <a class="shell-button shell-button--primary" href="<?= site_url('roles/create') ?>">Create access profile</a>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Access profile</th>
                    <th>Status</th>
                    <th class="data-table__actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($roles === []): ?>
                    <tr>
                        <td colspan="3" class="empty-state">No access profiles yet. Create the first one for your team.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td>
                            <div class="entity-cell">
                                <strong><?= esc($role->name) ?></strong>
                                <span><?= $role->is_system ? 'Standard profile' : 'Custom profile' ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?= $role->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                <?= esc(ucfirst($role->status)) ?>
                            </span>
                        </td>
                        <td class="data-table__actions">
                            <div class="table-actions">
                                <?php if ($canEditRoles): ?>
                                    <a class="shell-button shell-button--ghost" href="<?= site_url('roles/' . $role->id . '/edit') ?>">Edit</a>
                                    <form method="post" action="<?= site_url('roles/' . $role->id . '/status') ?>">
                                        <?= csrf_field() ?>
                                        <button class="shell-button shell-button--soft" type="submit">
                                            <?= $role->status === 'active' ? 'Deactivate' : 'Activate' ?>
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
</section>
<?= $this->endSection() ?>
