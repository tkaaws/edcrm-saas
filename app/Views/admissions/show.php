<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?= $this->include('admissions/_subnav') ?>

    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Keep student identity, payments, installments, and batch readiness connected in one admissions desk.</p>
        </div>
        <div class="table-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('admissions') ?>">Back to admissions</a>
        </div>
    </div>

    <div class="detail-grid">
        <section class="detail-card">
            <div class="enquiry-profile-card__header">
                <h3><?= esc($admission->student_name) ?></h3>
                <span class="status-badge <?= $admission->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                    <?= esc(ucfirst(str_replace('_', ' ', $admission->status))) ?>
                </span>
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
        </section>

        <section class="detail-card">
            <div class="summary-grid">
                <div class="summary-card">
                    <span>Admission date</span>
                    <strong><?= esc($admission->admission_date ? date('d M Y h:i A', strtotime($admission->admission_date)) : '-') ?></strong>
                </div>
                <div class="summary-card">
                    <span>Total fees</span>
                    <strong><?= esc(number_format((float) $admission->net_amount, 2)) ?></strong>
                </div>
                <div class="summary-card">
                    <span>Fee plan</span>
                    <strong><?= esc($admission->fee_plan_label ?: '-') ?></strong>
                </div>
                <div class="summary-card">
                    <span>Paid</span>
                    <strong><?= esc(number_format((float) $admission->paid_amount, 2)) ?></strong>
                </div>
                <div class="summary-card">
                    <span>Balance</span>
                    <strong><?= esc(number_format((float) $admission->balance_amount, 2)) ?></strong>
                </div>
                <div class="summary-card">
                    <span>Batch</span>
                    <strong><?= esc($admission->batch_pending ? 'Pending assignment' : 'Assigned') ?></strong>
                </div>
                <div class="summary-card">
                    <span>Next follow-up</span>
                    <strong><?= esc($admission->next_followup_at ? date('d M Y h:i A', strtotime($admission->next_followup_at)) : '-') ?></strong>
                </div>
            </div>

            <div class="settings-tabs history-tab">
                <div class="settings-tabs__nav" role="tablist" aria-label="Admission detail tabs">
                    <button class="settings-tabs__button settings-tabs__button--active" type="button" data-tab-target="admission-overview-panel">Overview</button>
                    <button class="settings-tabs__button" type="button" data-tab-target="admission-payments-panel">Payments</button>
                    <button class="settings-tabs__button" type="button" data-tab-target="admission-installments-panel">Installments</button>
                    <button class="settings-tabs__button" type="button" data-tab-target="admission-batch-panel">Batch</button>
                    <button class="settings-tabs__button" type="button" data-tab-target="admission-followups-panel">Follow-ups</button>
                    <button class="settings-tabs__button" type="button" data-tab-target="admission-history-panel">History</button>
                </div>

                <div class="settings-tabs__panel" id="admission-overview-panel">
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

                <div class="settings-tabs__panel" id="admission-payments-panel" hidden>
                    <?php if ($payments === []): ?>
                        <div class="empty-state">No payments recorded yet.</div>
                    <?php else: ?>
                        <div class="table-card table-card--plain">
                            <div class="table-wrap">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Receipt</th>
                                            <th>Date</th>
                                            <th>Mode</th>
                                            <th>Amount</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?= esc($payment->receipt_number) ?></td>
                                                <td><?= esc($payment->payment_date ? date('d M Y h:i A', strtotime($payment->payment_date)) : '-') ?></td>
                                                <td><?= esc(ucwords(str_replace('_', ' ', (string) $payment->payment_mode))) ?></td>
                                                <td><?= esc(number_format((float) $payment->amount, 2)) ?></td>
                                                <td><?= esc($payment->remarks ?: '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="settings-tabs__panel" id="admission-installments-panel" hidden>
                    <?php if ($installments === []): ?>
                        <div class="empty-state">No installments generated for this admission.</div>
                    <?php else: ?>
                        <div class="table-card table-card--plain">
                            <div class="table-wrap">
                                <table class="data-table">
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
                                                <td><?= esc((string) $installment->installment_number) ?></td>
                                                <td><?= esc($installment->due_date ? date('d M Y', strtotime($installment->due_date)) : '-') ?></td>
                                                <td><?= esc(number_format((float) $installment->due_amount, 2)) ?></td>
                                                <td><?= esc(number_format((float) $installment->paid_amount, 2)) ?></td>
                                                <td><?= esc(number_format((float) $installment->balance_amount, 2)) ?></td>
                                                <td><?= esc(ucfirst($installment->status)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="settings-tabs__panel" id="admission-batch-panel" hidden>
                    <div class="empty-state"><?= $admission->batch_pending ? 'Batch assignment is still pending for this student.' : 'Batch assignment details will appear here.' ?></div>
                </div>

                <div class="settings-tabs__panel" id="admission-followups-panel" hidden>
                    <?php if ($followups === []): ?>
                        <div class="empty-state">No admission follow-ups yet.</div>
                    <?php else: ?>
                        <div class="timeline-list">
                            <?php foreach ($followups as $followup): ?>
                                <article class="timeline-item">
                                    <div class="timeline-item__marker"></div>
                                    <div class="timeline-item__content">
                                        <div class="timeline-item__stamp"><?= esc($followup->created_at ? date('d/m/y h:i a', strtotime($followup->created_at)) : '-') ?></div>
                                        <div class="timeline-item__card">
                                            <h4><?= esc($followup->is_system_generated ? 'System follow-up' : 'Follow-up added') ?></h4>
                                            <div class="timeline-item__body"><?= esc($followup->remarks ?: '-') ?></div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="settings-tabs__panel" id="admission-history-panel" hidden>
                    <?php if ($statusHistory === []): ?>
                        <div class="empty-state">No status history yet.</div>
                    <?php else: ?>
                        <div class="timeline-list">
                            <?php foreach ($statusHistory as $event): ?>
                                <article class="timeline-item timeline-item--history">
                                    <div class="timeline-item__marker"></div>
                                    <div class="timeline-item__content">
                                        <div class="timeline-item__stamp"><?= esc($event->created_at ? date('d/m/y h:i a', strtotime($event->created_at)) : '-') ?></div>
                                        <div class="timeline-item__card">
                                            <h4><?= esc(($event->old_status ?: 'Start') . ' -> ' . $event->new_status) ?></h4>
                                            <div class="timeline-item__body"><?= esc($event->reason ?: $event->remarks ?: 'Admission updated') ?></div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</section>
<?= $this->endSection() ?>
