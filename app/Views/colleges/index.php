<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateColleges = in_array('colleges.create', $codes, true); ?>
    <?php $canEditColleges = in_array('colleges.edit', $codes, true); ?>
    <?php $canDeleteColleges = in_array('colleges.delete', $codes, true); ?>

    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Keep the college list ready for enquiry capture and reporting.</p>
        </div>
        <?php if ($canCreateColleges): ?>
            <button class="shell-button shell-button--primary" type="button" data-modal-open="college-create-modal">Add college</button>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>College</th>
                        <th>City</th>
                        <th>State</th>
                        <th>Status</th>
                        <th class="data-table__actions">Quick actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($colleges === []): ?>
                        <tr>
                            <td colspan="5" class="empty-state">No colleges yet. Add the first college for your company.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($colleges as $college): ?>
                        <tr>
                            <td data-label="College">
                                <div class="entity-cell">
                                    <strong><?= esc($college->name) ?></strong>
                                    <span>Used in enquiry capture</span>
                                </div>
                            </td>
                            <td data-label="City"><?= esc($college->city_name) ?></td>
                            <td data-label="State"><?= esc($college->state_name) ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?= $college->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= esc(ucfirst($college->status)) ?>
                                </span>
                            </td>
                            <td class="data-table__actions" data-label="Actions">
                                <div class="table-actions">
                                    <?php if ($canEditColleges): ?>
                                        <button class="shell-button shell-button--ghost shell-button--sm" type="button" data-modal-open="college-edit-modal-<?= (int) $college->id ?>">Edit</button>
                                    <?php endif; ?>
                                    <?php if ($canDeleteColleges): ?>
                                        <form method="post" action="<?= site_url('colleges/' . $college->id . '/delete') ?>">
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

    <?php if ($canCreateColleges): ?>
        <div class="action-modal" id="college-create-modal" hidden>
            <div class="action-modal__backdrop" data-modal-close></div>
            <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="college-create-modal-title">
                <div class="action-modal__header">
                    <div>
                        <h3 id="college-create-modal-title">Add college</h3>
                        <p>Add a college with its city and state so enquiry capture stays clean.</p>
                    </div>
                    <button class="action-modal__close" type="button" data-modal-close aria-label="Close">×</button>
                </div>
                <form class="form-stack" method="post" action="<?= site_url('colleges') ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <label class="field field--full">
                            <span>College name</span>
                            <input type="text" name="name" required>
                        </label>
                        <label class="field">
                            <span>City</span>
                            <input type="text" name="city_name" required>
                        </label>
                        <label class="field">
                            <span>State</span>
                            <input type="text" name="state_name" required>
                        </label>
                        <label class="field">
                            <span>Status</span>
                            <select name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </label>
                    </div>
                    <div class="form-actions">
                        <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                        <button class="shell-button shell-button--primary" type="submit">Create college</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canEditColleges): ?>
        <?php foreach ($colleges as $college): ?>
            <div class="action-modal" id="college-edit-modal-<?= (int) $college->id ?>" hidden>
                <div class="action-modal__backdrop" data-modal-close></div>
                <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="college-edit-modal-title-<?= (int) $college->id ?>">
                    <div class="action-modal__header">
                        <div>
                            <h3 id="college-edit-modal-title-<?= (int) $college->id ?>">Edit college</h3>
                            <p>Update the college details used during enquiry capture and reporting.</p>
                        </div>
                        <button class="action-modal__close" type="button" data-modal-close aria-label="Close">×</button>
                    </div>
                    <form class="form-stack" method="post" action="<?= site_url('colleges/' . $college->id) ?>">
                        <?= csrf_field() ?>
                        <div class="form-grid">
                            <label class="field field--full">
                                <span>College name</span>
                                <input type="text" name="name" value="<?= esc($college->name) ?>" required>
                            </label>
                            <label class="field">
                                <span>City</span>
                                <input type="text" name="city_name" value="<?= esc($college->city_name) ?>" required>
                            </label>
                            <label class="field">
                                <span>State</span>
                                <input type="text" name="state_name" value="<?= esc($college->state_name) ?>" required>
                            </label>
                            <label class="field">
                                <span>Status</span>
                                <select name="status">
                                    <option value="active" <?= $college->status === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $college->status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
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
