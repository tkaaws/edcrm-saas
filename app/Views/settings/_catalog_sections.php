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

<div class="summary-grid">
    <?php foreach ($sections as $index => $section): ?>
        <?php $modalId = 'settings-section-modal-' . $index . '-' . preg_replace('/[^a-z0-9_-]+/i', '-', (string) $section['category']); ?>
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
                <span class="summary-pill"><?= esc((string) count($section['fields'])) ?> setting<?= count($section['fields']) === 1 ? '' : 's' ?></span>
                <?php if ($lockedCount > 0): ?>
                    <span class="summary-pill summary-pill--locked"><?= esc((string) $lockedCount) ?> managed by EDCRM</span>
                <?php else: ?>
                    <span class="summary-pill summary-pill--editable">Editable</span>
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

                <form class="form-stack" method="post" action="<?= site_url('settings/catalog/' . $section['category']) ?>">
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
                            $valueType = $definition->value_type;
                            ?>
                            <?php if ($valueType === 'bool' || $valueType === 'boolean'): ?>
                                <div class="field">
                                    <span><?= esc($definition->label) ?></span>
                                    <input type="hidden" name="<?= esc($formKey) ?>" value="0">
                                    <label class="field-toggle">
                                        <span class="field-toggle__copy">
                                            <strong><?= esc($value ? 'Enabled' : 'Disabled') ?></strong>
                                            <small><?= esc($definition->description ?: 'Turn this on when this rule should apply.') ?></small>
                                        </span>
                                        <span class="field-toggle__control">
                                            <input type="checkbox" name="<?= esc($formKey) ?>" value="1" <?= $value ? 'checked' : '' ?> <?= $isLocked ? 'disabled' : '' ?>>
                                        </span>
                                    </label>

                                    <?php if ($isLocked): ?>
                                        <small>This setting is managed by the EDCRM team.</small>
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
                                    <?php elseif ($valueType === 'int' || $valueType === 'integer'): ?>
                                        <input type="number" name="<?= esc($formKey) ?>" value="<?= esc((string) $value) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                                    <?php elseif ($valueType === 'json' || $valueType === 'array' || $valueType === 'object'): ?>
                                        <textarea name="<?= esc($formKey) ?>" rows="3" <?= $isLocked ? 'disabled' : '' ?>><?= esc(is_array($value) ? implode(', ', $value) : (string) $value) ?></textarea>
                                    <?php else: ?>
                                        <input type="text" name="<?= esc($formKey) ?>" value="<?= esc((string) $value) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                                    <?php endif; ?>

                                    <?php if ($definition->description): ?>
                                        <small><?= esc($definition->description) ?></small>
                                    <?php endif; ?>

                                    <?php if ($isLocked): ?>
                                        <small>This setting is managed by the EDCRM team.</small>
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
