<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Create plan</h2>
            <p class="module-subtitle">Add a new billing plan to the catalog.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('platform/plans') ?>">Cancel</a>
    </div>

    <form method="post" action="<?= site_url('platform/plans') ?>">
        <?= csrf_field() ?>

        <div class="settings-grid">
            <div class="form-card">
                <h3 class="module-title module-title--small">Plan details</h3>

                <div class="form-grid">
                    <label class="field">
                        <span>Plan code <em>*</em></span>
                        <input type="text" name="code" value="<?= esc(old('code')) ?>" placeholder="e.g. professional" required>
                        <small>Lowercase letters, numbers, underscores only. Cannot be changed later.</small>
                    </label>

                    <label class="field">
                        <span>Plan name <em>*</em></span>
                        <input type="text" name="name" value="<?= esc(old('name')) ?>" placeholder="e.g. Professional" required>
                    </label>

                    <label class="field field--full">
                        <span>Description</span>
                        <input type="text" name="description" value="<?= esc(old('description')) ?>" placeholder="Short plan description shown to customers">
                    </label>

                    <label class="field">
                        <span>Monthly price (₹)</span>
                        <input type="number" name="monthly_price" min="0" step="0.01" value="<?= esc(old('monthly_price', '0')) ?>">
                    </label>

                    <label class="field">
                        <span>Yearly price (₹)</span>
                        <input type="number" name="yearly_price" min="0" step="0.01" value="<?= esc(old('yearly_price', '0')) ?>">
                    </label>

                    <label class="field">
                        <span>Max users</span>
                        <input type="number" name="max_users" min="0" value="<?= esc(old('max_users', '0')) ?>">
                        <small>0 = unlimited</small>
                    </label>

                    <label class="field">
                        <span>Max branches</span>
                        <input type="number" name="max_branches" min="0" value="<?= esc(old('max_branches', '0')) ?>">
                        <small>0 = unlimited</small>
                    </label>

                    <label class="field field--full" style="flex-direction:row;align-items:center;gap:.75rem;">
                        <input type="checkbox" name="is_public" value="1" <?= old('is_public') ? 'checked' : '' ?>>
                        <span>Show on public pricing page</span>
                    </label>
                </div>
            </div>

            <div class="form-card">
                <h3 class="module-title module-title--small">Included modules</h3>
                <p class="module-subtitle" style="margin-bottom:1rem;">Select which feature modules this plan unlocks.</p>

                <div class="form-grid">
                    <?php foreach ($allFeatures as $feature): ?>
                        <label class="field field--full" style="flex-direction:row;align-items:center;gap:.75rem;">
                            <input type="checkbox" name="modules[]" value="<?= esc($feature->code) ?>"
                                <?= in_array($feature->code, (array) old('modules', []), true) ? 'checked' : '' ?>>
                            <span>
                                <strong><?= esc($feature->name) ?></strong>
                                <small style="display:block;color:var(--muted);"><?= esc($feature->description) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="shell-button shell-button--primary">Create plan</button>
                    <a href="<?= site_url('platform/plans') ?>" class="shell-button shell-button--ghost">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
