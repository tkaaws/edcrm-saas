<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $codes = session()->get('user_privilege_codes') ?? []; ?>
<section class="module-page">
    <?php
    $primaryQueues = [
        ['label' => 'Enquiries', 'url' => site_url('enquiries?tab=enquiries')],
        ['label' => 'Today', 'url' => site_url('enquiries?tab=today')],
        ['label' => 'Missed', 'url' => site_url('enquiries?tab=missed')],
        ['label' => 'Fresh', 'url' => site_url('enquiries?tab=fresh')],
    ];
    $secondaryQueues = [
        ['label' => 'Expired', 'url' => site_url('enquiries/expired')],
        ['label' => 'Closed', 'url' => site_url('enquiries/closed')],
        ['label' => 'Bulk Assign', 'url' => site_url('enquiries/bulk-assign'), 'active' => true],
    ];
    ?>
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Bulk Assign Enquiries</h2>
            <p class="module-subtitle">Filter the lead pool first, then move the selected enquiries to the right branch and owner in one pass.</p>
        </div>
        <div class="table-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries') ?>">Back to enquiries</a>
            <?php if (in_array('enquiries.create', $codes, true)): ?>
                <a class="shell-button shell-button--primary" href="<?= site_url('enquiries/create') ?>">Add enquiry</a>
            <?php endif; ?>
        </div>
    </div>

    <nav class="queue-nav" aria-label="Enquiry navigation">
        <div class="queue-nav__group">
            <?php foreach ($primaryQueues as $tab): ?>
                <a class="queue-nav__link" href="<?= $tab['url'] ?>"><?= esc($tab['label']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="queue-nav__group queue-nav__group--secondary">
            <?php foreach ($secondaryQueues as $tab): ?>
                <a class="queue-nav__link queue-nav__link--soft <?= ! empty($tab['active']) ? 'queue-nav__link--active' : '' ?>" href="<?= $tab['url'] ?>">
                    <?= esc($tab['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <form class="form-card form-stack" method="get" action="<?= site_url('enquiries/bulk-assign') ?>">
        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Find enquiries</h3>
                <p class="module-subtitle">Use business filters first so the assignment list stays clean and intentional.</p>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Search</span>
                    <input type="text" name="search" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Name, mobile, source, course, branch">
                </label>
                <label class="field">
                    <span>Queue</span>
                    <select name="queue">
                        <?php $selectedQueue = (string) ($filters['queue'] ?? 'all'); ?>
                        <?php foreach ([
                            'all' => 'All visible enquiries',
                            'open' => 'Open enquiries',
                            'today' => 'Today',
                            'missed' => 'Missed',
                            'fresh' => 'Fresh',
                            'expired' => 'Expired',
                            'closed' => 'Closed',
                        ] as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= $selectedQueue === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Source</span>
                    <select name="source_id">
                        <option value="">All sources</option>
                        <?php $selected = (int) ($filters['source_id'] ?? 0); ?>
                        <?php foreach ($sources as $row): ?>
                            <option value="<?= (int) $row->id ?>" <?= $selected === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Course</span>
                    <select name="primary_course_id">
                        <option value="">All courses</option>
                        <?php $selected = (int) ($filters['primary_course_id'] ?? 0); ?>
                        <?php foreach ($courses as $row): ?>
                            <option value="<?= (int) $row->id ?>" <?= $selected === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Branch</span>
                    <select name="branch_id">
                        <option value="">All allowed branches</option>
                        <?php $selected = (int) ($filters['branch_id'] ?? 0); ?>
                        <?php foreach ($assignableBranches as $branch): ?>
                            <option value="<?= (int) $branch->id ?>" <?= $selected === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Assigned to</span>
                    <select name="owner_user_id">
                        <option value="">All allowed users</option>
                        <?php $selected = (int) ($filters['owner_user_id'] ?? 0); ?>
                        <?php foreach ($assignableUsers as $user): ?>
                            <option value="<?= (int) $user->id ?>" <?= $selected === (int) $user->id ? 'selected' : '' ?>>
                                <?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->email) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="form-actions">
                <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries/bulk-assign') ?>">Reset</a>
                <button class="shell-button shell-button--primary" type="submit">Apply filters</button>
            </div>
        </section>
    </form>

    <form class="form-stack" method="post" action="<?= site_url('enquiries/bulk-assign') ?>">
        <?= csrf_field() ?>

        <section class="form-card">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Matching enquiries</h3>
                <p class="module-subtitle">Select the rows you want to move. Assigned to and assigned on will be updated for every selected enquiry.</p>
            </div>

            <div class="table-wrap">
                <table class="data-table data-table--cards">
                    <thead>
                        <tr>
                            <th class="data-table__checkbox">
                                <label class="selection-check selection-check--inline">
                                    <input id="bulk-assign-all" type="checkbox">
                                    <span></span>
                                </label>
                            </th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Source</th>
                            <th>Course</th>
                            <th>Branch</th>
                            <th>Assigned to</th>
                            <th>Status</th>
                            <th>Created on</th>
                            <th>Modified on</th>
                            <th>Created by</th>
                            <th>Modified by</th>
                            <th class="data-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows === []): ?>
                            <tr>
                                <td colspan="13" class="empty-state">No enquiries matched the current filters.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="data-table__checkbox" data-label="Select">
                                    <label class="selection-check selection-check--inline">
                                        <input class="bulk-assign-row" type="checkbox" name="enquiry_ids[]" value="<?= (int) $row->id ?>">
                                        <span></span>
                                    </label>
                                </td>
                                <td data-label="Name">
                                    <div class="entity-cell">
                                        <strong><a href="<?= site_url('enquiries/' . $row->id) ?>"><?= esc($row->student_name) ?></a></strong>
                                        <span><?= esc($row->college_name ?: ($row->city ?: 'Student enquiry')) ?></span>
                                    </div>
                                </td>
                                <td data-label="Mobile"><?= esc($row->mobile_display) ?></td>
                                <td data-label="Source"><?= esc($row->source_display) ?></td>
                                <td data-label="Course"><?= esc($row->course_display) ?></td>
                                <td data-label="Branch"><?= esc($row->branch_display) ?></td>
                                <td data-label="Assigned to"><?= esc($row->owner_display) ?></td>
                                <td data-label="Status"><span class="status-badge status-badge--good"><?= esc($row->queue_status) ?></span></td>
                                <td data-label="Created on"><?= esc($row->created_at ? date('d M Y', strtotime($row->created_at)) : '-') ?></td>
                                <td data-label="Modified on"><?= esc($row->updated_at ? date('d M Y', strtotime($row->updated_at)) : '-') ?></td>
                                <td data-label="Created by"><?= esc($row->created_by_display) ?></td>
                                <td data-label="Modified by"><?= esc($row->updated_by_display) ?></td>
                                <td class="data-table__actions" data-label="Actions">
                                    <div class="table-actions">
                                        <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('enquiries/' . $row->id) ?>">Open</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="form-card">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Assign selected enquiries</h3>
                <p class="module-subtitle">Move the selected enquiries to one branch and one owner in a single update.</p>
            </div>

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
                    <span>Assigned to</span>
                    <select name="owner_user_id" required>
                        <option value="">Select user</option>
                        <?php foreach ($assignableUsers as $user): ?>
                            <option value="<?= (int) $user->id ?>">
                                <?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->email) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field field--full">
                    <span>Assignment reason</span>
                    <textarea name="assignment_reason" rows="3" placeholder="Why are these enquiries being reassigned?"></textarea>
                </label>
            </div>

            <div class="form-actions">
                <button class="shell-button shell-button--primary" type="submit">Assign selected enquiries</button>
            </div>
        </section>
    </form>
</section>

<script>
(() => {
    const master = document.getElementById('bulk-assign-all');
    const rows = Array.from(document.querySelectorAll('.bulk-assign-row'));

    if (!master || rows.length === 0) {
        return;
    }

    master.addEventListener('change', () => {
        rows.forEach((checkbox) => {
            checkbox.checked = master.checked;
        });
    });

    rows.forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            master.checked = rows.every((row) => row.checked);
        });
    });
})();
</script>
<?= $this->endSection() ?>
