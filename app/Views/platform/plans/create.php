<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Add subscription plan</h2>
            <p class="module-subtitle">Create a plan for new companies and assign included modules.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('platform/plans') ?>">Cancel</a>
    </div>

    <form class="form-card form-stack" method="post" action="<?= site_url('platform/plans') ?>">
        <?= csrf_field() ?>

        <div class="form-summary-grid">
            <div class="form-summary-card">
                <strong>Pricing</strong>
                <span>Define the plan identity and customer-facing prices.</span>
            </div>
            <div class="form-summary-card">
                <strong>Capacity</strong>
                <span>Set user and branch limits that match the commercial offer.</span>
            </div>
            <div class="form-summary-card">
                <strong>Modules</strong>
                <span>Choose which product areas become available on this plan.</span>
            </div>
        </div>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Plan details</h3>
                <p class="module-subtitle">Set up the plan identity, public naming, and price points.</p>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Plan code <em>*</em></span>
                    <input type="text" name="code" value="<?= esc(old('code')) ?>" placeholder="e.g. professional" required>
                    <small>Lowercase letters, numbers, and underscores only. This is the internal reference code.</small>
                </label>

                <label class="field">
                    <span>Plan name <em>*</em></span>
                    <input type="text" name="name" value="<?= esc(old('name')) ?>" placeholder="e.g. Professional" required>
                </label>

                <label class="field field--full">
                    <span>Description</span>
                    <input type="text" name="description" value="<?= esc(old('description')) ?>" placeholder="What customers see in the plan summary">
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
                    <small>0 means unlimited team members.</small>
                </label>

                <label class="field">
                    <span>Max branches</span>
                    <input type="number" name="max_branches" min="0" value="<?= esc(old('max_branches', '0')) ?>">
                    <small>0 means unlimited work locations.</small>
                </label>

                <label class="field field--full">
                    <span>Public pricing visibility</span>
                    <div class="checkbox-field">
                        <input type="checkbox" name="is_public" value="1" <?= old('is_public') ? 'checked' : '' ?>>
                        <div class="checkbox-field__copy">
                            <strong>Show this plan in public pricing</strong>
                            <small>Turn this on only when the plan should appear in public plan listings.</small>
                        </div>
                    </div>
                </label>
            </div>
        </section>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Included modules</h3>
                <p class="module-subtitle">Select which feature groups this plan unlocks.</p>
            </div>

            <div class="form-grid">
                <?php foreach ($allFeatures as $feature): ?>
                    <label class="field field--full">
                        <div class="checkbox-field">
                            <input type="checkbox" name="modules[]" value="<?= esc($feature->code) ?>"
                                <?= in_array($feature->code, (array) old('modules', []), true) ? 'checked' : '' ?>>
                            <div class="checkbox-field__copy">
                                <strong><?= esc($feature->name) ?></strong>
                                <small><?= esc($feature->description) ?></small>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="form-actions">
                <a href="<?= site_url('platform/plans') ?>" class="shell-button shell-button--ghost">Cancel</a>
                <button type="submit" class="shell-button shell-button--primary">Create plan</button>
            </div>
        </section>
    </form>
</section>
<?= $this->endSection() ?>
