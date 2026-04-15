<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Edit company</h2>
            <p class="module-subtitle"><?= esc($tenant->name) ?> — update company profile details.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants/' . $tenant->id) ?>">Cancel</a>
    </div>

    <?php $fieldErrors = session()->getFlashdata('fieldErrors') ?? []; ?>

    <form method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/update') ?>">
        <?= csrf_field() ?>

        <div class="settings-grid">
            <div class="form-card">
                <h3 class="module-title module-title--small">Company details</h3>

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
            </div>

            <div class="form-card">
                <h3 class="module-title module-title--small">Owner contact</h3>

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
                    <button type="submit" class="shell-button shell-button--primary">Save changes</button>
                    <a href="<?= site_url('platform/tenants/' . $tenant->id) ?>" class="shell-button shell-button--ghost">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
