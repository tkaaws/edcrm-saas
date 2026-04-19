<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateBatches = in_array('batches.create', $codes, true); ?>
    <?php $canEditBatches = in_array('batches.edit', $codes, true); ?>
    <?php $canDeleteBatches = in_array('batches.delete', $codes, true); ?>

    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Keep active batches ready for admissions and scheduling.</p>
        </div>
        <?php if ($canCreateBatches): ?>
            <button class="shell-button shell-button--primary" type="button" data-modal-open="batch-create-modal">Add batch</button>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Branch</th>
                        <th>Starts</th>
                        <th>Ends</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th class="data-table__actions">Quick actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($batches === []): ?>
                        <tr>
                            <td colspan="7" class="empty-state">No batches yet. Add the first batch for admissions and scheduling.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($batches as $batch): ?>
                        <tr>
                            <td data-label="Batch">
                                <div class="entity-cell">
                                    <strong><?= esc($batch->name) ?></strong>
                                    <span><?= esc($batch->code ?: 'No code') ?></span>
                                </div>
                            </td>
                            <td data-label="Branch"><?= esc($batch->branch_name ?: '-') ?></td>
                            <td data-label="Starts"><?= esc($batch->starts_on ? date('d M Y', strtotime($batch->starts_on)) : '-') ?></td>
                            <td data-label="Ends"><?= esc($batch->ends_on ? date('d M Y', strtotime($batch->ends_on)) : '-') ?></td>
                            <td data-label="Capacity"><?= esc($batch->capacity ?: '-') ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?= $batch->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= esc(ucfirst($batch->status)) ?>
                                </span>
                            </td>
                            <td class="data-table__actions" data-label="Actions">
                                <div class="table-actions">
                                    <?php if ($canEditBatches): ?>
                                        <button class="shell-button shell-button--ghost shell-button--sm" type="button" data-modal-open="batch-edit-modal-<?= (int) $batch->id ?>">Edit</button>
                                    <?php endif; ?>
                                    <?php if ($canDeleteBatches): ?>
                                        <form method="post" action="<?= site_url('batches/' . $batch->id . '/delete') ?>" onsubmit="return confirm('Remove this batch?');">
                                            <?= csrf_field() ?>
                                            <button class="shell-button shell-button--soft shell-button--sm" type="submit">Remove</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= $this->include('shared/pagination') ?>

    <?php if ($canCreateBatches): ?>
        <div class="action-modal" id="batch-create-modal" hidden>
            <div class="action-modal__backdrop" data-modal-close></div>
            <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="batch-create-modal-title">
                <div class="action-modal__header">
                    <div>
                        <h3 id="batch-create-modal-title">Add batch</h3>
                        <p>Create a batch once so admissions can assign students to it quickly.</p>
                    </div>
                    <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
                </div>
                <form class="form-stack" method="post" action="<?= site_url('batches') ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <label class="field">
                            <span>Branch</span>
                            <select name="branch_id" required>
                                <option value="">Select branch</option>
                                <?php foreach ($assignableBranches as $branch): ?>
                                    <option value="<?= (int) $branch->id ?>"><?= esc($branch->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Batch name</span>
                            <input type="text" name="name" required>
                        </label>
                        <label class="field">
                            <span>Batch code</span>
                            <input type="text" name="code">
                        </label>
                        <label class="field">
                            <span>Status</span>
                            <select name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="completed">Completed</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Start date</span>
                            <input type="date" name="starts_on">
                        </label>
                        <label class="field">
                            <span>End date</span>
                            <input type="date" name="ends_on">
                        </label>
                        <label class="field">
                            <span>Capacity</span>
                            <input type="number" min="0" name="capacity">
                        </label>
                        <label class="field field--full">
                            <span>Notes</span>
                            <textarea name="notes" rows="3"></textarea>
                        </label>
                    </div>
                    <div class="form-actions">
                        <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                        <button class="shell-button shell-button--primary" type="submit">Create batch</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canEditBatches): ?>
        <?php foreach ($batches as $batch): ?>
            <div class="action-modal" id="batch-edit-modal-<?= (int) $batch->id ?>" hidden>
                <div class="action-modal__backdrop" data-modal-close></div>
                <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="batch-edit-modal-title-<?= (int) $batch->id ?>">
                    <div class="action-modal__header">
                        <div>
                            <h3 id="batch-edit-modal-title-<?= (int) $batch->id ?>">Edit batch</h3>
                            <p>Update the batch details without leaving the list.</p>
                        </div>
                        <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
                    </div>
                    <form class="form-stack" method="post" action="<?= site_url('batches/' . $batch->id) ?>">
                        <?= csrf_field() ?>
                        <div class="form-grid">
                            <label class="field">
                                <span>Branch</span>
                                <select name="branch_id" required>
                                    <option value="">Select branch</option>
                                    <?php foreach ($assignableBranches as $branch): ?>
                                        <option value="<?= (int) $branch->id ?>" <?= (int) $batch->branch_id === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="field">
                                <span>Batch name</span>
                                <input type="text" name="name" value="<?= esc($batch->name) ?>" required>
                            </label>
                            <label class="field">
                                <span>Batch code</span>
                                <input type="text" name="code" value="<?= esc($batch->code) ?>">
                            </label>
                            <label class="field">
                                <span>Status</span>
                                <select name="status">
                                    <option value="active" <?= $batch->status === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $batch->status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="completed" <?= $batch->status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>Start date</span>
                                <input type="date" name="starts_on" value="<?= esc($batch->starts_on) ?>">
                            </label>
                            <label class="field">
                                <span>End date</span>
                                <input type="date" name="ends_on" value="<?= esc($batch->ends_on) ?>">
                            </label>
                            <label class="field">
                                <span>Capacity</span>
                                <input type="number" min="0" name="capacity" value="<?= esc($batch->capacity) ?>">
                            </label>
                            <label class="field field--full">
                                <span>Notes</span>
                                <textarea name="notes" rows="3"><?= esc($batch->notes) ?></textarea>
                            </label>
                        </div>
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
<?= $this->endSection() ?>
