<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($pageTitle ?? 'User form') ?></h2>
            <p class="module-subtitle">Create and maintain tenant users with role, branch, and password control.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('users') ?>">Back to users</a>
    </div>

    <form class="form-card" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>

        <?php if (($canSubmit ?? true) === false): ?>
            <div class="alert alert--warning">
                No assignable roles are available for your current access scope and tenant plan.
            </div>
        <?php endif; ?>

        <div class="form-grid">
            <label class="field">
                <span>First name</span>
                <input type="text" name="first_name" value="<?= esc(old('first_name', $user->first_name ?? '')) ?>" required>
            </label>

            <label class="field">
                <span>Last name</span>
                <input type="text" name="last_name" value="<?= esc(old('last_name', $user->last_name ?? '')) ?>">
            </label>

            <label class="field">
                <span>Email</span>
                <input type="email" name="email" value="<?= esc(old('email', $user->email ?? '')) ?>" required>
            </label>

            <label class="field">
                <span>Username</span>
                <input type="text" name="username" value="<?= esc(old('username', $user->username ?? '')) ?>" required>
            </label>

            <label class="field">
                <span>Employee code</span>
                <input type="text" name="employee_code" value="<?= esc(old('employee_code', $user->employee_code ?? '')) ?>">
            </label>

            <label class="field">
                <span>Role</span>
                <select name="role_id" required>
                    <option value="">Select role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= esc((string) $role->id) ?>" <?= (string) old('role_id', $user->role_id ?? '') === (string) $role->id ? 'selected' : '' ?>>
                            <?= esc($role->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span>Department</span>
                <input type="text" name="department" value="<?= esc(old('department', $user->department ?? '')) ?>">
            </label>

            <label class="field">
                <span>Designation</span>
                <input type="text" name="designation" value="<?= esc(old('designation', $user->designation ?? '')) ?>">
            </label>

            <label class="field">
                <span>Mobile number</span>
                <input type="text" name="mobile_number" value="<?= esc(old('mobile_number', $user->mobile_number ?? '')) ?>">
            </label>

            <label class="field">
                <span>WhatsApp number</span>
                <input type="text" name="whatsapp_number" value="<?= esc(old('whatsapp_number', $user->whatsapp_number ?? '')) ?>">
            </label>

            <label class="field field--full">
                <span>Password <?= $user ? '(leave blank to keep existing)' : '' ?></span>
                <input type="password" name="password" minlength="8" <?= $user ? '' : 'required' ?>>
            </label>
        </div>

        <div class="choice-grid">
            <section class="choice-card">
                <h3>Branch access</h3>
                <div class="choice-list">
                    <?php foreach ($branches as $branch): ?>
                        <?php $checked = in_array((int) $branch->id, old('branch_ids', $userBranchIds ?? []), true); ?>
                        <label class="checkbox-row">
                            <input type="checkbox" name="branch_ids[]" value="<?= esc((string) $branch->id) ?>" <?= $checked ? 'checked' : '' ?>>
                            <span><?= esc($branch->name) ?> <small><?= esc($branch->code) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="choice-card">
                <h3>Primary branch</h3>
                <label class="field">
                    <span>Primary branch</span>
                    <select name="primary_branch_id" required>
                        <option value="">Select primary branch</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= esc((string) $branch->id) ?>" <?= (string) old('primary_branch_id', $primaryBranchId ?? '') === (string) $branch->id ? 'selected' : '' ?>>
                                <?= esc($branch->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="checkbox-row">
                    <input type="checkbox" name="is_active" value="1" <?= old('is_active', $user->is_active ?? 1) ? 'checked' : '' ?>>
                    <span>Active account</span>
                </label>

                <label class="checkbox-row">
                    <input type="checkbox" name="must_reset_password" value="1" <?= old('must_reset_password', $user->must_reset_password ?? 0) ? 'checked' : '' ?>>
                    <span>Force password reset on next login</span>
                </label>
            </section>
        </div>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('users') ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit" <?= ($canSubmit ?? true) ? '' : 'disabled' ?>><?= esc($submitText) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
