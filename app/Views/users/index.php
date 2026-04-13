<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Tenant users</h2>
            <p class="module-subtitle">Manage branch access, role assignment, and account status for each tenant user.</p>
        </div>
        <a class="shell-button shell-button--primary" href="<?= site_url('users/create') ?>">Create user</a>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Primary branch</th>
                    <th>Status</th>
                    <th>Last login</th>
                    <th class="data-table__actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users === []): ?>
                    <tr>
                        <td colspan="6" class="empty-state">No users yet. Create the first user for this tenant.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="entity-cell">
                                <strong><?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->username) ?></strong>
                                <span><?= esc($user->email) ?></span>
                            </div>
                        </td>
                        <td><?= esc($user->role_name ?? 'Unassigned') ?></td>
                        <td><?= esc($user->primary_branch_name ?? 'Not assigned') ?></td>
                        <td>
                            <span class="status-badge <?= $user->is_active ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                <?= $user->is_active ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td><?= esc($user->last_login_at ?? 'Never') ?></td>
                        <td class="data-table__actions">
                            <div class="table-actions">
                                <a class="shell-button shell-button--ghost" href="<?= site_url('users/' . $user->id . '/edit') ?>">Edit</a>
                                <form method="post" action="<?= site_url('users/' . $user->id . '/status') ?>">
                                    <?= csrf_field() ?>
                                    <button class="shell-button shell-button--soft" type="submit">
                                        <?= $user->is_active ? 'Deactivate' : 'Activate' ?>
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
