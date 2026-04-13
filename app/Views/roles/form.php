<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($pageTitle ?? 'Role form') ?></h2>
            <p class="module-subtitle">Create and maintain role templates with module-level privilege assignment.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('roles') ?>">Back to roles</a>
    </div>

    <form class="form-card" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>

        <div class="form-grid">
            <label class="field">
                <span>Role name</span>
                <input type="text" name="name" value="<?= esc(old('name', $role->name ?? '')) ?>" required>
            </label>

            <label class="field">
                <span>Role code</span>
                <input type="text" name="code" value="<?= esc(old('code', $role->code ?? '')) ?>" <?= (! empty($role) && $role->is_system) ? 'readonly' : 'required' ?>>
            </label>

            <label class="field">
                <span>Status</span>
                <select name="status">
                    <option value="active" <?= old('status', $role->status ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= old('status', $role->status ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </label>

            <label class="field">
                <span>Role type</span>
                <input type="text" value="<?= (! empty($role) && $role->is_system) ? 'System role' : 'Custom role' ?>" readonly>
            </label>
        </div>

        <section class="form-card form-card--nested">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Privileges</h3>
                    <p class="module-subtitle">Select what this role can do across the CRM platform.</p>
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
                                        <small><?= esc($privilege->code) ?></small>
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
            <button class="shell-button shell-button--primary" type="submit"><?= esc($submitText) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
