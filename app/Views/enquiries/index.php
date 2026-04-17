<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$codes = session()->get('user_privilege_codes') ?? [];
$canCreateEnquiry = in_array('enquiries.create', $codes, true);
$canBulkAssign = in_array('enquiries.bulk_assign', $codes, true);
$editableRowsById = $editableRowsById ?? [];
$columnCount = match ($currentTab ?? 'enquiries') {
    'today', 'fresh' => 11,
    'missed' => 12,
    'expired', 'closed' => 13,
    default => 12,
};
?>
<section class="module-page">
    <?php
    $primaryQueues = [
        'enquiries' => ['label' => 'Enquiries', 'url' => site_url('enquiries?tab=enquiries')],
        'today'     => ['label' => 'Today', 'url' => site_url('enquiries?tab=today')],
        'missed'    => ['label' => 'Missed', 'url' => site_url('enquiries?tab=missed')],
        'fresh'     => ['label' => 'Fresh', 'url' => site_url('enquiries?tab=fresh')],
    ];
    $secondaryQueues = [
        'expired' => ['label' => 'Expired', 'url' => site_url('enquiries/expired')],
        'closed'  => ['label' => 'Closed', 'url' => site_url('enquiries/closed')],
    ];
    if ($canBulkAssign) {
        $secondaryQueues['bulk-assign'] = ['label' => 'Bulk Assign', 'url' => site_url('enquiries/bulk-assign')];
    }
    ?>
    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Track active leads, follow-ups, and recovery queues without clutter.</p>
        </div>
        <div class="table-actions">
            <?php if ($canBulkAssign): ?>
                <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries/bulk-assign') ?>">Bulk assign</a>
            <?php endif; ?>
            <?php if ($canCreateEnquiry): ?>
                <button class="shell-button shell-button--primary" type="button" data-modal-open="create-enquiry-modal">Add enquiry</button>
            <?php endif; ?>
        </div>
    </div>

    <nav class="queue-nav" aria-label="Enquiry navigation">
        <div class="queue-nav__group">
            <?php foreach ($primaryQueues as $tabCode => $tab): ?>
                <a class="queue-nav__link <?= $currentTab === $tabCode ? 'queue-nav__link--active' : '' ?>" href="<?= $tab['url'] ?>">
                    <?= esc($tab['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="queue-nav__group queue-nav__group--secondary">
            <?php foreach ($secondaryQueues as $tabCode => $tab): ?>
                <a class="queue-nav__link queue-nav__link--soft <?= $currentTab === $tabCode ? 'queue-nav__link--active' : '' ?>" href="<?= $tab['url'] ?>">
                    <?= esc($tab['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Source</th>
                        <th>Course</th>
                        <?php if ($currentTab === 'today'): ?>
                            <th>Assigned to</th>
                            <th>Due time</th>
                        <?php elseif ($currentTab === 'missed'): ?>
                            <th>Assigned to</th>
                            <th>Due date</th>
                            <th>Overdue by</th>
                        <?php elseif ($currentTab === 'fresh'): ?>
                            <th>Branch</th>
                            <th>Assigned to</th>
                        <?php elseif ($currentTab === 'expired'): ?>
                            <th>Branch</th>
                            <th>Assigned to</th>
                            <th>Last follow-up</th>
                            <th>Expired on</th>
                        <?php elseif ($currentTab === 'closed'): ?>
                            <th>Branch</th>
                            <th>Assigned to</th>
                            <th>Closed by</th>
                            <th>Closed on</th>
                        <?php else: ?>
                            <th>Branch</th>
                            <th>Assigned to</th>
                            <th>Status</th>
                        <?php endif; ?>
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
                            <td colspan="<?= $columnCount ?>" class="empty-state">No enquiries found in this queue yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td data-label="Name">
                                <div class="entity-cell">
                                    <strong><a href="<?= site_url('enquiries/' . $row->id) ?>"><?= esc($row->student_name) ?></a></strong>
                                    <span><?= esc($row->city ?: ($row->college_name ?: 'Student enquiry')) ?></span>
                                </div>
                            </td>
                            <td data-label="Mobile"><?= esc($row->mobile_display) ?></td>
                            <td data-label="Source"><?= esc($row->source_display) ?></td>
                            <td data-label="Course"><?= esc($row->course_display) ?></td>
                            <?php if ($currentTab === 'today'): ?>
                                <td data-label="Assigned to"><?= esc($row->owner_display) ?></td>
                                <td data-label="Due time"><?= esc($row->next_followup_at ? date('d M Y h:i A', strtotime($row->next_followup_at)) : '-') ?></td>
                            <?php elseif ($currentTab === 'missed'): ?>
                                <td data-label="Assigned to"><?= esc($row->owner_display) ?></td>
                                <td data-label="Due date"><?= esc($row->next_followup_at ? date('d M Y', strtotime($row->next_followup_at)) : '-') ?></td>
                                <td data-label="Overdue by"><?= esc($row->overdue_by !== null ? $row->overdue_by . ' days' : '-') ?></td>
                            <?php elseif ($currentTab === 'fresh'): ?>
                                <td data-label="Branch"><?= esc($row->branch_display) ?></td>
                                <td data-label="Assigned to"><?= esc($row->owner_display) ?></td>
                            <?php elseif ($currentTab === 'expired'): ?>
                                <td data-label="Branch"><?= esc($row->branch_display) ?></td>
                                <td data-label="Assigned to"><?= esc($row->owner_display) ?></td>
                                <td data-label="Last follow-up"><?= esc($row->last_followup_at ? date('d M Y', strtotime($row->last_followup_at)) : '-') ?></td>
                                <td data-label="Expired on"><?= esc($row->expired_on ? date('d M Y', strtotime($row->expired_on)) : '-') ?></td>
                            <?php elseif ($currentTab === 'closed'): ?>
                                <td data-label="Branch"><?= esc($row->branch_display) ?></td>
                                <td data-label="Assigned to"><?= esc($row->owner_display) ?></td>
                                <td data-label="Closed by"><?= esc($row->closed_by_display) ?></td>
                                <td data-label="Closed on"><?= esc($row->closed_at ? date('d M Y', strtotime($row->closed_at)) : '-') ?></td>
                            <?php else: ?>
                                <td data-label="Branch"><?= esc($row->branch_display) ?></td>
                                <td data-label="Assigned to"><?= esc($row->owner_display) ?></td>
                                <td data-label="Status">
                                    <span class="status-badge <?= $row->display_status === 'Active' ? 'status-badge--good' : 'status-badge--neutral' ?>"><?= esc($row->display_status) ?></span>
                                </td>
                            <?php endif; ?>
                            <td data-label="Created on"><?= esc($row->created_at ? date('d M Y', strtotime($row->created_at)) : '-') ?></td>
                            <td data-label="Modified on"><?= esc($row->updated_at ? date('d M Y', strtotime($row->updated_at)) : '-') ?></td>
                            <td data-label="Created by"><?= esc($row->created_by_display) ?></td>
                            <td data-label="Modified by"><?= esc($row->updated_by_display) ?></td>
                            <td class="data-table__actions" data-label="Actions">
                                <div class="table-actions">
                                    <?php if (in_array('enquiries.edit', $codes, true) && in_array($row->lifecycle_status, ['new', 'active'], true)): ?>
                                        <?php $editRow = $editableRowsById[(int) $row->id] ?? $row; ?>
                                        <button
                                            class="shell-button shell-button--ghost shell-button--sm"
                                            type="button"
                                            data-modal-open="edit-enquiry-modal-<?= (int) $row->id ?>"
                                            data-edit-enquiry
                                            data-enquiry-id="<?= (int) $row->id ?>"
                                            data-student-name="<?= esc($editRow->student_name ?? '', 'attr') ?>"
                                            data-mobile="<?= esc($editRow->mobile ?? '', 'attr') ?>"
                                            data-email="<?= esc($editRow->email ?? '', 'attr') ?>"
                                            data-whatsapp-number="<?= esc($editRow->whatsapp_number ?? '', 'attr') ?>"
                                            data-source-id="<?= (int) ($editRow->source_id ?? 0) ?>"
                                            data-course-id="<?= (int) ($editRow->primary_course_id ?? 0) ?>"
                                            data-college-id="<?= (int) ($editRow->college_id ?? 0) ?>"
                                            data-qualification-id="<?= (int) ($editRow->qualification_id ?? 0) ?>"
                                            data-city="<?= esc($editRow->city ?? '', 'attr') ?>"
                                            data-next-followup-at="<?= esc(! empty($editRow->next_followup_at) ? date('Y-m-d\TH:i', strtotime($editRow->next_followup_at)) : '', 'attr') ?>"
                                            data-notes="<?= esc($editRow->notes ?? '', 'attr') ?>"
                                            data-branch-id="<?= (int) ($editRow->branch_id ?? 0) ?>"
                                            data-owner-user-id="<?= (int) ($editRow->owner_user_id ?? 0) ?>"
                                        >Edit</button>
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

<?php if ($canCreateEnquiry): ?>
    <div class="action-modal" id="create-enquiry-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog action-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="create-enquiry-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="create-enquiry-modal-title">Add enquiry</h3>
                    <p>Capture a new lead without leaving the enquiry workspace.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form class="form-stack" method="post" action="<?= site_url('enquiries') ?>">
                <?= csrf_field() ?>
                <?php $formEnquiry = null; $showAssignmentSection = false; $useOldInput = true; ?>
                <?= $this->include('enquiries/_form_sections') ?>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Create enquiry</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($rows as $row): ?>
    <?php if (in_array('enquiries.edit', $codes, true) && in_array($row->lifecycle_status, ['new', 'active'], true)): ?>
        <?php $editRow = $editableRowsById[(int) $row->id] ?? $row; ?>
        <div class="action-modal" id="edit-enquiry-modal-<?= (int) $row->id ?>" hidden>
            <div class="action-modal__backdrop" data-modal-close></div>
            <div class="action-modal__dialog action-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="edit-enquiry-modal-title-<?= (int) $row->id ?>">
                <div class="action-modal__header">
                    <div>
                        <h3 id="edit-enquiry-modal-title-<?= (int) $row->id ?>">Edit enquiry</h3>
                        <p>Update the core lead details without leaving the enquiry list.</p>
                    </div>
                    <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
                </div>
                <form class="form-stack" method="post" action="<?= site_url('enquiries/' . $row->id) ?>">
                    <?= csrf_field() ?>
                    <?php
                    $formEnquiry = $editRow;
                    $showAssignmentSection = in_array('enquiries.reassign_in_edit', $codes, true) && in_array($row->lifecycle_status, ['new', 'active'], true);
                    $useOldInput = false;
                    ?>
                    <?= $this->include('enquiries/_form_sections') ?>
                    <div class="form-actions">
                        <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                        <button class="shell-button shell-button--primary" type="submit">Save enquiry</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
<script>
(() => {
    const fillField = (scope, name, value) => {
        const field = scope.querySelector(`[name="${name}"]`);
        if (!field) {
            return;
        }

        if (field.tagName === 'SELECT' || field.tagName === 'INPUT' || field.tagName === 'TEXTAREA') {
            field.value = value ?? '';
        }
    };

    document.querySelectorAll('[data-edit-enquiry]').forEach((button) => {
        button.addEventListener('click', () => {
            const enquiryId = button.getAttribute('data-enquiry-id');
            const modal = enquiryId ? document.getElementById(`edit-enquiry-modal-${enquiryId}`) : null;
            if (!modal) {
                return;
            }

            fillField(modal, 'student_name', button.getAttribute('data-student-name'));
            fillField(modal, 'mobile', button.getAttribute('data-mobile'));
            fillField(modal, 'email', button.getAttribute('data-email'));
            fillField(modal, 'whatsapp_number', button.getAttribute('data-whatsapp-number'));
            fillField(modal, 'source_id', button.getAttribute('data-source-id'));
            fillField(modal, 'primary_course_id', button.getAttribute('data-course-id'));
            fillField(modal, 'college_id', button.getAttribute('data-college-id'));
            fillField(modal, 'qualification_id', button.getAttribute('data-qualification-id'));
            fillField(modal, 'city', button.getAttribute('data-city'));
            fillField(modal, 'next_followup_at', button.getAttribute('data-next-followup-at'));
            fillField(modal, 'notes', button.getAttribute('data-notes'));
            fillField(modal, 'branch_id', button.getAttribute('data-branch-id'));
            fillField(modal, 'owner_user_id', button.getAttribute('data-owner-user-id'));
        });
    });

    const syncBranchUsers = (scope) => {
        const branchSelects = scope.querySelectorAll('[data-branch-select]');
        branchSelects.forEach((branchSelect) => {
            const targetId = branchSelect.getAttribute('data-user-target');
            const userSelect = targetId ? document.getElementById(targetId) : null;
            if (!userSelect) {
                return;
            }

            const updateOptions = () => {
                const selectedBranch = branchSelect.value;
                const selectedUser = userSelect.getAttribute('data-selected-user') || userSelect.value;
                let hasVisibleSelectedUser = false;
                const firstOption = userSelect.options[0] || null;

                userSelect.disabled = selectedBranch === '';
                if (firstOption) {
                    firstOption.textContent = selectedBranch === '' ? 'Choose branch first' : 'Keep current owner';
                }

                Array.from(userSelect.options).forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        return;
                    }

                    const branchIds = (option.dataset.branchIds || '').split(',').filter(Boolean);
                    const visible = selectedBranch === '' || branchIds.includes(selectedBranch);
                    option.hidden = !visible;
                    if (!visible && option.selected) {
                        option.selected = false;
                    }
                    if (visible && option.value === selectedUser) {
                        hasVisibleSelectedUser = true;
                    }
                });

                if (selectedBranch !== '' && hasVisibleSelectedUser) {
                    userSelect.value = selectedUser;
                } else if (selectedBranch !== '' && userSelect.selectedIndex > 0 && userSelect.options[userSelect.selectedIndex].hidden) {
                    userSelect.value = '';
                }
            };

            branchSelect.addEventListener('change', updateOptions);
            updateOptions();
        });
    };

    syncBranchUsers(document);
})();
</script>
<?= $this->endSection() ?>
