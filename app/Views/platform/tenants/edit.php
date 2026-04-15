<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <p class="module-subtitle"><?= esc($tenant->name) ?> - update company profile details.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants/' . $tenant->id) ?>">Cancel</a>
    </div>

    <?php $fieldErrors = session()->getFlashdata('fieldErrors') ?? []; ?>

    <form class="form-card form-stack" method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/update') ?>">
        <?= csrf_field() ?>

        <div class="form-summary-grid">
            <div class="form-summary-card">
                <strong>Profile</strong>
                <span>Update the company name, legal details, and regional defaults.</span>
            </div>
            <div class="form-summary-card">
                <strong>Owner contact</strong>
                <span>Keep the primary owner information accurate for support and account operations.</span>
            </div>
            <div class="form-summary-card">
                <strong>Save once</strong>
                <span>Review both sections and save all changes together.</span>
            </div>
        </div>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Company details</h3>
                <p class="module-subtitle">Update the main company record and workspace defaults.</p>
            </div>

            <div class="form-grid">
                <label class="field field--full">
                    <span>Company name <em>*</em></span>
                    <input type="text" name="name" value="<?= esc(old('name', $tenant->name)) ?>" required>
                    <?php if (isset($fieldErrors['name'])): ?>
                        <span class="field-error"><?= esc($fieldErrors['name']) ?></span>
                    <?php endif; ?>
                </label>

                <label class="field field--full">
                    <span>Legal name</span>
                    <input type="text" name="legal_name" value="<?= esc(old('legal_name', $tenant->legal_name)) ?>">
                </label>

                <label class="field">
                    <span>Timezone</span>
                    <input type="text" name="default_timezone" value="<?= esc(old('default_timezone', $tenant->default_timezone)) ?>" placeholder="Asia/Kolkata">
                </label>

                <label class="field">
                    <span>Currency code</span>
                    <input type="text" name="default_currency_code" maxlength="3" value="<?= esc(old('default_currency_code', $tenant->default_currency_code)) ?>" placeholder="INR">
                </label>

                <label class="field">
                    <span>Country code</span>
                    <input type="text" name="country_code" maxlength="2" value="<?= esc(old('country_code', $tenant->country_code)) ?>" placeholder="IN">
                </label>

                <label class="field">
                    <span>Locale</span>
                    <input type="text" name="locale_code" maxlength="10" value="<?= esc(old('locale_code', $tenant->locale_code)) ?>" placeholder="en">
                </label>
            </div>
        </section>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Owner contact</h3>
                <p class="module-subtitle">Keep the main company contact details up to date.</p>
            </div>

            <div class="form-grid">
                <label class="field field--full">
                    <span>Owner name</span>
                    <input type="text" name="owner_name" value="<?= esc(old('owner_name', $tenant->owner_name)) ?>">
                </label>

                <label class="field field--full">
                    <span>Owner email</span>
                    <input type="email" name="owner_email" value="<?= esc(old('owner_email', $tenant->owner_email)) ?>">
                    <?php if (isset($fieldErrors['owner_email'])): ?>
                        <span class="field-error"><?= esc($fieldErrors['owner_email']) ?></span>
                    <?php endif; ?>
                </label>

                <label class="field field--full">
                    <span>Owner phone</span>
                    <input type="text" name="owner_phone" value="<?= esc(old('owner_phone', $tenant->owner_phone)) ?>">
                </label>
            </div>

            <div class="form-actions">
                <a href="<?= site_url('platform/tenants/' . $tenant->id) ?>" class="shell-button shell-button--ghost">Cancel</a>
                <button type="submit" class="shell-button shell-button--primary">Save changes</button>
            </div>
        </section>
    </form>
</section>
<?= $this->endSection() ?>
