<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php
    $hasSelection = $selectedType !== null;
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
                    <p class="module-subtitle">Choose a business list, review the available options, then add a new value only when needed.</p>
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
                    <p class="module-subtitle">Keep this list clean and business-friendly so companies see only the values they actually need.</p>
                </div>
            </div>
        <?php endif; ?>

        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small"><?= $hasSelection ? 'Available options in ' . esc($selectedType->name) : 'Platform values' ?></h3>
                    <p class="module-subtitle">Shared values available across companies unless hidden locally.</p>
                </div>
                <?php if ($selectedType): ?>
                    <div class="toolbar-actions">
                        <button class="shell-button shell-button--primary" type="button" data-modal-open="platform-master-value-modal">Add value</button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-card">
                <div class="table-wrap">
                    <table class="data-table data-table--cards">
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
                                <td data-label="Name">
                                    <div class="entity-cell">
                                        <strong><?= esc($value->label) ?></strong>
                                        <span><?= esc($value->description ?: 'Standard platform value') ?></span>
                                    </div>
                                </td>
                                <?php $parentId = (int) ($value->parent_value_id ?? 0); ?>
                                <td data-label="Parent"><?= esc($parentLabels[$parentId] ?? '-') ?></td>
                                <td data-label="Status">
                                    <span class="status-badge <?= $value->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                        <?= esc(ucfirst($value->status)) ?>
                                    </span>
                                </td>
                                <td class="data-table__actions" data-label="Actions">
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

    <?php endif; ?>
</section>

<?php if ($selectedType): ?>
    <div class="action-modal" id="platform-master-value-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog action-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="platform-master-value-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="platform-master-value-modal-title">Add a new platform value</h3>
                    <p>Use this only when the required option does not already exist in the list above.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">×</button>
            </div>

            <form class="form-stack" method="post" action="<?= site_url('platform/master-data/values') ?>">
                <?= csrf_field() ?>
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
                                    <option value="<?= esc((string) $value->id) ?>" <?= old('parent_value_id') == $value->id ? 'selected' : '' ?>><?= esc($value->label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small>Use this only for lists that need parent and child values.</small>
                        </label>
                    <?php endif; ?>
                </div>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Create value</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
