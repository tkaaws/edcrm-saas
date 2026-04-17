<?php
$branch = $branch ?? null;
$regionalInputOptions = $regionalInputOptions ?? [];
$countryOptions = $regionalInputOptions['countries'] ?? [];
$timezoneOptions = $regionalInputOptions['timezones'] ?? [];
$currencyOptions = $regionalInputOptions['currencies'] ?? [];
?>
<div class="form-summary-grid">
    <div class="form-summary-card">
        <strong>Step 1</strong>
        <span>Enter the branch identity that people will see across the workspace.</span>
    </div>
    <div class="form-summary-card">
        <strong>Step 2</strong>
        <span>Add location and operational defaults if this branch needs its own setup.</span>
    </div>
    <div class="form-summary-card">
        <strong>Step 3</strong>
        <span>Save now and refine branch-specific rules later from branch settings.</span>
    </div>
</div>

<section class="form-card form-card--nested">
    <div class="form-section-header">
        <h3 class="module-title module-title--small">Branch identity</h3>
        <p class="module-subtitle">Start with the branch name, code, and main location details.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Branch name</span>
            <input type="text" name="name" value="<?= esc(old('name', $branch->name ?? '')) ?>" required>
        </label>

        <label class="field">
            <span>Branch short code</span>
            <input type="text" name="code" value="<?= esc(old('code', $branch->code ?? '')) ?>" required>
        </label>

        <label class="field">
            <span>City</span>
            <input type="text" name="city" value="<?= esc(old('city', $branch->city ?? '')) ?>">
        </label>

        <label class="field">
            <span>Branch type</span>
            <input type="text" name="type" value="<?= esc(old('type', $branch->type ?? '')) ?>" placeholder="Main, satellite, online">
        </label>

        <label class="field field--full">
            <span>Address line 1</span>
            <input type="text" name="address_line_1" value="<?= esc(old('address_line_1', $branch->address_line_1 ?? '')) ?>">
        </label>
    </div>
</section>

<section class="form-card form-card--nested">
    <div class="form-section-header">
        <h3 class="module-title module-title--small">Operational defaults</h3>
        <p class="module-subtitle">Use branch-specific values only when they should differ from company defaults.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Country</span>
            <select name="country_code">
                <?php $selectedCountry = old('country_code', $branch->country_code ?? ''); ?>
                <?php foreach ($countryOptions as $code => $label): ?>
                    <option value="<?= esc($code) ?>" <?= $selectedCountry === $code ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="field">
            <span>State / region</span>
            <input type="text" name="state_code" value="<?= esc(old('state_code', $branch->state_code ?? '')) ?>">
        </label>

        <label class="field">
            <span>Timezone</span>
            <select name="timezone">
                <option value="">Use company default</option>
                <?php $selectedTimezone = old('timezone', $branch->timezone ?? ''); ?>
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
                <?php $selectedCurrency = old('currency_code', $branch->currency_code ?? ''); ?>
                <?php foreach ($currencyOptions as $code => $label): ?>
                    <option value="<?= esc($code) ?>" <?= $selectedCurrency === $code ? 'selected' : '' ?>>
                        <?= esc($code . ' - ' . $label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Leave blank to use the company default.</small>
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

    <?php if (empty($branch)): ?>
        <p class="form-note">Country, currency, timezone, and status will use company defaults for now. You can refine them later from branch settings.</p>
    <?php endif; ?>
</section>
