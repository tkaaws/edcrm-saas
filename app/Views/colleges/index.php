<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateColleges = in_array('colleges.create', $codes, true); ?>
    <?php $canEditColleges = in_array('colleges.edit', $codes, true); ?>
    <?php $canDeleteColleges = in_array('colleges.delete', $codes, true); ?>

    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Colleges</h2>
            <p class="module-subtitle">Keep the college list ready for enquiry capture and reporting.</p>
        </div>
        <?php if ($canCreateColleges): ?>
            <a class="shell-button shell-button--primary" href="<?= site_url('colleges/create') ?>">Add college</a>
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
                                        <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('colleges/' . $college->id . '/edit') ?>">Edit</a>
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
</section>
<?= $this->endSection() ?>
