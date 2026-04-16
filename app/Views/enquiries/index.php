<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$codes = session()->get('user_privilege_codes') ?? [];
$canCreateEnquiry = in_array('enquiries.create', $codes, true);
$canBulkAssign = in_array('enquiries.bulk_assign', $codes, true);
$columnCount = match ($currentTab ?? 'enquiries') {
    'today', 'fresh' => 11,
    'missed' => 12,
    'expired', 'closed' => 13,
    default => 12,
};
?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($pageTitle ?? 'Enquiries') ?></h2>
            <p class="module-subtitle">Track active leads, time-sensitive follow-ups, and recovery queues without clutter.</p>
        </div>
        <div class="table-actions">
            <?php if ($canBulkAssign): ?>
                <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries/bulk-assign') ?>">Bulk assign</a>
            <?php endif; ?>
            <?php if ($canCreateEnquiry): ?>
                <a class="shell-button shell-button--primary" href="<?= site_url('enquiries/create') ?>">Add enquiry</a>
            <?php endif; ?>
        </div>
    </div>

    <section class="settings-tabs" aria-label="Enquiry navigation">
        <div class="settings-tabs__nav">
            <a class="settings-tabs__button <?= $currentTab === 'enquiries' ? 'settings-tabs__button--active' : '' ?>" href="<?= site_url('enquiries?tab=enquiries') ?>">Enquiries</a>
            <a class="settings-tabs__button <?= $currentTab === 'today' ? 'settings-tabs__button--active' : '' ?>" href="<?= site_url('enquiries?tab=today') ?>">Today</a>
            <a class="settings-tabs__button <?= $currentTab === 'missed' ? 'settings-tabs__button--active' : '' ?>" href="<?= site_url('enquiries?tab=missed') ?>">Missed</a>
            <a class="settings-tabs__button <?= $currentTab === 'fresh' ? 'settings-tabs__button--active' : '' ?>" href="<?= site_url('enquiries?tab=fresh') ?>">Fresh</a>
            <a class="settings-tabs__button <?= $currentTab === 'expired' ? 'settings-tabs__button--active' : '' ?>" href="<?= site_url('enquiries/expired') ?>">Expired Enquiries</a>
            <a class="settings-tabs__button <?= $currentTab === 'closed' ? 'settings-tabs__button--active' : '' ?>" href="<?= site_url('enquiries/closed') ?>">Closed Enquiries</a>
            <?php if ($canBulkAssign): ?>
                <a class="settings-tabs__button" href="<?= site_url('enquiries/bulk-assign') ?>">Bulk Assign</a>
            <?php endif; ?>
        </div>
    </section>

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
                                    <span class="status-badge status-badge--good"><?= esc($row->queue_status) ?></span>
                                </td>
                            <?php endif; ?>
                            <td data-label="Created on"><?= esc($row->created_at ? date('d M Y', strtotime($row->created_at)) : '-') ?></td>
                            <td data-label="Modified on"><?= esc($row->updated_at ? date('d M Y', strtotime($row->updated_at)) : '-') ?></td>
                            <td data-label="Created by"><?= esc($row->created_by_display) ?></td>
                            <td data-label="Modified by"><?= esc($row->updated_by_display) ?></td>
                            <td class="data-table__actions" data-label="Actions">
                                <div class="table-actions">
                                    <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('enquiries/' . $row->id) ?>">Open</a>
                                    <?php if (in_array('enquiries.edit', $codes, true) && in_array($row->lifecycle_status, ['new', 'active'], true)): ?>
                                        <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('enquiries/' . $row->id . '/edit') ?>">Edit</a>
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
