<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $selectedCourseId = (int) ($selectedCourseId ?? 0); ?>
    <?php $openCreateModal = (bool) ($openCreateModal ?? false); ?>
    <?= $this->include('admissions/_subnav') ?>

    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Set course-wise fee plans once, then let admissions load them automatically during conversion.</p>
        </div>
        <?php if ($canManageFeeStructures): ?>
            <button class="shell-button shell-button--primary" type="button" data-modal-open="fee-structure-create-modal">Add fee structure</button>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>Fee structure</th>
                        <th>Course</th>
                        <th>Fee heads</th>
                        <th>Total fees</th>
                        <th>Installments</th>
                        <th>Status</th>
                        <?php if ($canManageFeeStructures): ?>
                            <th class="data-table__actions">Quick actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="<?= $canManageFeeStructures ? '7' : '6' ?>" class="empty-state">No fee structures yet. Add the first course-wise plan for admissions.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td data-label="Fee structure">
                                <div class="entity-cell">
                                    <strong><?= esc($row->name) ?></strong>
                                    <span><?= esc($row->description ?: 'Reusable admissions pricing plan') ?></span>
                                </div>
                            </td>
                            <td data-label="Course"><?= esc($row->course_label ?: '-') ?></td>
                            <td data-label="Fee heads">
                                <div class="entity-cell">
                                    <strong><?= esc((string) $row->item_count) ?> rows</strong>
                                    <span><?= esc($row->fee_head_summary ?: '-') ?></span>
                                </div>
                            </td>
                            <td data-label="Total fees"><?= esc(number_format((float) $row->total_amount, 2)) ?></td>
                            <td data-label="Installments"><?= esc((string) $row->default_installment_count) ?> x <?= esc((string) $row->default_installment_gap_days) ?> days</td>
                            <td data-label="Status">
                                <span class="status-badge <?= $row->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= esc(ucfirst($row->status)) ?>
                                </span>
                            </td>
                            <?php if ($canManageFeeStructures): ?>
                                <td class="data-table__actions" data-label="Actions">
                                    <div class="table-actions">
                                        <button class="shell-button shell-button--ghost shell-button--sm" type="button" data-modal-open="fee-structure-edit-modal-<?= (int) $row->id ?>">Edit</button>
                                        <form method="post" action="<?= site_url('admissions/fee-structures/' . $row->id . '/delete') ?>">
                                            <?= csrf_field() ?>
                                            <button class="shell-button shell-button--soft shell-button--sm" type="submit">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= $this->include('shared/pagination') ?>

    <?php if ($canManageFeeStructures): ?>
        <div class="action-modal" id="fee-structure-create-modal" hidden>
            <div class="action-modal__backdrop" data-modal-close></div>
            <div class="action-modal__dialog action-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="fee-structure-create-title">
                <div class="action-modal__header">
                    <div>
                        <h3 id="fee-structure-create-title">Add fee structure</h3>
                        <p>Attach a compact fee plan to a course so admissions can load it automatically.</p>
                    </div>
                    <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
                </div>
                <form class="form-stack" method="post" action="<?= site_url('admissions/fee-structures') ?>" data-fee-structure-form>
                    <?= csrf_field() ?>
                    <?php
                    $formRow = null;
                    $formItems = [];
                    $fieldPrefix = 'items';
                    $selectedCourseId = $selectedCourseId;
                    ?>
                    <?= $this->include('admissions/_fee_structure_form_fields') ?>
                    <div class="form-actions">
                        <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                        <button class="shell-button shell-button--primary" type="submit">Create fee structure</button>
                    </div>
                </form>
            </div>
        </div>

        <?php foreach ($rows as $row): ?>
            <div class="action-modal" id="fee-structure-edit-modal-<?= (int) $row->id ?>" hidden>
                <div class="action-modal__backdrop" data-modal-close></div>
                <div class="action-modal__dialog action-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="fee-structure-edit-title-<?= (int) $row->id ?>">
                    <div class="action-modal__header">
                        <div>
                            <h3 id="fee-structure-edit-title-<?= (int) $row->id ?>">Edit fee structure</h3>
                            <p>Update the selected course-wise fee plan and its fee head rows.</p>
                        </div>
                        <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
                    </div>
                    <form class="form-stack" method="post" action="<?= site_url('admissions/fee-structures/' . $row->id) ?>" data-fee-structure-form>
                        <?= csrf_field() ?>
                        <?php
                        $formRow = $row;
                        $formItems = $row->items ?? [];
                        $fieldPrefix = 'items';
                        ?>
                        <?= $this->include('admissions/_fee_structure_form_fields') ?>
                        <div class="form-actions">
                            <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                            <button class="shell-button shell-button--primary" type="submit">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<script>
(() => {
    <?php if ($canManageFeeStructures && $openCreateModal): ?>
    const autoOpenCreateButton = document.querySelector('[data-modal-open="fee-structure-create-modal"]');
    if (autoOpenCreateButton instanceof HTMLElement) {
        autoOpenCreateButton.click();
    }
    <?php endif; ?>

    document.querySelectorAll('[data-fee-items-builder]').forEach((builder) => {
        const list = builder.querySelector('[data-fee-items-list]');
        const template = builder.querySelector('[data-fee-item-template]');
        const addButton = builder.querySelector('[data-add-fee-item]');
        const prefix = builder.getAttribute('data-field-prefix') || 'items';

        if (!list || !template || !addButton) {
            return;
        }

        const renumberRows = () => {
            const rows = Array.from(list.querySelectorAll('[data-fee-item-row]'));
            rows.forEach((row, index) => {
                const orderValue = index + 1;
                row.querySelectorAll('input, select, textarea').forEach((field) => {
                    if (field.hasAttribute('data-fee-name')) {
                        field.name = `${prefix}[${index}][fee_head_name]`;
                    } else if (field.hasAttribute('data-fee-code')) {
                        field.name = `${prefix}[${index}][fee_head_code]`;
                    } else if (field.hasAttribute('data-fee-amount')) {
                        field.name = `${prefix}[${index}][amount]`;
                    } else if (field.hasAttribute('data-fee-order')) {
                        field.name = `${prefix}[${index}][display_order]`;
                        if (!field.value) {
                            field.value = String(orderValue);
                        }
                    } else if (field.hasAttribute('data-fee-discount')) {
                        field.name = `${prefix}[${index}][allow_discount]`;
                    }
                });

                const existingOrderField = row.querySelector(`input[name^="${prefix}"][name$="[display_order]"]`);
                if (existingOrderField && !existingOrderField.hasAttribute('data-fee-order')) {
                    existingOrderField.value = String(orderValue);
                }
            });
        };

        const addRow = () => {
            const fragment = template.content.cloneNode(true);
            list.appendChild(fragment);
            renumberRows();
        };

        addButton.addEventListener('click', addRow);
        list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-fee-item]');
            if (!button) {
                return;
            }

            const rows = list.querySelectorAll('[data-fee-item-row]');
            if (rows.length <= 1) {
                return;
            }

            button.closest('[data-fee-item-row]')?.remove();
            renumberRows();
        });

        renumberRows();
    });
})();
</script>
<?= $this->endSection() ?>
