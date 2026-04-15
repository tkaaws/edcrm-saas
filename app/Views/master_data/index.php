<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php
    $hasSelection = $selectedType !== null;
    $canAddCompanyValue = $hasSelection && (int) $selectedType->allow_tenant_entries === 1;
    ?>
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Business Lookup Data</h2>
            <p class="module-subtitle">Manage shared lists like sources, follow-up status, and courses.</p>
        </div>
    </div>

    <section class="form-card">
        <div class="module-toolbar">
            <div>
                <h3 class="module-title module-title--small">Lookup data menu</h3>
                <p class="module-subtitle">Choose a list, review what already exists, then add company options if needed.</p>
            </div>
        </div>

        <div class="catalog-menu-grid">
            <?php foreach ($types as $type): ?>
                <a class="shell-button <?= $selectedTypeCode === $type->code ? 'shell-button--primary' : 'shell-button--ghost' ?>" href="<?= site_url('settings/master-data?type=' . $type->code) ?>">
                    <?= esc($type->name) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (! $hasSelection): ?>
        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Choose a lookup list</h3>
                    <p class="module-subtitle">Start by choosing one list from the menu above. We will show the available options and let you add new ones only when needed.</p>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small"><?= esc($selectedType->name) ?></h3>
                    <p class="module-subtitle">Keep this list simple. Standard values stay protected, and your custom values can be managed here.</p>
                </div>
            </div>

            <div class="table-wrap">
                <div class="table-card">
                    <table class="data-table data-table--cards">
                        <thead>
                            <tr>
                                <th>Value</th>
                                <th>Availability</th>
                                <th>Status</th>
                                <th class="data-table__actions">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (($catalogValues ?? []) === []): ?>
                                <tr>
                                    <td colspan="4" class="empty-state">No values are available for this list yet.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach (($catalogValues ?? []) as $value): ?>
                                <tr>
                                    <td data-label="Value">
                                        <div class="entity-cell">
                                            <strong><?= esc($value->label) ?></strong>
                                            <span><?= esc($value->description ?: ($value->is_protected ? 'Standard value' : 'Custom value')) ?></span>
                                        </div>
                                    </td>
                                    <td data-label="Availability">
                                        <span class="status-badge <?= $value->is_visible_for_company ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                            <?= $value->is_visible_for_company ? 'Available' : 'Hidden' ?>
                                        </span>
                                    </td>
                                    <td data-label="Status">
                                        <?php if ($value->is_protected): ?>
                                            <span class="status-badge status-badge--neutral">Protected</span>
                                        <?php else: ?>
                                            <span class="status-badge status-badge--info">Custom</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="data-table__actions" data-label="Action">
                                        <?php if ($value->is_protected && (int) $selectedType->allow_tenant_hide_platform_values === 1): ?>
                                            <form method="post" action="<?= site_url('settings/master-data/platform-value/' . $value->id . '/toggle') ?>">
                                                <?= csrf_field() ?>
                                                <button class="shell-button shell-button--soft shell-button--sm" type="submit">
                                                    <?= $value->is_visible_for_company ? 'Hide' : 'Show' ?>
                                                </button>
                                            </form>
                                        <?php elseif (! $value->is_protected): ?>
                                            <form method="post" action="<?= site_url('settings/master-data/tenant-value/' . $value->id . '/delete') ?>" onsubmit="return confirm('Remove <?= esc(addslashes($value->label)) ?> from this list?')">
                                                <?= csrf_field() ?>
                                                <button class="shell-button shell-button--danger shell-button--sm" type="submit">Remove</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Always available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="catalog-grid">
            <form class="form-card" method="post" action="<?= site_url('settings/master-data/' . $selectedType->code) ?>">
                <?= csrf_field() ?>
                <div class="module-toolbar">
                    <div>
                        <h3 class="module-title module-title--small">Add a new value</h3>
                        <p class="module-subtitle">Add a value only when your company needs an option that is not already available above.</p>
                    </div>
                </div>

                <?php if ($canAddCompanyValue): ?>
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
                                    <?php foreach (($catalogValues ?? []) as $value): ?>
                                        <option value="<?= esc((string) $value->id) ?>" <?= old('parent_value_id') == $value->id ? 'selected' : '' ?>>
                                            <?= esc($value->label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small>Use this only for lists that need parent and child values.</small>
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button class="shell-button shell-button--primary" type="submit">Create value</button>
                    </div>
                <?php else: ?>
                    <p class="empty-state">This list is managed centrally. New values cannot be added for this company.</p>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

</section>
<?= $this->endSection() ?>
