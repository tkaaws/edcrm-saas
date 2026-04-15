<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateUsers = in_array('users.create', $codes, true); ?>
    <?php $canEditUsers = in_array('users.edit', $codes, true); ?>
    <?php $canImpersonateUsers = in_array('users.impersonate', $codes, true) || (($roleCode ?? '') === 'platform_admin'); ?>
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Team members</h2>
            <p class="module-subtitle">Add people, assign access, and manage who can work in each branch.</p>
        </div>
        <?php if ($canCreateUsers): ?>
            <a class="shell-button shell-button--primary" href="<?= site_url('users/create') ?>">Add team member</a>
        <?php endif; ?>
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
                        <td colspan="6" class="empty-state">No team members yet. Add the first person for this company.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="entity-cell">
                                <strong><?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->email) ?></strong>
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
                                <?php if ($canEditUsers && ! empty($user->can_manage_target)): ?>
                                    <a class="shell-button shell-button--ghost" href="<?= site_url('users/' . $user->id . '/edit') ?>">Edit</a>
                                    <form method="post" action="<?= site_url('users/' . $user->id . '/status') ?>">
                                        <?= csrf_field() ?>
                                        <button class="shell-button shell-button--soft" type="submit">
                                            <?= $user->is_active ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canImpersonateUsers && ! empty($user->can_manage_target) && $user->is_active && (int) $user->id !== (int) session()->get('user_id') && (int) ($user->allow_impersonation ?? 1) === 1): ?>
                                    <form method="post" action="<?= site_url('impersonation/start/' . $user->id) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="reason" value="Company support access">
                                        <button class="shell-button shell-button--soft" type="submit">Open as this user</button>
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
