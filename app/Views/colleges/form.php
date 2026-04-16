<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <p class="module-subtitle">Add or update the college list used while creating enquiries.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('colleges') ?>">Back to colleges</a>
    </div>

    <form class="form-card form-stack" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">College details</h3>
                <p class="module-subtitle">Every college should carry its city and state so reporting stays clean later.</p>
            </div>

            <div class="form-grid">
                <label class="field field--full">
                    <span>College name</span>
                    <input type="text" name="name" value="<?= esc(old('name', $college->name ?? '')) ?>" required>
                </label>

                <label class="field">
                    <span>City</span>
                    <input type="text" name="city_name" value="<?= esc(old('city_name', $college->city_name ?? '')) ?>" required>
                </label>

                <label class="field">
                    <span>State</span>
                    <input type="text" name="state_name" value="<?= esc(old('state_name', $college->state_name ?? '')) ?>" required>
                </label>

                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="active" <?= old('status', $college->status ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= old('status', $college->status ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('colleges') ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit"><?= esc($submitText) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
