<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($pageTitle ?? 'Branch form') ?></h2>
            <p class="module-subtitle">Create and maintain institute branches with regional and operational defaults.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('branches') ?>">Back to branches</a>
    </div>

    <form class="form-card" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>

        <div class="form-grid">
            <label class="field">
                <span>Branch name</span>
                <input type="text" name="name" value="<?= esc(old('name', $branch->name ?? '')) ?>" required>
            </label>

            <label class="field">
                <span>Branch code</span>
                <input type="text" name="code" value="<?= esc(old('code', $branch->code ?? '')) ?>" required>
            </label>

            <label class="field">
                <span>City</span>
                <input type="text" name="city" value="<?= esc(old('city', $branch->city ?? '')) ?>">
            </label>

            <label class="field field--full">
                <span>Address line 1</span>
                <input type="text" name="address_line_1" value="<?= esc(old('address_line_1', $branch->address_line_1 ?? '')) ?>">
            </label>
        </div>

        <?php if (! empty($branch)): ?>
            <section class="form-card form-card--nested">
                <div class="module-toolbar">
                    <div>
                        <h3 class="module-title module-title--small">Additional details</h3>
                        <p class="module-subtitle">Regional overrides and extended branch profile settings.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <label class="field">
                        <span>Type</span>
                        <input type="text" name="type" value="<?= esc(old('type', $branch->type ?? '')) ?>" placeholder="main, satellite, online">
                    </label>

                    <label class="field">
                        <span>Country code</span>
                        <input type="text" name="country_code" value="<?= esc(old('country_code', $branch->country_code ?? '')) ?>" placeholder="IN">
                    </label>

                    <label class="field">
                        <span>State code</span>
                        <input type="text" name="state_code" value="<?= esc(old('state_code', $branch->state_code ?? '')) ?>">
                    </label>

                    <label class="field">
                        <span>Timezone</span>
                        <input type="text" name="timezone" value="<?= esc(old('timezone', $branch->timezone ?? '')) ?>" placeholder="Asia/Kolkata">
                    </label>

                    <label class="field">
                        <span>Currency code</span>
                        <input type="text" name="currency_code" value="<?= esc(old('currency_code', $branch->currency_code ?? '')) ?>" placeholder="INR">
                    </label>

                    <label class="field field--full">
                        <span>Address line 2</span>
                        <input type="text" name="address_line_2" value="<?= esc(old('address_line_2', $branch->address_line_2 ?? '')) ?>">
                    </label>

                    <label class="field">
                        <span>Postal code</span>
                        <input type="text" name="postal_code" value="<?= esc(old('postal_code', $branch->postal_code ?? '')) ?>">
                    </label>

                    <label class="field">
                        <span>Status</span>
                        <select name="status">
                            <option value="active" <?= old('status', $branch->status ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= old('status', $branch->status ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </label>
                </div>
            </section>
        <?php else: ?>
            <p class="module-subtitle" style="margin-top:1rem;">
                Country, currency, timezone, and branch status will inherit tenant defaults for now. You can refine them later from branch settings.
            </p>
        <?php endif; ?>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('branches') ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit"><?= esc($submitText) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
