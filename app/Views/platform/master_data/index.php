<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php
    $hasSelection = $selectedType !== null;
    $valueCount = count($values ?? []);
    ?>
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Platform Business Lookup Data</h2>
            <p class="module-subtitle">Manage plain-language business lists like Sources, Communication Types, Follow-up Status, and Courses.</p>
        </div>
    </div>

    <?php if ($types === []): ?>
        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Initialize standard lookup lists</h3>
                    <p class="module-subtitle">We already know the common business lists. Start with defaults like Enquiry Source, Communication Type, Follow-up Status, and Course.</p>
                </div>
            </div>
            <div class="choice-list">
                <span class="status-badge status-badge--neutral">Enquiry Source</span>
                <span class="status-badge status-badge--neutral">Lead Qualification</span>
                <span class="status-badge status-badge--neutral">Follow-up Status</span>
                <span class="status-badge status-badge--neutral">Communication Type</span>
                <span class="status-badge status-badge--neutral">Lost Reason</span>
                <span class="status-badge status-badge--neutral">Closure Reason</span>
                <span class="status-badge status-badge--neutral">Purpose Category</span>
                <span class="status-badge status-badge--neutral">Course</span>
            </div>
            <div class="form-actions">
                <form method="post" action="<?= site_url('platform/master-data/initialize') ?>">
                    <?= csrf_field() ?>
                    <button class="shell-button shell-button--primary" type="submit">Load standard lookup lists</button>
                </form>
            </div>
        </section>
    <?php else: ?>
        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Lookup data menu</h3>
                    <p class="module-subtitle">Choose a list to review available options first, then add or update values.</p>
                </div>
            </div>

            <div class="catalog-menu-grid">
                <?php foreach ($types as $type): ?>
                    <a class="shell-button <?= $selectedTypeCode === $type->code ? 'shell-button--primary' : 'shell-button--ghost' ?>" href="<?= site_url('platform/master-data?type=' . $type->code) ?>">
                        <?= esc($type->name) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($hasSelection): ?>
            <div class="catalog-hero">
                <div class="catalog-hero__copy">
                    <h3 class="module-title module-title--small"><?= esc($selectedType->name) ?></h3>
                    <p class="module-subtitle">Review platform values first, then manage list rules and add new values when needed.</p>
                </div>
                <div class="catalog-stats">
                    <div class="catalog-stat">
                        <span class="catalog-stat__label">Platform values</span>
                        <strong class="catalog-stat__value"><?= esc((string) $valueCount) ?></strong>
                    </div>
                    <div class="catalog-stat">
                        <span class="catalog-stat__label">Company additions</span>
                        <strong class="catalog-stat__value"><?= (int) $selectedType->allow_tenant_entries === 1 ? 'On' : 'Off' ?></strong>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small"><?= $hasSelection ? 'Available options in ' . esc($selectedType->name) : 'Platform values' ?></h3>
                    <p class="module-subtitle">Shared values available across companies unless hidden locally.</p>
                </div>
            </div>

            <div class="table-card">
                <div class="table-wrap">
                    <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Parent</th>
                            <th>Status</th>
                            <th class="data-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($values === []): ?>
                            <tr>
                                <td colspan="4" class="empty-state">No values yet for this list.</td>
                            </tr>
                        <?php endif; ?>
                        <?php $parentLabels = []; foreach ($values as $value): $parentLabels[(int) $value->id] = $value->label; endforeach; ?>
                        <?php foreach ($values as $value): ?>
                            <tr>
                                <td>
                                    <div class="entity-cell">
                                        <strong><?= esc($value->label) ?></strong>
                                        <span><?= esc($value->description ?: 'Standard platform value') ?></span>
                                    </div>
                                </td>
                                <?php $parentId = (int) ($value->parent_value_id ?? 0); ?>
                                <td><?= esc($parentLabels[$parentId] ?? '-') ?></td>
                                <td>
                                    <span class="status-badge <?= $value->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                        <?= esc(ucfirst($value->status)) ?>
                                    </span>
                                </td>
                                <td class="data-table__actions">
                                    <form method="post" action="<?= site_url('platform/master-data/values/' . $value->id . '/status') ?>">
                                        <?= csrf_field() ?>
                                        <button class="shell-button shell-button--soft shell-button--sm" type="submit">
                                            <?= $value->status === 'active' ? 'Hide' : 'Show' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </section>

        <div class="catalog-grid">
            <section class="form-card">
                <div class="module-toolbar">
                    <div>
                        <h3 class="module-title module-title--small">List rules</h3>
                        <p class="module-subtitle">The current business list you are managing.</p>
                    </div>
                </div>

                <?php if ($selectedType): ?>
                    <div class="table-card">
                        <div class="table-wrap">
                            <table class="data-table">
                                <tbody>
                                    <tr><th>Name</th><td><?= esc($selectedType->name) ?></td></tr>
                                    <tr><th>Used in</th><td><?= esc(ucwords(str_replace('_', ' ', $selectedType->module_code))) ?></td></tr>
                                    <tr><th>Company custom entries</th><td><?= (int) $selectedType->allow_tenant_entries === 1 ? 'Allowed' : 'Platform only' ?></td></tr>
                                    <tr><th>Company hide/show</th><td><?= (int) $selectedType->allow_tenant_hide_platform_values === 1 ? 'Allowed' : 'Locked' ?></td></tr>
                                    <tr><th>Status</th><td><?= esc(ucfirst($selectedType->status)) ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Choose a lookup list first to view list rules.</p>
                <?php endif; ?>
            </section>

            <form class="form-card" method="post" action="<?= site_url('platform/master-data/values') ?>">
                <?= csrf_field() ?>
                <div class="module-toolbar">
                    <div>
                        <h3 class="module-title module-title--small">Add a new platform value</h3>
                        <p class="module-subtitle">Use this only when the required option does not already exist in the list above.</p>
                    </div>
                </div>

                <?php if ($selectedType): ?>
                    <input type="hidden" name="type_id" value="<?= esc((string) $selectedType->id) ?>">
                    <div class="form-grid">
                        <label class="field">
                            <span>Name</span>
                            <input type="text" name="label" value="<?= esc(old('label')) ?>" required>
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
                        <?php if ((int) $selectedType->supports_hierarchy === 1): ?>
                            <label class="field field--full">
                                <span>Parent option</span>
                                <select name="parent_value_id">
                                    <option value="">No parent</option>
                                    <?php foreach ($values as $value): ?>
                                        <option value="<?= esc((string) $value->id) ?>" <?= old('parent_value_id') == $value->id ? 'selected' : '' ?>>
                                            <?= esc($value->label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small>Use this only for lists that need parent and child values.</small>
                            </label>
                        <?php endif; ?>
                        <label class="field field--full">
                            <span>Extra details (optional)</span>
                            <textarea name="metadata_json" rows="3" placeholder='{"duration_months": 6}'><?= esc(old('metadata_json')) ?></textarea>
                        </label>
                    </div>

                    <div class="choice-list">
                        <label class="checkbox-row">
                            <input type="checkbox" name="is_system" value="1" <?= old('is_system') ? 'checked' : '' ?>>
                            <span>Mark as shared system option</span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button class="shell-button shell-button--primary" type="submit">Create value</button>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Choose a lookup list first to add platform values.</p>
                <?php endif; ?>
            </form>
        </div>

        <details class="form-card">
            <summary><strong>Advanced catalog setup</strong></summary>
            <p class="module-subtitle" style="margin-top:1rem;">Use this only when we are introducing a genuinely new business catalog beyond the standard lists.</p>
            <form method="post" action="<?= site_url('platform/master-data/types') ?>" style="margin-top:1rem;">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="field">
                        <span>Name</span>
                        <input type="text" name="name" value="<?= esc(old('name')) ?>">
                    </label>
                    <label class="field">
                        <span>Code</span>
                        <input type="text" name="code" value="<?= esc(old('code')) ?>" placeholder="auto-generated if left blank">
                    </label>
                    <label class="field">
                        <span>Module code</span>
                        <input type="text" name="module_code" value="<?= esc(old('module_code', 'enquiries')) ?>">
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
                    <label class="checkbox-row"><input type="checkbox" name="allow_tenant_entries" value="1" <?= old('allow_tenant_entries') ? 'checked' : '' ?>><span>Allow company custom entries</span></label>
                    <label class="checkbox-row"><input type="checkbox" name="allow_tenant_hide_platform_values" value="1" <?= old('allow_tenant_hide_platform_values') ? 'checked' : '' ?>><span>Allow company hide/show of platform values</span></label>
                    <label class="checkbox-row"><input type="checkbox" name="strict_reporting_catalog" value="1" <?= old('strict_reporting_catalog') ? 'checked' : '' ?>><span>Keep reporting catalog strict</span></label>
                    <label class="checkbox-row"><input type="checkbox" name="supports_hierarchy" value="1" <?= old('supports_hierarchy') ? 'checked' : '' ?>><span>Supports parent/child values</span></label>
                </div>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="submit">Create advanced type</button>
                </div>
            </form>
        </details>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
