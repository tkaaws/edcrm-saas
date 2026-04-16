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
            <h2 class="module-title"><?= esc($tenant->name) ?> Access Controls</h2>
            <p class="module-subtitle">Set which workspace-level defaults can be changed by this company.</p>
        </div>
        <div class="toolbar-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants/' . $tenant->id) ?>">Back to company</a>
            <a class="shell-button shell-button--ghost" href="<?= site_url('platform/tenants') ?>">All companies</a>
        </div>
    </div>

    <div class="settings-grid">
        <div class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Policy summary</h3>
                    <p class="module-subtitle">Workspace-level policy rules set by platform support.</p>
                </div>
            </div>

            <dl class="context-list context-list--wide">
                <div><dt>Company</dt><dd><?= esc($tenant->name) ?></dd></div>
                <div><dt>Company ID</dt><dd><code><?= esc($tenant->slug) ?></code></dd></div>
                <div><dt>Status</dt><dd><?= esc(ucfirst($tenant->status)) ?></dd></div>
                <div><dt>Contact email</dt><dd><?= esc($tenant->owner_email ?: '-') ?></dd></div>
            </dl>
        </div>
    </div>

    <div class="summary-grid">
        <?php foreach ($sections as $section): ?>
            <?php $modalId = 'platform-policy-modal-' . preg_replace('/[^a-z0-9_-]+/i', '-', (string) $section['scope'] . '-' . (string) $section['category']); ?>
            <section class="form-card summary-card">
                <div class="module-toolbar">
                    <div>
                        <h3 class="module-title module-title--small"><?= esc($section['title']) ?></h3>
                        <p class="module-subtitle"><?= esc($section['subtitle']) ?></p>
                    </div>
                </div>

                <div class="summary-card__meta">
                    <span class="summary-pill"><?= esc((string) count($section['fields'])) ?> control<?= count($section['fields']) === 1 ? '' : 's' ?></span>
                    <span class="summary-pill <?= $section['scope'] === 'platform_policy' ? 'summary-pill--locked' : 'summary-pill--editable' ?>">
                        <?= $section['scope'] === 'platform_policy' ? 'Platform enforced' : 'Company override rules' ?>
                    </span>
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

                    <form class="form-stack" method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/policy/' . $section['scope'] . '/' . $section['category']) ?>">
                        <?= csrf_field() ?>
                        <div class="form-grid">
                            <?php foreach ($section['fields'] as $field): ?>
                                <?php
                                $definition = $field['definition'];
                                $formKey = $field['formKey'];
                                $value = old($formKey, $field['value']);
                                $lockMode = old($formKey . '__lock_mode', $field['lockMode']);
                                $notes = old($formKey . '__notes', $field['notes']);
                                $options = $field['options'];
                                $optionLabels = $field['optionLabels'] ?? [];
                                $valueType = (string) $definition->value_type;
                                $platformOnly = $section['scope'] === 'platform_policy';
                                ?>
                                <div class="field field--full">
                                    <span><?= esc($definition->label) ?></span>

                                    <?php if (in_array($valueType, ['bool', 'boolean'], true)): ?>
                                        <input type="hidden" name="<?= esc($formKey) ?>" value="0">
                                        <label class="field-toggle">
                                            <span class="field-toggle__copy">
                                                <strong><?= esc($value ? 'Enabled' : 'Disabled') ?></strong>
                                                <small><?= esc($definition->description ?: 'Turn this on when this rule should be enforced.') ?></small>
                                            </span>
                                            <span class="field-toggle__control">
                                                <input type="checkbox" name="<?= esc($formKey) ?>" value="1" <?= $value ? 'checked' : '' ?>>
                                            </span>
                                        </label>
                                    <?php elseif ($options !== []): ?>
                                        <select name="<?= esc($formKey) ?>">
                                            <?php foreach ($options as $option): ?>
                                                <option value="<?= esc($option) ?>" <?= (string) $value === $option ? 'selected' : '' ?>>
                                                    <?= esc($optionLabels[$option] ?? ucwords(str_replace(['_', '-'], ' ', $option))) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif (in_array($valueType, ['int', 'integer'], true)): ?>
                                        <input type="number" name="<?= esc($formKey) ?>" value="<?= esc((string) $value) ?>">
                                    <?php elseif (in_array($valueType, ['json', 'array', 'object'], true)): ?>
                                        <textarea name="<?= esc($formKey) ?>" rows="3"><?= esc(is_array($value) ? implode(', ', $value) : (string) $value) ?></textarea>
                                    <?php else: ?>
                                        <input type="text" name="<?= esc($formKey) ?>" value="<?= esc((string) $value) ?>">
                                    <?php endif; ?>

                                    <?php if (! $platformOnly): ?>
                                        <div class="form-grid section-divider">
                                            <label class="field">
                                                <span>Lock mode</span>
                                                <select name="<?= esc($formKey) ?>__lock_mode">
                                                    <?php $lockLabels = ['editable' => 'Editable by company', 'tenant_locked' => 'No company override', 'branch_locked' => 'Branch-only control', 'platform_enforced' => 'Platform enforced']; ?>
                                                    <?php foreach ($lockLabels as $mode => $modeLabel): ?>
                                                        <option value="<?= esc($mode) ?>" <?= $lockMode === $mode ? 'selected' : '' ?>><?= esc($modeLabel) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <label class="field">
                                                <span>Notes</span>
                                                <input type="text" name="<?= esc($formKey) ?>__notes" value="<?= esc((string) $notes) ?>">
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <input type="hidden" name="<?= esc($formKey) ?>__lock_mode" value="platform_enforced">
                                        <input type="hidden" name="<?= esc($formKey) ?>__notes" value="<?= esc((string) $notes) ?>">
                                        <small>This platform support policy is always enforced centrally.</small>
                                    <?php endif; ?>
                                </div>
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
