<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $fieldErrors = session('fieldErrors') ?? []; ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Create company</h2>
            <p class="module-subtitle">Create a new company with its first branch, owner login, default modules, and settings.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants') ?>">Back to companies</a>
    </div>

    <?php if (! empty($fieldErrors)): ?>
    <div class="alert alert--error">Please correct the errors below before submitting.</div>
    <?php endif; ?>

    <?php if (session('error')): ?>
    <div class="alert alert--error"><?= esc(session('error')) ?></div>
    <?php endif; ?>

    <form class="form-card" method="post" action="<?= site_url('platform/tenants') ?>">
        <?= csrf_field() ?>

        <section class="form-card form-card--nested">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Company profile</h3>
                    <p class="module-subtitle">Core company identity and workspace defaults.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field <?= isset($fieldErrors['name']) ? 'field--error' : '' ?>">
                    <span>Company name</span>
                    <input type="text" name="name" value="<?= esc(old('name')) ?>" required>
                    <?php if (isset($fieldErrors['name'])): ?><span class="field-error"><?= esc($fieldErrors['name']) ?></span><?php endif; ?>
                </label>
                <label class="field <?= isset($fieldErrors['slug']) ? 'field--error' : '' ?>">
                    <span>Company ID</span>
                    <input type="text" name="slug" value="<?= esc(old('slug')) ?>" placeholder="auto-generated if blank">
                    <?php if (isset($fieldErrors['slug'])): ?><span class="field-error"><?= esc($fieldErrors['slug']) ?></span><?php endif; ?>
                </label>
                <label class="field">
                    <span>Legal name</span>
                    <input type="text" name="legal_name" value="<?= esc(old('legal_name')) ?>">
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="draft" <?= old('status') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </label>
                <label class="field">
                    <span>Default timezone</span>
                    <input type="text" name="default_timezone" value="<?= esc(old('default_timezone', 'Asia/Kolkata')) ?>">
                </label>
                <label class="field">
                    <span>Default currency code</span>
                    <input type="text" name="default_currency_code" value="<?= esc(old('default_currency_code', 'INR')) ?>">
                </label>
                <label class="field">
                    <span>Country code</span>
                    <input type="text" name="country_code" value="<?= esc(old('country_code', 'IN')) ?>">
                </label>
                <label class="field">
                    <span>Locale code</span>
                    <input type="text" name="locale_code" value="<?= esc(old('locale_code', 'en')) ?>">
                </label>
                <label class="field">
                    <span>Branding name</span>
                    <input type="text" name="branding_name" value="<?= esc(old('branding_name')) ?>">
                </label>
                <label class="field">
                    <span>Branch visibility mode</span>
                    <select name="branch_visibility_mode">
                        <?php foreach (['own', 'restricted', 'all'] as $mode): ?>
                            <option value="<?= esc($mode) ?>" <?= old('branch_visibility_mode', 'own') === $mode ? 'selected' : '' ?>><?= esc(ucfirst($mode)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Enquiry visibility mode</span>
                    <select name="enquiry_visibility_mode">
                        <?php foreach (['own', 'restricted', 'all'] as $mode): ?>
                            <option value="<?= esc($mode) ?>" <?= old('enquiry_visibility_mode', 'own') === $mode ? 'selected' : '' ?>><?= esc(ucfirst($mode)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Admission visibility mode</span>
                    <select name="admission_visibility_mode">
                        <?php foreach (['own', 'restricted', 'all'] as $mode): ?>
                            <option value="<?= esc($mode) ?>" <?= old('admission_visibility_mode', 'own') === $mode ? 'selected' : '' ?>><?= esc(ucfirst($mode)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>

        <section class="form-card form-card--nested">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">First branch</h3>
                    <p class="module-subtitle">This branch is the first operational location for the new company.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field <?= isset($fieldErrors['branch_name']) ? 'field--error' : '' ?>">
                    <span>Branch name</span>
                    <input type="text" name="branch_name" value="<?= esc(old('branch_name', 'HQ')) ?>" required>
                    <?php if (isset($fieldErrors['branch_name'])): ?><span class="field-error"><?= esc($fieldErrors['branch_name']) ?></span><?php endif; ?>
                </label>
                <label class="field <?= isset($fieldErrors['branch_code']) ? 'field--error' : '' ?>">
                    <span>Branch code</span>
                    <input type="text" name="branch_code" value="<?= esc(old('branch_code', 'HQ')) ?>" required>
                    <?php if (isset($fieldErrors['branch_code'])): ?><span class="field-error"><?= esc($fieldErrors['branch_code']) ?></span><?php endif; ?>
                </label>
                <label class="field">
                    <span>Branch type</span>
                    <input type="text" name="branch_type" value="<?= esc(old('branch_type', 'main')) ?>">
                </label>
                <label class="field">
                    <span>City</span>
                    <input type="text" name="branch_city" value="<?= esc(old('branch_city')) ?>">
                </label>
                <label class="field">
                    <span>State code</span>
                    <input type="text" name="branch_state_code" value="<?= esc(old('branch_state_code')) ?>">
                </label>
                <label class="field">
                    <span>Branch timezone</span>
                    <input type="text" name="branch_timezone" value="<?= esc(old('branch_timezone')) ?>" placeholder="leave blank to inherit company default">
                </label>
                <label class="field">
                    <span>Branch currency code</span>
                    <input type="text" name="branch_currency_code" value="<?= esc(old('branch_currency_code')) ?>" placeholder="leave blank to inherit company default">
                </label>
                <label class="field">
                    <span>Postal code</span>
                    <input type="text" name="branch_postal_code" value="<?= esc(old('branch_postal_code')) ?>">
                </label>
                <label class="field field--full">
                    <span>Address line 1</span>
                    <input type="text" name="branch_address_line_1" value="<?= esc(old('branch_address_line_1')) ?>">
                </label>
                <label class="field field--full">
                    <span>Address line 2</span>
                    <input type="text" name="branch_address_line_2" value="<?= esc(old('branch_address_line_2')) ?>">
                </label>
            </div>
        </section>

        <section class="form-card form-card--nested">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Owner user</h3>
                    <p class="module-subtitle">This person becomes the first company admin login.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field <?= isset($fieldErrors['owner_name']) ? 'field--error' : '' ?>">
                    <span>Owner name</span>
                    <input type="text" name="owner_name" value="<?= esc(old('owner_name')) ?>" required>
                    <?php if (isset($fieldErrors['owner_name'])): ?><span class="field-error"><?= esc($fieldErrors['owner_name']) ?></span><?php endif; ?>
                </label>
                <label class="field <?= isset($fieldErrors['owner_email']) ? 'field--error' : '' ?>">
                    <span>Owner email</span>
                    <input type="email" name="owner_email" value="<?= esc(old('owner_email')) ?>" required>
                    <small>This email is used for the owner login.</small>
                    <?php if (isset($fieldErrors['owner_email'])): ?><span class="field-error"><?= esc($fieldErrors['owner_email']) ?></span><?php endif; ?>
                </label>
                <label class="field">
                    <span>Owner phone</span>
                    <input type="text" name="owner_phone" value="<?= esc(old('owner_phone')) ?>">
                </label>
                <label class="field">
                    <span>Owner employee code</span>
                    <input type="text" name="owner_employee_code" value="<?= esc(old('owner_employee_code', 'EMP-001')) ?>">
                </label>
                <label class="field <?= isset($fieldErrors['owner_password']) ? 'field--error' : '' ?>">
                    <span>Owner password</span>
                    <input type="password" name="owner_password" minlength="8" required>
                    <?php if (isset($fieldErrors['owner_password'])): ?><span class="field-error"><?= esc($fieldErrors['owner_password']) ?></span><?php endif; ?>
                </label>
                <label class="field <?= isset($fieldErrors['owner_password_confirm']) ? 'field--error' : '' ?>">
                    <span>Confirm password</span>
                    <input type="password" name="owner_password_confirm" minlength="8" required>
                    <?php if (isset($fieldErrors['owner_password_confirm'])): ?><span class="field-error"><?= esc($fieldErrors['owner_password_confirm']) ?></span><?php endif; ?>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants') ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit">Create company</button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
