<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$activeTab = $activeTab ?? 'overview';
$canCollectPayment = $canCollectPayment ?? false;
$canManageAdmission = $canManageAdmission ?? false;
$canManageBatch = $canManageBatch ?? false;
$canManageFollowups = $canManageFollowups ?? false;
$canDeleteFollowups = $canDeleteFollowups ?? false;
$canViewHistory = $canViewHistory ?? false;
$canCancelAdmission = $canCancelAdmission ?? false;
$statusLabel = ucfirst(str_replace('_', ' ', (string) $admission->status));
$statusBadgeClass = $admission->status === 'active' ? 'status-badge--good' : 'status-badge--neutral';
$tabLabels = [
    'overview' => 'Overview',
    'payments' => 'Payments',
    'installments' => 'Installments',
    'batch' => 'Batch',
    'followups' => 'Follow-ups',
    'history' => 'History',
];
if (! $canViewHistory) {
    unset($tabLabels['history']);
    if ($activeTab === 'history') {
        $activeTab = 'overview';
    }
}
$formatDateTime = static fn(?string $value): string => $value ? date('d M Y h:i A', strtotime($value)) : '-';
$formatDate = static fn(?string $value): string => $value ? date('d M Y', strtotime($value)) : '-';
?>
<section class="module-page">
    <?= $this->include('admissions/_subnav') ?>

    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Work student identity, payments, batch movement, and recovery from one admissions desk.</p>
        </div>
        <div class="table-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('admissions') ?>">Back to admissions</a>
        </div>
    </div>

    <div class="enquiry-workspace">
        <aside class="enquiry-rail">
            <section class="detail-card enquiry-profile-card">
                <div class="enquiry-profile-card__header">
                    <h3><?= esc($admission->student_name) ?></h3>
                    <span class="status-badge <?= esc($statusBadgeClass) ?>"><?= esc($statusLabel) ?></span>
                </div>

                <div class="enquiry-contact-list">
                    <div class="enquiry-contact-list__item">
                        <span>Admission number</span>
                        <strong><?= esc($admission->admission_number) ?></strong>
                    </div>
                    <div class="enquiry-contact-list__item">
                        <span>Mobile</span>
                        <strong><?= esc($admission->mobile) ?></strong>
                    </div>
                    <div class="enquiry-contact-list__item">
                        <span>Email</span>
                        <strong><?= esc($admission->email ?: '-') ?></strong>
                    </div>
                    <div class="enquiry-contact-list__item">
                        <span>WhatsApp</span>
                        <strong><?= esc($admission->whatsapp_number ?: '-') ?></strong>
                    </div>
                </div>

                <div class="enquiry-quick-facts">
                    <div><span>Course</span><strong><?= esc($admission->course_display) ?></strong></div>
                    <div><span>College</span><strong><?= esc($admission->college_name ?: '-') ?></strong></div>
                    <div><span>Branch</span><strong><?= esc($admission->branch_display) ?></strong></div>
                    <div><span>Assigned to</span><strong><?= esc($admission->assigned_user_display) ?></strong></div>
                </div>

                <?php if (! empty($admission->remarks)): ?>
                    <div class="form-note"><?= esc($admission->remarks) ?></div>
                <?php endif; ?>
            </section>

            <section class="detail-card enquiry-actions-card">
                <div class="form-section-header">
                    <h3 class="module-title module-title--small">Quick actions</h3>
                    <p class="module-subtitle">Open a focused popup only when you need to do something.</p>
                </div>

                <div class="enquiry-action-stack">
                    <?php if ($canCollectPayment): ?>
                        <button class="shell-button shell-button--primary enquiry-action-stack__button" type="button" data-modal-open="admission-payment-modal">Collect payment</button>
                    <?php endif; ?>
                    <?php if ($canManageBatch): ?>
                        <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="button" data-modal-open="admission-batch-modal">
                            <?= esc($currentBatchAssignment ? 'Change batch' : 'Assign batch') ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($canManageFollowups): ?>
                        <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="button" data-modal-open="admission-followup-modal">Add follow-up</button>
                    <?php endif; ?>
                    <?php if ($canManageAdmission && $admission->status === 'active'): ?>
                        <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="button" data-modal-open="admission-hold-modal">Hold admission</button>
                    <?php endif; ?>
                    <?php if ($canCancelAdmission): ?>
                        <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="button" data-modal-open="admission-cancel-modal">Cancel admission</button>
                    <?php endif; ?>
                </div>
            </section>
        </aside>

        <div class="enquiry-main">
            <section class="detail-card enquiry-summary-strip">
                <div class="enquiry-summary-strip__item"><span>Admission date</span><strong><?= esc($formatDateTime($admission->admission_date)) ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Fee plan</span><strong><?= esc($admission->fee_plan_label ?: '-') ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Total fees</span><strong><?= esc(number_format((float) $admission->net_amount, 2)) ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Paid</span><strong><?= esc(number_format((float) $admission->paid_amount, 2)) ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Balance</span><strong><?= esc(number_format((float) $admission->balance_amount, 2)) ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Current batch</span><strong><?= esc($currentBatchAssignment->batch_name ?? '-') ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Next follow-up</span><strong><?= esc($formatDateTime($admission->next_followup_at)) ?></strong></div>
            </section>

            <section class="detail-card enquiry-detail-panel">
                <div class="module-toolbar module-toolbar--panel">
                    <div>
                        <h3 class="module-title module-title--small">Admission activity</h3>
                        <p class="module-subtitle">Move between finance, batch movement, follow-ups, and history without leaving the desk.</p>
                    </div>
                    <?php if ($activeTab === 'payments' && $canCollectPayment): ?>
                        <div class="table-actions">
                            <button class="shell-button shell-button--primary" type="button" data-modal-open="admission-payment-modal">Collect payment</button>
                        </div>
                    <?php elseif ($activeTab === 'batch' && $canManageBatch): ?>
                        <div class="table-actions">
                            <button class="shell-button shell-button--primary" type="button" data-modal-open="admission-batch-modal"><?= esc($currentBatchAssignment ? 'Change batch' : 'Assign batch') ?></button>
                        </div>
                    <?php elseif ($activeTab === 'followups' && $canManageFollowups): ?>
                        <div class="table-actions">
                            <button class="shell-button shell-button--primary" type="button" data-modal-open="admission-followup-modal">Add follow-up</button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="settings-tabs history-tab">
                    <div class="settings-tabs__nav" role="tablist" aria-label="Admission detail tabs">
                        <?php foreach ($tabLabels as $tabKey => $tabLabel): ?>
                            <button class="settings-tabs__button <?= $activeTab === $tabKey ? 'settings-tabs__button--active' : '' ?>" type="button" data-tab-target="admission-<?= esc($tabKey) ?>-panel" data-tab-key="<?= esc($tabKey) ?>" role="tab" aria-selected="<?= $activeTab === $tabKey ? 'true' : 'false' ?>">
                                <?= esc($tabLabel) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="settings-tabs__panel" id="admission-overview-panel" <?= $activeTab === 'overview' ? '' : 'hidden' ?>>
                        <div class="summary-grid">
                            <div class="summary-card"><span>Mode of class</span><strong><?= esc($admission->mode_of_class ? ucfirst($admission->mode_of_class) : '-') ?></strong></div>
                            <div class="summary-card"><span>Created by</span><strong><?= esc($admission->created_by_display) ?></strong></div>
                            <div class="summary-card"><span>College</span><strong><?= esc($admission->college_name ?: '-') ?></strong></div>
                            <div class="summary-card"><span>City</span><strong><?= esc($admission->city ?: '-') ?></strong></div>
                        </div>

                        <div class="table-card table-card--plain">
                            <div class="table-wrap">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Fee head</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($feeItems === []): ?>
                                            <tr>
                                                <td colspan="2" class="empty-state">No fee heads captured for this admission yet.</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($feeItems as $item): ?>
                                            <tr>
                                                <td><?= esc($item->fee_head_name) ?></td>
                                                <td><?= esc(number_format((float) $item->amount, 2)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="settings-tabs__panel" id="admission-payments-panel" <?= $activeTab === 'payments' ? '' : 'hidden' ?>>
                        <?php if ($payments === []): ?>
                            <div class="empty-state">No payments recorded yet.</div>
                        <?php else: ?>
                            <div class="table-card table-card--plain">
                                <div class="table-wrap">
                                    <table class="data-table data-table--cards">
                                        <thead>
                                            <tr>
                                                <th>Receipt</th>
                                                <th>Date</th>
                                                <th>Mode</th>
                                                <th>Amount</th>
                                                <th>Received by</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td data-label="Receipt"><?= esc($payment->receipt_number) ?></td>
                                                    <td data-label="Date"><?= esc($formatDateTime($payment->payment_date)) ?></td>
                                                    <td data-label="Mode"><?= esc(ucwords(str_replace('_', ' ', (string) $payment->payment_mode))) ?></td>
                                                    <td data-label="Amount"><?= esc(number_format((float) $payment->amount, 2)) ?></td>
                                                    <td data-label="Received by"><?= esc(trim((string) ($payment->received_by_name ?? '')) ?: '-') ?></td>
                                                    <td data-label="Remarks"><?= esc($payment->remarks ?: '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="settings-tabs__panel" id="admission-installments-panel" <?= $activeTab === 'installments' ? '' : 'hidden' ?>>
                        <?php if ($installments === []): ?>
                            <div class="empty-state">No installments generated for this admission.</div>
                        <?php else: ?>
                            <div class="table-card table-card--plain">
                                <div class="table-wrap">
                                    <table class="data-table data-table--cards">
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Due date</th>
                                                <th>Due amount</th>
                                                <th>Paid</th>
                                                <th>Balance</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($installments as $installment): ?>
                                                <tr>
                                                    <td data-label="No."><?= esc((string) $installment->installment_number) ?></td>
                                                    <td data-label="Due date"><?= esc($formatDate($installment->due_date)) ?></td>
                                                    <td data-label="Due amount"><?= esc(number_format((float) $installment->due_amount, 2)) ?></td>
                                                    <td data-label="Paid"><?= esc(number_format((float) $installment->paid_amount, 2)) ?></td>
                                                    <td data-label="Balance"><?= esc(number_format((float) $installment->balance_amount, 2)) ?></td>
                                                    <td data-label="Status">
                                                        <span class="status-badge <?= in_array($installment->status, ['paid'], true) ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                                            <?= esc(ucfirst($installment->status)) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="settings-tabs__panel" id="admission-batch-panel" <?= $activeTab === 'batch' ? '' : 'hidden' ?>>
                        <?php if (! $hasBatchTable): ?>
                            <div class="empty-state">Batch setup is not available on this server yet.</div>
                        <?php else: ?>
                            <div class="summary-grid">
                                <div class="summary-card"><span>Current batch</span><strong><?= esc($currentBatchAssignment->batch_name ?? 'Not assigned') ?></strong></div>
                                <div class="summary-card"><span>Batch code</span><strong><?= esc($currentBatchAssignment->batch_code ?? '-') ?></strong></div>
                                <div class="summary-card"><span>Assigned on</span><strong><?= esc($formatDateTime($currentBatchAssignment->assigned_on ?? null)) ?></strong></div>
                                <div class="summary-card"><span>Schedule</span><strong><?= esc(($currentBatchAssignment->starts_on ?? null) ? $formatDate($currentBatchAssignment->starts_on) . ' to ' . $formatDate($currentBatchAssignment->ends_on ?? null) : '-') ?></strong></div>
                            </div>

                            <div class="table-card table-card--plain">
                                <div class="table-wrap">
                                    <table class="data-table data-table--cards">
                                        <thead>
                                            <tr>
                                                <th>Moved on</th>
                                                <th>From batch</th>
                                                <th>To batch</th>
                                                <th>Reason</th>
                                                <th>Moved by</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($batchHistory === []): ?>
                                                <tr>
                                                    <td colspan="5" class="empty-state">No batch movement yet.</td>
                                                </tr>
                                            <?php endif; ?>
                                            <?php foreach ($batchHistory as $event): ?>
                                                <tr>
                                                    <td data-label="Moved on"><?= esc($formatDateTime($event->moved_at)) ?></td>
                                                    <td data-label="From batch"><?= esc($event->from_batch_name ?: '-') ?></td>
                                                    <td data-label="To batch"><?= esc($event->to_batch_name ?: '-') ?></td>
                                                    <td data-label="Reason"><?= esc($event->reason ?: '-') ?></td>
                                                    <td data-label="Moved by"><?= esc(trim((string) ($event->moved_by_name ?? '')) ?: '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="settings-tabs__panel" id="admission-followups-panel" <?= $activeTab === 'followups' ? '' : 'hidden' ?>>
                        <?php if ($followups === []): ?>
                            <div class="empty-state">No admission follow-ups yet.</div>
                        <?php else: ?>
                            <div class="timeline-list">
                                <?php foreach ($followups as $followup): ?>
                                    <article class="timeline-item">
                                        <div class="timeline-item__marker"></div>
                                        <div class="timeline-item__content">
                                            <div class="timeline-item__stamp"><?= esc($formatDateTime($followup->created_at)) ?></div>
                                            <div class="timeline-item__card">
                                                <div class="timeline-item__header">
                                                    <div>
                                                        <h4><?= esc(trim((string) ($followup->created_by_name ?? '')) ?: 'System') ?> added a follow-up</h4>
                                                        <p><?= esc($followup->communication_mode_label ?: 'Admission note') ?></p>
                                                    </div>
                                                    <?php if (! empty($followup->is_system_generated)): ?>
                                                        <span class="status-badge status-badge--neutral">System generated</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="timeline-item__meta">
                                                    <?php if (! empty($followup->followup_status_label)): ?>
                                                        <span><strong>Outcome:</strong> <?= esc($followup->followup_status_label) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (! empty($followup->next_followup_at)): ?>
                                                        <span><strong>Next follow-up:</strong> <?= esc($formatDateTime($followup->next_followup_at)) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (! empty($followup->remarks)): ?>
                                                    <div class="timeline-item__body"><?= esc($followup->remarks) ?></div>
                                                <?php endif; ?>
                                                <?php if ($canManageFollowups || $canDeleteFollowups): ?>
                                                    <div class="table-actions">
                                                        <?php if ($canManageFollowups): ?>
                                                            <button class="shell-button shell-button--ghost shell-button--sm" type="button" data-modal-open="admission-followup-edit-modal-<?= (int) $followup->id ?>">Edit follow-up</button>
                                                        <?php endif; ?>
                                                        <?php if ($canDeleteFollowups): ?>
                                                            <form method="post" action="<?= site_url('admissions/' . $admission->id . '/followups/' . $followup->id . '/delete') ?>" onsubmit="return confirm('Delete this follow-up?');">
                                                                <?= csrf_field() ?>
                                                                <button class="shell-button shell-button--ghost shell-button--sm" type="submit">Delete</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($canViewHistory): ?>
                        <div class="settings-tabs__panel" id="admission-history-panel" <?= $activeTab === 'history' ? '' : 'hidden' ?>>
                            <?php if ($statusHistory === []): ?>
                                <div class="empty-state">No status history yet.</div>
                            <?php else: ?>
                                <div class="timeline-list">
                                    <?php foreach ($statusHistory as $event): ?>
                                        <article class="timeline-item timeline-item--history">
                                            <div class="timeline-item__marker"></div>
                                            <div class="timeline-item__content">
                                                <div class="timeline-item__stamp"><?= esc($formatDateTime($event->created_at)) ?></div>
                                                <div class="timeline-item__card">
                                                    <div class="timeline-item__header">
                                                        <div>
                                                            <h4><?= esc(($event->old_status ?: 'Start') . ' -> ' . $event->new_status) ?></h4>
                                                            <p><?= esc(trim((string) ($event->changed_by_name ?? '')) ?: 'System') ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="timeline-item__body"><?= esc($event->reason ?: $event->remarks ?: 'Status updated') ?></div>
                                                    <?php if (! empty($event->remarks) && $event->remarks !== $event->reason): ?>
                                                        <div class="form-note"><?= esc($event->remarks) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</section>

<?php if ($canCollectPayment): ?>
    <div class="action-modal" id="admission-payment-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admission-payment-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="admission-payment-modal-title">Collect payment</h3>
                    <p>Record a payment and let the system allocate it across the remaining installments.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form class="form-stack" method="post" action="<?= site_url('admissions/' . $admission->id . '/payments') ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="field">
                        <span>Amount</span>
                        <input type="number" step="0.01" min="0.01" max="<?= esc((string) max(0, (float) $admission->balance_amount)) ?>" name="amount" required>
                    </label>
                    <label class="field">
                        <span>Payment date</span>
                        <input type="datetime-local" name="payment_date" value="<?= esc(date('Y-m-d\TH:i')) ?>" required>
                    </label>
                    <label class="field">
                        <span>Payment mode</span>
                        <select name="payment_mode" required>
                            <option value="">Select payment mode</option>
                            <?php foreach ($paymentModeOptions as $value => $label): ?>
                                <option value="<?= esc($value) ?>"><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Reference number</span>
                        <input type="text" name="transaction_reference">
                    </label>
                    <label class="field field--full">
                        <span>Remarks</span>
                        <textarea name="remarks" rows="4"></textarea>
                    </label>
                </div>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Save payment</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($canManageBatch): ?>
    <div class="action-modal" id="admission-batch-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admission-batch-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="admission-batch-modal-title"><?= esc($currentBatchAssignment ? 'Change batch' : 'Assign batch') ?></h3>
                    <p>Keep the student in the correct batch without leaving the admissions desk.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form class="form-stack" method="post" action="<?= site_url('admissions/' . $admission->id . '/batch') ?>">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Batch</span>
                    <select name="batch_id" required>
                        <option value="">Select batch</option>
                        <?php foreach ($batchOptions as $batch): ?>
                            <option value="<?= (int) $batch->id ?>" <?= (int) ($currentBatchAssignment->batch_id ?? 0) === (int) $batch->id ? 'selected' : '' ?>>
                                <?= esc($batch->name . ($batch->code ? ' (' . $batch->code . ')' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Comment</span>
                    <textarea name="remarks" rows="4" placeholder="Why is the batch being assigned or changed?"></textarea>
                </label>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit"><?= esc($currentBatchAssignment ? 'Save batch change' : 'Assign batch') ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($canManageFollowups): ?>
    <div class="action-modal" id="admission-followup-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admission-followup-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="admission-followup-modal-title">Add follow-up</h3>
                    <p>Capture the latest payment or recovery conversation without leaving the desk.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form class="form-stack" method="post" action="<?= site_url('admissions/' . $admission->id . '/followups') ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="field">
                        <span>Communication mode</span>
                        <select name="communication_mode_id">
                            <option value="">Select mode</option>
                            <?php foreach ($communicationModes as $row): ?>
                                <option value="<?= (int) $row->id ?>"><?= esc($row->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Follow-up outcome</span>
                        <select name="followup_status_id">
                            <option value="">Select outcome</option>
                            <?php foreach ($followupStatuses as $row): ?>
                                <option value="<?= (int) $row->id ?>"><?= esc($row->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Next follow-up</span>
                        <input type="datetime-local" name="next_followup_at">
                    </label>
                    <label class="field field--full">
                        <span>Remarks</span>
                        <textarea name="remarks" rows="4" required></textarea>
                    </label>
                </div>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Save follow-up</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($followups as $followup): ?>
        <div class="action-modal" id="admission-followup-edit-modal-<?= (int) $followup->id ?>" hidden>
            <div class="action-modal__backdrop" data-modal-close></div>
            <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admission-followup-edit-modal-title-<?= (int) $followup->id ?>">
                <div class="action-modal__header">
                    <div>
                        <h3 id="admission-followup-edit-modal-title-<?= (int) $followup->id ?>">Edit follow-up</h3>
                        <p>Update the follow-up without leaving the admission desk.</p>
                    </div>
                    <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
                </div>
                <form class="form-stack" method="post" action="<?= site_url('admissions/' . $admission->id . '/followups/' . $followup->id) ?>">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <label class="field">
                            <span>Communication mode</span>
                            <select name="communication_mode_id">
                                <option value="">Select mode</option>
                                <?php foreach ($communicationModes as $row): ?>
                                    <option value="<?= (int) $row->id ?>" <?= (int) ($followup->communication_mode_id ?? 0) === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Follow-up outcome</span>
                            <select name="followup_status_id">
                                <option value="">Select outcome</option>
                                <?php foreach ($followupStatuses as $row): ?>
                                    <option value="<?= (int) $row->id ?>" <?= (int) ($followup->followup_status_id ?? 0) === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Next follow-up</span>
                            <input type="datetime-local" name="next_followup_at" value="<?= esc(! empty($followup->next_followup_at) ? date('Y-m-d\TH:i', strtotime($followup->next_followup_at)) : '') ?>">
                        </label>
                        <label class="field field--full">
                            <span>Remarks</span>
                            <textarea name="remarks" rows="4" required><?= esc($followup->remarks ?? '') ?></textarea>
                        </label>
                    </div>
                    <div class="form-actions">
                        <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                        <button class="shell-button shell-button--primary" type="submit">Save follow-up</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($canManageAdmission && $admission->status === 'active'): ?>
    <div class="action-modal" id="admission-hold-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admission-hold-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="admission-hold-modal-title">Hold admission</h3>
                    <p>Pause this admission only when the student needs a temporary stop.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form class="form-stack" method="post" action="<?= site_url('admissions/' . $admission->id . '/hold') ?>">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Reason</span>
                    <input type="text" name="reason" required>
                </label>
                <label class="field">
                    <span>Remarks</span>
                    <textarea name="remarks" rows="4"></textarea>
                </label>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Hold admission</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($canCancelAdmission): ?>
    <div class="action-modal" id="admission-cancel-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="admission-cancel-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="admission-cancel-modal-title">Cancel admission</h3>
                    <p>Only cancel when the admission should no longer continue.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form class="form-stack" method="post" action="<?= site_url('admissions/' . $admission->id . '/cancel') ?>">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Reason</span>
                    <input type="text" name="reason" required>
                </label>
                <label class="field">
                    <span>Remarks</span>
                    <textarea name="remarks" rows="4"></textarea>
                </label>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Cancel admission</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
(() => {
    const activeTabKey = <?= json_encode($activeTab) ?>;
    const buttons = Array.from(document.querySelectorAll('[data-tab-target]'));
    const panels = Array.from(document.querySelectorAll('.history-tab .settings-tabs__panel'));

    const showTab = (targetId) => {
        buttons.forEach((button) => {
            const isActive = button.getAttribute('data-tab-target') === targetId;
            button.classList.toggle('settings-tabs__button--active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        panels.forEach((panel) => {
            panel.hidden = panel.id !== targetId;
        });
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            showTab(button.getAttribute('data-tab-target'));
        });
    });

    const initialButton = buttons.find((button) => button.getAttribute('data-tab-key') === activeTabKey) || buttons[0];
    if (initialButton) {
        showTab(initialButton.getAttribute('data-tab-target'));
    }
})();
</script>
<?= $this->endSection() ?>
