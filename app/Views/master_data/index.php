<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Tenant master data</h2>
            <p class="module-subtitle">Manage easy business lists like Sources, Communication Types, Follow-up Status, and Courses for this tenant.</p>
        </div>
    </div>

    <section class="form-card">
        <div class="module-toolbar">
            <div>
                <h3 class="module-title module-title--small">Master data menu</h3>
                <p class="module-subtitle">Pick the list you want to work on. Example: open Enquiry Source to add sources, or open Mode of Communication to add communication types.</p>
            </div>
        </div>

        <div class="choice-list" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); display:grid;">
            <?php foreach ($types as $type): ?>
                <a class="shell-button <?= $selectedTypeCode === $type->code ? 'shell-button--primary' : 'shell-button--ghost' ?>" href="<?= site_url('settings/master-data?type=' . $type->code) ?>">
                    <?= esc($type->name) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="settings-grid">
        <form class="form-card" method="post" action="<?= $selectedType ? site_url('settings/master-data/' . $selectedType->code) : '#' ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Add value<?= $selectedType ? ' to ' . esc($selectedType->name) : '' ?></h3>
                    <p class="module-subtitle">Create a custom option for the selected catalog when local additions are allowed.</p>
                </div>
            </div>

            <?php if ($selectedType && (int) $selectedType->allow_tenant_entries === 1): ?>
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
                        <label class="field">
                            <span>Parent value</span>
                            <select name="parent_value_id">
                                <option value="">None</option>
                                <?php foreach (($platformValues ?? []) as $value): ?>
                                    <option value="<?= esc((string) $value->id) ?>" <?= old('parent_value_id') == $value->id ? 'selected' : '' ?>>
                                        <?= esc($value->label) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php foreach (($tenantValues ?? []) as $value): ?>
                                    <option value="<?= esc((string) $value->id) ?>" <?= old('parent_value_id') == $value->id ? 'selected' : '' ?>>
                                        <?= esc($value->label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>Shown only for lists that use parent and child values.</small>
                        </label>
                    <?php endif; ?>
                    <label class="field field--full">
                        <span>Metadata JSON</span>
                        <textarea name="metadata_json" rows="3"><?= esc(old('metadata_json')) ?></textarea>
                    </label>
                </div>

                <p class="module-subtitle">Short code is generated automatically from the name.</p>

                <div class="form-actions">
                    <button class="shell-button shell-button--primary" type="submit">Create custom value</button>
                </div>
            <?php elseif ($selectedType): ?>
                <p class="empty-state">This catalog is platform-managed only. Tenant-specific values are disabled.</p>
            <?php else: ?>
                <p class="empty-state">Select a catalog to add tenant-specific values.</p>
            <?php endif; ?>
        </form>
    </div>

    <div class="settings-grid">
        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small"><?= $selectedType ? esc($selectedType->name) : 'Platform values' ?></h3>
                    <p class="module-subtitle">Shared values seeded by the platform. You can hide them locally when the catalog allows it.</p>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Short code</th>
                            <th>Visibility</th>
                            <th>Status</th>
                            <th class="data-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (($platformValues ?? []) === []): ?>
                            <tr>
                                <td colspan="5" class="empty-state">No platform values available for this catalog.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (($platformValues ?? []) as $value): ?>
                            <?php $override = ($overrideMap ?? [])[(int) $value->id] ?? null; ?>
                            <?php $isVisible = ! $override || (int) $override->is_visible === 1; ?>
                            <tr>
                                <td>
                                    <div class="entity-cell">
                                        <strong><?= esc($value->label) ?></strong>
                                        <span><?= esc($value->description ?: 'Shared platform default') ?></span>
                                    </div>
                                </td>
                                <td><?= esc($value->code) ?></td>
                                <td>
                                    <span class="status-badge <?= $isVisible ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                        <?= $isVisible ? 'Visible' : 'Hidden' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $value->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                        <?= esc(ucfirst($value->status)) ?>
                                    </span>
                                </td>
                                <td class="data-table__actions">
                                    <?php if ($selectedType && (int) $selectedType->allow_tenant_hide_platform_values === 1): ?>
                                        <form method="post" action="<?= site_url('settings/master-data/platform-value/' . $value->id . '/toggle') ?>">
                                            <?= csrf_field() ?>
                                            <button class="shell-button shell-button--soft" type="submit">
                                                <?= $isVisible ? 'Hide' : 'Show' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Locked by platform</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Custom values<?= $selectedType ? ' for ' . esc($selectedType->name) : '' ?></h3>
                    <p class="module-subtitle">Tenant-specific entries added on top of the platform catalog.</p>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Short code</th>
                            <th>Status</th>
                            <th class="data-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (($tenantValues ?? []) === []): ?>
                            <tr>
                                <td colspan="4" class="empty-state">No tenant-specific values yet for this catalog.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (($tenantValues ?? []) as $value): ?>
                            <tr>
                                <td>
                                    <div class="entity-cell">
                                        <strong><?= esc($value->label) ?></strong>
                                        <span><?= esc($value->description ?: 'Tenant custom value') ?></span>
                                    </div>
                                </td>
                                <td><?= esc($value->code) ?></td>
                                <td>
                                    <span class="status-badge <?= $value->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                        <?= esc(ucfirst($value->status)) ?>
                                    </span>
                                </td>
                                <td class="data-table__actions">
                                    <form method="post" action="<?= site_url('settings/master-data/tenant-value/' . $value->id . '/status') ?>">
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

    <section class="form-card">
        <div class="module-toolbar">
            <div>
                <h3 class="module-title module-title--small">Effective runtime list</h3>
                <p class="module-subtitle">This is the exact union the application will use in forms and workflows.</p>
            </div>
        </div>

        <div class="choice-list">
            <?php if (($effectiveValues ?? []) === []): ?>
                <span class="empty-state">No effective values available for this catalog yet.</span>
            <?php endif; ?>
            <?php foreach (($effectiveValues ?? []) as $value): ?>
                <span class="status-badge <?= $value->scope_type === 'tenant' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                    <?= esc($value->label) ?> · <?= esc($value->scope_type) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </section>
</section>
<?= $this->endSection() ?>
