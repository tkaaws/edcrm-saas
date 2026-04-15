<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($branchRecord->name) ?> Branch Settings</h2>
            <p class="module-subtitle">Manage branch-specific preferences and working rules.</p>
        </div>
        <div class="toolbar-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('branches/' . $branchRecord->id . '/edit') ?>">Edit branch</a>
            <a class="shell-button shell-button--ghost" href="<?= site_url('branches') ?>">Back to branches</a>
        </div>
    </div>

    <div class="settings-grid">
        <div class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Branch profile</h3>
                    <p class="module-subtitle">Basic branch details and default setup.</p>
                </div>
                <span class="status-badge <?= $branchRecord->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                    <?= esc(ucfirst($branchRecord->status)) ?>
                </span>
            </div>

            <dl class="context-list context-list--wide">
                <div><dt>Code</dt><dd><?= esc($branchRecord->code) ?></dd></div>
                <div><dt>Type</dt><dd><?= esc($branchRecord->type ?: 'General branch') ?></dd></div>
                <div><dt>City</dt><dd><?= esc($branchRecord->city ?: '-') ?></dd></div>
                <div><dt>Timezone</dt><dd><?= esc($branchRecord->timezone ?: 'Tenant default') ?></dd></div>
                <div><dt>Currency</dt><dd><?= esc($branchRecord->currency_code ?: 'Tenant default') ?></dd></div>
            </dl>
        </div>
    </div>

    <div class="settings-grid">
        <?php foreach ($sections as $section): ?>
            <form class="form-card" method="post" action="<?= site_url('branches/' . $branchRecord->id . '/settings/' . $section['category']) ?>">
                <?= csrf_field() ?>

                <div class="module-toolbar">
                    <div>
                        <h3 class="module-title module-title--small"><?= esc($section['title']) ?></h3>
                        <p class="module-subtitle"><?= esc($section['subtitle']) ?></p>
                    </div>
                </div>

                <div class="form-grid">
                    <?php foreach ($section['fields'] as $field): ?>
                        <?php
                        $definition = $field['definition'];
                        $formKey = $field['formKey'];
                        $value = old($formKey, $field['value']);
                        $options = $field['options'];
                        $optionLabels = $field['optionLabels'] ?? [];
                        $isLocked = $field['isLocked'];
                        $valueType = (string) $definition->value_type;
                        ?>
                        <label class="field<?= in_array($valueType, ['json', 'array', 'object'], true) ? ' field--full' : '' ?>">
                            <span><?= esc($definition->label) ?></span>

                            <?php if (in_array($valueType, ['bool', 'boolean'], true)): ?>
                                <input type="hidden" name="<?= esc($formKey) ?>" value="0">
                                <label class="checkbox-row">
                                    <input type="checkbox" name="<?= esc($formKey) ?>" value="1" <?= $value ? 'checked' : '' ?> <?= $isLocked ? 'disabled' : '' ?>>
                                    <span><?= esc($definition->description ?: 'Enabled') ?></span>
                                </label>
                            <?php elseif ($options !== []): ?>
                                <select name="<?= esc($formKey) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                                    <?php foreach ($options as $option): ?>
                                        <option value="<?= esc($option) ?>" <?= (string) $value === $option ? 'selected' : '' ?>>
                                            <?= esc($optionLabels[$option] ?? ucwords(str_replace(['_', '-'], ' ', $option))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif (in_array($valueType, ['int', 'integer'], true)): ?>
                                <input type="number" name="<?= esc($formKey) ?>" value="<?= esc((string) $value) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                            <?php elseif (in_array($valueType, ['json', 'array', 'object'], true)): ?>
                                <textarea name="<?= esc($formKey) ?>" rows="3" <?= $isLocked ? 'disabled' : '' ?>><?= esc(is_array($value) ? implode(', ', $value) : (string) $value) ?></textarea>
                            <?php else: ?>
                                <input type="text" name="<?= esc($formKey) ?>" value="<?= esc((string) $value) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                            <?php endif; ?>

                            <?php if ($definition->description): ?>
                                <small><?= esc($definition->description) ?></small>
                            <?php endif; ?>

                            <?php if ($isLocked): ?>
                                <small>This option is locked by your company or the EDCRM team.</small>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button class="shell-button shell-button--primary" type="submit">Save <?= esc($section['title']) ?></button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?= $this->endSection() ?>
