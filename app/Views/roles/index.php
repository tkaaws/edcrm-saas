<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateRoles = in_array('roles.create', $codes, true); ?>
    <?php $canEditRoles = in_array('roles.edit', $codes, true); ?>
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Team access rules</h2>
            <p class="module-subtitle">Create reusable team roles for different kinds of team members.</p>
        </div>
        <?php if ($canCreateRoles): ?>
            <a class="shell-button shell-button--primary" href="<?= site_url('roles/create') ?>">Create role</a>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Role name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="data-table__actions">Quick actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($roles === []): ?>
                        <tr>
                            <td colspan="4" class="empty-state">No roles yet. Create one role to control what team members can do.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td>
                                <div class="entity-cell">
                                    <strong><?= esc($role->name) ?></strong>
                                    <span><?= $role->is_system ? 'Core role' : 'Custom role' ?></span>
                                </div>
                            </td>
                            <td><?= $role->is_system ? 'System' : 'Custom' ?></td>
                            <td>
                                <span class="status-badge <?= $role->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= esc(ucfirst($role->status)) ?>
                                </span>
                            </td>
                            <td class="data-table__actions">
                                <div class="table-actions">
                                    <?php if ($canEditRoles): ?>
                                        <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('roles/' . $role->id . '/edit') ?>">Edit</a>
                                        <form method="post" action="<?= site_url('roles/' . $role->id . '/status') ?>">
                                            <?= csrf_field() ?>
                                            <button class="shell-button shell-button--soft shell-button--sm" type="submit">
                                                <?= $role->status === 'active' ? 'Disable' : 'Enable' ?>
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
