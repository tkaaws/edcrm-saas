<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Platform master data catalog</h2>
            <p class="module-subtitle">Define shared catalogs, activate or retire values, and standardize tenant-facing dropdowns.</p>
        </div>
    </div>

    <div class="settings-grid">
        <form class="form-card" method="post" action="<?= site_url('platform/master-data/types') ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Create master type</h3>
                    <p class="module-subtitle">Add a new catalog like enquiry source, follow-up status, or course.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Name</span>
                    <input type="text" name="name" value="<?= esc(old('name')) ?>" required>
                </label>
                <label class="field">
                    <span>Code</span>
                    <input type="text" name="code" value="<?= esc(old('code')) ?>" placeholder="auto-generated if left blank">
                </label>
                <label class="field">
                    <span>Module code</span>
                    <input type="text" name="module_code" value="<?= esc(old('module_code', 'enquiries')) ?>" required>
                </label>
                <label class="field">
                    <span>Sort order</span>
                    <input type="number" name="sort_order" value="<?= esc(old('sort_order', '0')) ?>">
                </label>
                <label class="field field--full">
                    <span>Description</span>
                    <textarea name="description" rows="2"><?= esc(old('description')) ?></textarea>
                </label>
            </div>

            <div class="choice-list">
                <label class="checkbox-row">
                    <input type="checkbox" name="allow_tenant_entries" value="1" <?= old('allow_tenant_entries') ? 'checked' : '' ?>>
                    <span>Allow tenant custom entries</span>
                </label>
                <label class="checkbox-row">
                    <input type="checkbox" name="allow_tenant_hide_platform_values" value="1" <?= old('allow_tenant_hide_platform_values') ? 'checked' : '' ?>>
                    <span>Allow tenant hide/show of platform values</span>
                </label>
                <label class="checkbox-row">
                    <input type="checkbox" name="strict_reporting_catalog" value="1" <?= old('strict_reporting_catalog') ? 'checked' : '' ?>>
                    <span>Keep reporting catalog strict</span>
                </label>
                <label class="checkbox-row">
                    <input type="checkbox" name="supports_hierarchy" value="1" <?= old('supports_hierarchy') ? 'checked' : '' ?>>
                    <span>Supports parent/child values</span>
                </label>
            </div>

            <div class="form-actions">
                <button class="shell-button shell-button--primary" type="submit">Create type</button>
            </div>
        </form>

        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Type catalog</h3>
                    <p class="module-subtitle">Current platform-owned master-data groups.</p>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Module</th>
                            <th>Tenant entries</th>
                            <th>Hide platform values</th>
                            <th>Status</th>
                            <th class="data-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($types === []): ?>
                            <tr>
                                <td colspan="6" class="empty-state">No master-data types yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($types as $type): ?>
                            <tr>
                                <td>
                                    <div class="entity-cell">
                                        <strong>
                                            <a href="<?= site_url('platform/master-data?type=' . $type->code) ?>"><?= esc($type->name) ?></a>
                                        </strong>
                                        <span><?= esc($type->code) ?></span>
                                    </div>
                                </td>
                                <td><?= esc($type->module_code) ?></td>
                                <td><?= (int) $type->allow_tenant_entries === 1 ? 'Allowed' : 'Platform only' ?></td>
                                <td><?= (int) $type->allow_tenant_hide_platform_values === 1 ? 'Allowed' : 'Locked' ?></td>
                                <td>
                                    <span class="status-badge <?= $type->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                        <?= esc(ucfirst($type->status)) ?>
                                    </span>
                                </td>
                                <td class="data-table__actions">
                                    <div class="table-actions">
                                        <a class="shell-button shell-button--ghost" href="<?= site_url('platform/master-data?type=' . $type->code) ?>">Open</a>
                                        <form method="post" action="<?= site_url('platform/master-data/types/' . $type->id . '/status') ?>">
                                            <?= csrf_field() ?>
                                            <button class="shell-button shell-button--soft" type="submit">
                                                <?= $type->status === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="settings-grid">
        <form class="form-card" method="post" action="<?= site_url('platform/master-data/values') ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Create platform value</h3>
                    <p class="module-subtitle">Add a shared option under the selected master-data type.</p>
                </div>
            </div>

            <?php if ($selectedType): ?>
                <input type="hidden" name="type_id" value="<?= esc((string) $selectedType->id) ?>">
                <div class="form-grid">
                    <label class="field">
                        <span>Master type</span>
                        <input type="text" value="<?= esc($selectedType->name) ?>" readonly>
                    </label>
                    <label class="field">
                        <span>Label</span>
                        <input type="text" name="label" value="<?= esc(old('label')) ?>" required>
                    </label>
                    <label class="field">
                        <span>Code</span>
                        <input type="text" name="code" value="<?= esc(old('code')) ?>" placeholder="auto-generated if left blank">
                    </label>
                    <label class="field">
                        <span>Parent value</span>
                        <select name="parent_value_id">
                            <option value="">None</option>
                            <?php foreach ($values as $value): ?>
                                <option value="<?= esc((string) $value->id) ?>" <?= old('parent_value_id') == $value->id ? 'selected' : '' ?>>
                                    <?= esc($value->label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Sort order</span>
                        <input type="number" name="sort_order" value="<?= esc(old('sort_order', '0')) ?>">
                    </label>
                    <label class="field">
                        <span>Status</span>
                        <select name="status">
                            <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </label>
                    <label class="field field--full">
                        <span>Description</span>
                        <textarea name="description" rows="2"><?= esc(old('description')) ?></textarea>
                    </label>
                    <label class="field field--full">
                        <span>Metadata JSON</span>
                        <textarea name="metadata_json" rows="3" placeholder='{"duration_months": 6}'><?= esc(old('metadata_json')) ?></textarea>
                    </label>
                </div>

                <div class="choice-list">
                    <label class="checkbox-row">
                        <input type="checkbox" name="is_system" value="1" <?= old('is_system') ? 'checked' : '' ?>>
                        <span>Mark as system default</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="shell-button shell-button--primary" type="submit">Create value</button>
                </div>
            <?php else: ?>
                <p class="empty-state">Create or select a type first to add platform values.</p>
            <?php endif; ?>
        </form>

        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Platform values<?= $selectedType ? ' for ' . esc($selectedType->name) : '' ?></h3>
                    <p class="module-subtitle">Shared options available across tenants unless hidden locally.</p>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Code</th>
                            <th>Scope</th>
                            <th>Parent</th>
                            <th>Status</th>
                            <th class="data-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($values === []): ?>
                            <tr>
                                <td colspan="6" class="empty-state">No values yet for this master-data type.</td>
                            </tr>
                        <?php endif; ?>
                        <?php $parentLabels = []; foreach ($values as $value) { $parentLabels[(int) $value->id] = $value->label; } ?>
                        <?php foreach ($values as $value): ?>
                            <tr>
                                <td>
                                    <div class="entity-cell">
                                        <strong><?= esc($value->label) ?></strong>
                                        <span><?= esc($value->description ?: 'Standard platform value') ?></span>
                                    </div>
                                </td>
                                <td><?= esc($value->code) ?></td>
                                <td><?= esc(ucfirst($value->scope_type)) ?></td>
                                <td><?= esc($parentLabels[(int) ($value->parent_value_id ?? 0)] ?? '—') ?></td>
                                <td>
                                    <span class="status-badge <?= $value->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                        <?= esc(ucfirst($value->status)) ?>
                                    </span>
                                </td>
                                <td class="data-table__actions">
                                    <form method="post" action="<?= site_url('platform/master-data/values/' . $value->id . '/status') ?>">
                                        <?= csrf_field() ?>
                                        <button class="shell-button shell-button--soft" type="submit">
                                            <?= $value->status === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
<?= $this->endSection() ?>
