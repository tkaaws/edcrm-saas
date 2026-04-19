<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$currentQueue = $currentQueue ?? 'admissions';
$queues = [
    'admissions' => 'Admissions',
    'pending_fees' => 'Pending Fees',
    'today_followup' => 'Today Follow-up',
    'missed_followup' => 'Missed Follow-up',
    'batch_pending' => 'Batch Pending',
    'on_hold' => 'On Hold',
    'cancelled' => 'Cancelled',
];
?>
<section class="module-page">
    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Work admissions, fee balances, and batch readiness from one compact workspace.</p>
        </div>
        <?php if ($canCreateAdmission): ?>
            <a class="shell-button shell-button--primary" href="<?= site_url('admissions/create') ?>">Create admission</a>
        <?php endif; ?>
    </div>

    <nav class="queue-nav" aria-label="Admission navigation">
        <div class="queue-nav__group queue-nav__group--wrap">
            <?php foreach ($queues as $queueCode => $queueLabel): ?>
                <a class="queue-nav__link <?= $currentQueue === $queueCode ? 'queue-nav__link--active' : '' ?>" href="<?= site_url('admissions?queue=' . $queueCode) ?>">
                    <?= esc($queueLabel) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Branch</th>
                        <th>Assigned to</th>
                        <th>Status</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Batch</th>
                        <th>Admission date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="10" class="empty-state">No admissions are available in this queue yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td data-label="Student">
                                <div class="entity-cell">
                                    <strong><a href="<?= site_url('admissions/' . $row->id) ?>"><?= esc($row->student_name) ?></a></strong>
                                    <span><?= esc($row->admission_number) ?></span>
                                </div>
                            </td>
                            <td data-label="Mobile"><?= esc($row->mobile) ?></td>
                            <td data-label="Course"><?= esc($row->course_display) ?></td>
                            <td data-label="Branch"><?= esc($row->branch_display) ?></td>
                            <td data-label="Assigned to"><?= esc($row->assigned_user_display) ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?= $row->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= esc(ucfirst(str_replace('_', ' ', $row->status))) ?>
                                </span>
                            </td>
                            <td data-label="Paid"><?= esc(number_format((float) $row->paid_amount, 2)) ?></td>
                            <td data-label="Balance"><?= esc(number_format((float) $row->balance_amount, 2)) ?></td>
                            <td data-label="Batch"><?= esc($row->batch_pending ? 'Pending' : 'Assigned') ?></td>
                            <td data-label="Admission date"><?= esc($row->admission_date ? date('d M Y', strtotime($row->admission_date)) : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= $this->include('shared/pagination') ?>
</section>
<?= $this->endSection() ?>
