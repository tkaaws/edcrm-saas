<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <p class="module-subtitle">Choose what this role allows each team member to see and do.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('roles') ?>">Back to roles</a>
    </div>

    <form class="form-card form-stack" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>

        <?php if ($privilegeGroups === []): ?>
            <div class="shell-alert shell-alert--warning">
                No available permissions can be assigned from your current account.
            </div>
        <?php endif; ?>

        <div class="form-summary-grid">
            <div class="form-summary-card">
                <strong>Name the role</strong>
                <span>Use a clear business name that managers will understand immediately.</span>
            </div>
            <div class="form-summary-card">
                <strong>Choose access</strong>
                <span>Pick only the permissions this role should need for daily work.</span>
            </div>
            <div class="form-summary-card">
                <strong>Keep it reusable</strong>
                <span>Design roles so they can be assigned to multiple people without one-off changes.</span>
            </div>
        </div>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Role details</h3>
                <p class="module-subtitle">Start with the role name and basic role type information.</p>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Role name</span>
                    <input type="text" name="name" value="<?= esc(old('name', $role->name ?? '')) ?>" required>
                </label>

                <?php if (! empty($role)): ?>
                    <label class="field">
                        <span>Role type</span>
                        <input type="text" value="<?= (! empty($role) && $role->is_system) ? 'Core role' : 'Custom role' ?>" readonly>
                    </label>
                <?php endif; ?>
            </div>
        </section>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">What this role can do</h3>
                <p class="module-subtitle">Pick the actions this team role is allowed to use.</p>
            </div>

            <div class="privilege-groups">
                <?php $selectedPrivilegeIds = array_map('intval', old('privilege_ids', $selectedPrivilegeIds ?? [])); ?>
                <?php foreach ($privilegeGroups as $module => $privileges): ?>
                    <section class="choice-card">
                        <h3><?= esc(ucwords(str_replace('_', ' ', $module))) ?></h3>
                        <div class="choice-list">
                            <?php foreach ($privileges as $privilege): ?>
                                <label class="checkbox-row checkbox-row--stacked">
                                    <input type="checkbox" name="privilege_ids[]" value="<?= esc((string) $privilege->id) ?>" <?= in_array((int) $privilege->id, $selectedPrivilegeIds, true) ? 'checked' : '' ?>>
                                    <span>
                                        <strong><?= esc($privilege->name) ?></strong>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('roles') ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit" <?= $privilegeGroups === [] ? 'disabled' : '' ?>><?= esc($submitText) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
