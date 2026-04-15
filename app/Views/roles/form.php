<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($pageTitle ?? 'Access profile') ?></h2>
            <p class="module-subtitle">Choose what this kind of team member can see and do.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('roles') ?>">Back to access profiles</a>
    </div>

    <form class="form-card" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>

        <?php if ($privilegeGroups === []): ?>
            <div class="alert alert--warning">
                No available permissions can be assigned from your current account.
            </div>
        <?php endif; ?>

        <div class="form-grid">
            <label class="field">
                <span>Access profile name</span>
                <input type="text" name="name" value="<?= esc(old('name', $role->name ?? '')) ?>" required>
            </label>

            <?php if (! empty($role)): ?>
                <label class="field">
                    <span>Internal code</span>
                    <input type="text" value="<?= esc($role->code ?? '') ?>" readonly>
                </label>

                <label class="field">
                    <span>Profile type</span>
                    <input type="text" value="<?= (! empty($role) && $role->is_system) ? 'Standard profile' : 'Custom profile' ?>" readonly>
                </label>
            <?php endif; ?>
        </div>

        <section class="form-card form-card--nested">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Privileges</h3>
                    <p class="module-subtitle">Select what this access profile can do across the workspace.</p>
                </div>
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
