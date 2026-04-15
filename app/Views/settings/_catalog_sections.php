<?php foreach ($sections as $section): ?>
    <form class="form-card" method="post" action="<?= site_url('settings/catalog/' . $section['category']) ?>">
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
                $lockMode = $field['lockMode'];
                $valueType = $definition->value_type;
                ?>
                <label class="field<?= $valueType === 'json' ? ' field--full' : '' ?>">
                    <span><?= esc($definition->label) ?></span>

                    <?php if ($valueType === 'bool' || $valueType === 'boolean'): ?>
                        <input type="hidden" name="<?= esc($formKey) ?>" value="0">
                        <label class="checkbox-row">
                            <input type="checkbox" name="<?= esc($formKey) ?>" value="1" <?= $value ? 'checked' : '' ?> <?= $isLocked ? 'disabled' : '' ?>>
                            <span>Enabled</span>
                        </label>
                    <?php elseif ($options !== []): ?>
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
                        <small>This setting is currently locked by platform policy (<?= esc($lockMode) ?>).</small>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button class="shell-button shell-button--primary" type="submit">Save <?= esc($section['title']) ?></button>
        </div>
    </form>
<?php endforeach; ?>
