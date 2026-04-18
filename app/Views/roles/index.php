<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateRoles = in_array('roles.create', $codes, true); ?>
    <?php $canEditRoles = in_array('roles.edit', $codes, true); ?>
    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Manage reusable roles and permission sets.</p>
        </div>
        <?php if ($canCreateRoles): ?>
            <a class="shell-button shell-button--primary" href="<?= site_url('roles/create') ?>">Create role</a>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
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
                            <td data-label="Role">
                                <div class="entity-cell">
                                    <strong><?= esc($role->name) ?></strong>
                                    <span><?= $role->is_system ? 'Core role' : 'Custom role' ?></span>
                                </div>
                            </td>
                            <td data-label="Type"><?= $role->is_system ? 'System' : 'Custom' ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?= $role->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= esc(ucfirst($role->status)) ?>
                                </span>
                            </td>
                            <td class="data-table__actions" data-label="Actions">
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

    <?= $this->include('shared/pagination') ?>
</section>
<?= $this->endSection() ?>
