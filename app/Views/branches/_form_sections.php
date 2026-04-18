<?php
$formBranch = $formBranch ?? ($branch ?? null);
$regionalInputOptions = $regionalInputOptions ?? [];
$timezoneOptions = $regionalInputOptions['timezones'] ?? [];
$currencyOptions = $regionalInputOptions['currencies'] ?? [];
$useOldInput = (bool) ($useOldInput ?? true);
$fieldValue = static function (string $key, mixed $default = '') use ($useOldInput) {
    return $useOldInput ? old($key, $default) : $default;
};
?>
<section class="form-card form-card--nested">
    <div class="form-section-header">
        <h3 class="module-title module-title--small">Branch identity</h3>
        <p class="module-subtitle">Keep the visible branch details short and clear.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Branch name</span>
            <input type="text" name="name" value="<?= esc($fieldValue('name', $formBranch->name ?? '')) ?>" required>
        </label>

        <label class="field">
            <span>Branch short code</span>
            <input type="text" name="code" value="<?= esc($fieldValue('code', $formBranch->code ?? '')) ?>" required>
        </label>

        <label class="field">
            <span>City</span>
            <input type="text" name="city" value="<?= esc($fieldValue('city', $formBranch->city ?? '')) ?>">
        </label>

        <label class="field">
            <span>Branch type</span>
            <input type="text" name="type" value="<?= esc($fieldValue('type', $formBranch->type ?? '')) ?>" placeholder="Main, satellite, online">
        </label>

        <label class="field">
            <span>Status</span>
            <select name="status">
                <option value="active" <?= $fieldValue('status', $formBranch->status ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $fieldValue('status', $formBranch->status ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </label>

    </div>
</section>

<section class="form-card form-card--nested">
    <div class="form-section-header">
        <h3 class="module-title module-title--small">Operational defaults</h3>
        <p class="module-subtitle">Only override the company defaults where this branch genuinely differs.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>State / region</span>
            <input type="text" name="state_code" value="<?= esc($fieldValue('state_code', $formBranch->state_code ?? '')) ?>">
        </label>

        <label class="field">
            <span>Timezone</span>
            <select name="timezone">
                <option value="">Use company default</option>
                <?php $selectedTimezone = $fieldValue('timezone', $formBranch->timezone ?? ''); ?>
                <?php foreach ($timezoneOptions as $timezone): ?>
                    <option value="<?= esc($timezone) ?>" <?= $selectedTimezone === $timezone ? 'selected' : '' ?>>
                        <?= esc($timezone) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Leave blank to use the company default.</small>
        </label>

        <label class="field">
            <span>Currency</span>
            <select name="currency_code">
                <option value="">Use company default</option>
                <?php $selectedCurrency = $fieldValue('currency_code', $formBranch->currency_code ?? ''); ?>
                <?php foreach ($currencyOptions as $code => $label): ?>
                    <option value="<?= esc($code) ?>" <?= $selectedCurrency === $code ? 'selected' : '' ?>>
                        <?= esc($code . ' - ' . $label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Leave blank to use the company default.</small>
        </label>

    </div>
</section>
