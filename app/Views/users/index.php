<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateUsers = in_array('users.create', $codes, true); ?>
    <?php $canEditUsers = in_array('users.edit', $codes, true); ?>
    <?php $canImpersonateUsers = in_array('users.impersonate', $codes, true) || (($roleCode ?? '') === 'platform_admin'); ?>
    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Manage people, access, and branch ownership.</p>
        </div>
        <?php if ($canCreateUsers): ?>
            <a class="shell-button shell-button--primary" href="<?= site_url('users/create') ?>">Add team member</a>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>Team member</th>
                        <th>Work role</th>
                        <th>Home branch</th>
                        <th>Status</th>
                        <th>Last login</th>
                        <th class="data-table__actions">Quick actions</th>
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
                            <td data-label="Team member">
                                <div class="entity-cell">
                                    <strong><?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->email) ?></strong>
                                    <span><?= esc($user->email) ?></span>
                                </div>
                            </td>
                            <td data-label="Work role"><?= esc($user->role_name ?? 'Unassigned') ?></td>
                            <td data-label="Home branch"><?= esc($user->primary_branch_name ?? 'Not assigned') ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?= $user->is_active ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= $user->is_active ? 'Active' : 'Disabled' ?>
                                </span>
                            </td>
                            <td data-label="Last login"><?= esc($user->last_login_at ?? 'Never') ?></td>
                            <td class="data-table__actions" data-label="Actions">
                                <div class="table-actions">
                                    <?php if ($canEditUsers && ! empty($user->can_manage_target)): ?>
                                        <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('users/' . $user->id . '/edit') ?>">Edit</a>
                                        <form method="post" action="<?= site_url('users/' . $user->id . '/status') ?>">
                                            <?= csrf_field() ?>
                                            <button class="shell-button shell-button--soft shell-button--sm" type="submit">
                                                <?= $user->is_active ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($canImpersonateUsers && ! empty($user->can_manage_target) && $user->is_active && (int) $user->id !== (int) session()->get('user_id') && (int) ($user->allow_impersonation ?? 1) === 1): ?>
                                        <form method="post" action="<?= site_url('impersonation/start/' . $user->id) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="reason" value="Company support access">
                                            <button class="shell-button shell-button--soft shell-button--sm" type="submit">Open as user</button>
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
