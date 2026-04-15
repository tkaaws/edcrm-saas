<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($pageTitle ?? 'Team member') ?></h2>
            <p class="module-subtitle">Set up a person, assign their role, and choose where they work.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('users') ?>">Back to team</a>
    </div>

    <form class="form-card form-stack" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>

        <?php if (($canSubmit ?? true) === false): ?>
            <div class="shell-alert shell-alert--warning">
                No team roles are available to assign right now.
            </div>
        <?php endif; ?>

        <div class="form-summary-grid">
            <div class="form-summary-card">
                <strong>Identity</strong>
                <span>Capture the team member name, email, and sign-in password.</span>
            </div>
            <div class="form-summary-card">
                <strong>Access</strong>
                <span>Assign the correct role, reporting line, and working branches.</span>
            </div>
            <div class="form-summary-card">
                <strong>Optional details</strong>
                <span>Add staff and contact details only when you need them.</span>
            </div>
        </div>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Account details</h3>
                <p class="module-subtitle">Start with the person identity and login information.</p>
            </div>

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
                    <small>This email will be used for sign-in.</small>
                </label>

                <label class="field">
                    <span>Password <?= $user ? '(leave blank to keep current)' : '' ?></span>
                    <input type="password" name="password" minlength="8" <?= $user ? '' : 'required' ?>>
                </label>
            </div>
        </section>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Access and reporting</h3>
                <p class="module-subtitle">Choose what this person can do and how they fit into the team structure.</p>
            </div>

            <div class="form-grid">
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
                    <span>Reporting head</span>
                    <select name="manager_user_id">
                        <option value="">No reporting manager</option>
                        <?php foreach ($managerUsers as $manager): ?>
                            <?php $managerId = (string) $manager->id; ?>
                            <option value="<?= esc($managerId) ?>" <?= (string) old('manager_user_id', $hierarchy->manager_user_id ?? '') === $managerId ? 'selected' : '' ?>>
                                <?= esc(trim($manager->first_name . ' ' . ($manager->last_name ?? ''))) ?> (<?= esc($manager->email) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Use this only when the person reports to a team lead or manager.</small>
                </label>
            </div>

            <div class="choice-grid">
                <section class="choice-card">
                <h3>Assigned branches</h3>
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
                </section>
            </div>
        </section>

        <?php if ($user): ?>
            <section class="form-card form-card--nested">
                <div class="form-section-header">
                    <h3 class="module-title module-title--small">Additional details</h3>
                    <p class="module-subtitle">Optional staff and contact details.</p>
                </div>

                <div class="form-grid">
                    <label class="field">
                        <span>Staff ID</span>
                        <input type="text" name="employee_code" value="<?= esc(old('employee_code', $user->employee_code ?? '')) ?>">
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
                </div>
            </section>
        <?php endif; ?>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('users') ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit" <?= ($canSubmit ?? true) ? '' : 'disabled' ?>><?= esc($submitText) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
