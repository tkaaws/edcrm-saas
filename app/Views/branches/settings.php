<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php
    $toDisplayValue = static function (array $field): string {
        $definition = $field['definition'];
        $value = $field['value'];
        $options = $field['options'] ?? [];
        $optionLabels = $field['optionLabels'] ?? [];
        $valueType = (string) $definition->value_type;

        if (in_array($valueType, ['bool', 'boolean'], true)) {
            return $value ? 'Enabled' : 'Disabled';
        }

        if ($options !== []) {
            $key = (string) $value;
            return (string) ($optionLabels[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key)));
        }

        if (is_array($value)) {
            return $value === [] ? 'Not set' : implode(', ', $value);
        }

        $text = trim((string) $value);

        return $text !== '' ? $text : 'Not set';
    };
    ?>
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
                <div><dt>Timezone</dt><dd><?= esc($branchRecord->timezone ?: 'Company default') ?></dd></div>
                <div><dt>Currency</dt><dd><?= esc($branchRecord->currency_code ?: 'Company default') ?></dd></div>
            </dl>
        </div>
    </div>

    <div class="summary-grid">
        <?php foreach ($sections as $section): ?>
            <?php $modalId = 'branch-settings-modal-' . preg_replace('/[^a-z0-9_-]+/i', '-', (string) $section['category']); ?>
            <section class="form-card summary-card">
                <div class="module-toolbar">
                    <div>
                        <h3 class="module-title module-title--small"><?= esc($section['title']) ?></h3>
                        <p class="module-subtitle"><?= esc($section['subtitle']) ?></p>
                    </div>
                </div>

                <div class="summary-card__meta">
                    <?php
                    $lockedCount = 0;
                    foreach ($section['fields'] as $field) {
                        if (! empty($field['isLocked'])) {
                            $lockedCount++;
                        }
                    }
                    ?>
                    <span class="summary-pill"><?= esc((string) count($section['fields'])) ?> rule<?= count($section['fields']) === 1 ? '' : 's' ?></span>
                    <?php if ($lockedCount > 0): ?>
                        <span class="summary-pill summary-pill--locked"><?= esc((string) $lockedCount) ?> managed by company</span>
                    <?php endif; ?>
                </div>

                <div class="summary-card__rows">
                    <?php foreach (array_slice($section['fields'], 0, 4) as $field): ?>
                        <div class="summary-card__row">
                            <span><?= esc($field['definition']->label) ?></span>
                            <strong><?= esc($toDisplayValue($field)) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-card__footer">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-open="<?= esc($modalId) ?>">Edit <?= esc($section['title']) ?></button>
                </div>
            </section>

            <div class="action-modal" id="<?= esc($modalId) ?>" hidden>
                <div class="action-modal__backdrop" data-modal-close></div>
                <div class="action-modal__dialog action-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="<?= esc($modalId) ?>-title">
                    <div class="action-modal__header">
                        <div>
                            <h3 id="<?= esc($modalId) ?>-title"><?= esc($section['title']) ?></h3>
                            <p><?= esc($section['subtitle']) ?></p>
                        </div>
                        <button class="action-modal__close" type="button" data-modal-close aria-label="Close">×</button>
                    </div>

                    <form class="form-stack" method="post" action="<?= site_url('branches/' . $branchRecord->id . '/settings/' . $section['category']) ?>">
                        <?= csrf_field() ?>
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
                                <?php if (in_array($valueType, ['bool', 'boolean'], true)): ?>
                                    <div class="field">
                                        <span><?= esc($definition->label) ?></span>
                                        <input type="hidden" name="<?= esc($formKey) ?>" value="0">
                                        <label class="field-toggle">
                                            <span class="field-toggle__copy">
                                                <strong><?= esc($value ? 'Enabled' : 'Disabled') ?></strong>
                                                <small><?= esc($definition->description ?: 'Turn this on when this branch should use this rule.') ?></small>
                                            </span>
                                            <span class="field-toggle__control">
                                                <input type="checkbox" name="<?= esc($formKey) ?>" value="1" <?= $value ? 'checked' : '' ?> <?= $isLocked ? 'disabled' : '' ?>>
                                            </span>
                                        </label>
                                        <?php if ($isLocked): ?>
                                            <small>This option is managed by your company or the EDCRM team.</small>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <label class="field<?= in_array($valueType, ['json', 'array', 'object'], true) ? ' field--full' : '' ?>">
                                        <span><?= esc($definition->label) ?></span>
                                        <?php if ($options !== []): ?>
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
                                            <small>This option is managed by your company or the EDCRM team.</small>
                                        <?php endif; ?>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-actions">
                            <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                            <button class="shell-button shell-button--primary" type="submit">Save <?= esc($section['title']) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?= $this->endSection() ?>
