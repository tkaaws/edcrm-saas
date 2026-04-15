<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($tenant->name) ?> Access Controls</h2>
            <p class="module-subtitle">Set which workspace-level defaults can be changed by this company.</p>
        </div>
        <div style="display:flex;gap:.5rem;">
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

    <div class="settings-grid">
        <?php foreach ($sections as $section): ?>
            <form class="form-card" method="post" action="<?= site_url('platform/tenants/' . $tenant->id . '/policy/' . $section['scope'] . '/' . $section['category']) ?>">
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
                        $lockMode = old($formKey . '__lock_mode', $field['lockMode']);
                        $notes = old($formKey . '__notes', $field['notes']);
                        $options = $field['options'];
                        $valueType = (string) $definition->value_type;
                        $platformOnly = $section['scope'] === 'platform_policy';
                        ?>
                        <div class="field field--full">
                            <span><?= esc($definition->label) ?></span>

                            <?php if (in_array($valueType, ['bool', 'boolean'], true)): ?>
                                <input type="hidden" name="<?= esc($formKey) ?>" value="0">
                                <label class="checkbox-row">
                                    <input type="checkbox" name="<?= esc($formKey) ?>" value="1" <?= $value ? 'checked' : '' ?>>
                                    <span><?= esc($definition->description ?: 'Enabled') ?></span>
                                </label>
                            <?php elseif ($options !== []): ?>
                                <select name="<?= esc($formKey) ?>">
                                    <?php foreach ($options as $option): ?>
                                        <option value="<?= esc($option) ?>" <?= (string) $value === $option ? 'selected' : '' ?>>
                                            <?= esc(ucwords(str_replace(['_', '-'], ' ', $option))) ?>
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
                                <div class="form-grid" style="margin-top:.75rem;">
                                    <label class="field">
                                        <span>Lock mode</span>
                                        <select name="<?= esc($formKey) ?>__lock_mode">
                                            <?php $lockLabels = ['editable' => 'Editable by company', 'tenant_locked' => 'No company override', 'branch_locked' => 'Branch-only control', 'platform_enforced' => 'Platform enforced']; ?>
                                            <?php foreach ($lockLabels as $mode => $modeLabel): ?>
                                                <option value="<?= esc($mode) ?>" <?= $lockMode === $mode ? 'selected' : '' ?>>
                                                    <?= esc($modeLabel) ?>
                                                </option>
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
                    <button class="shell-button shell-button--primary" type="submit">Save <?= esc($section['title']) ?></button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?= $this->endSection() ?>
