<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($enquiry->student_name) ?></h2>
            <p class="module-subtitle">Work the enquiry from one place: identity, ownership, follow-ups, and audit history stay connected.</p>
        </div>
        <div class="table-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries') ?>">Back to enquiries</a>
            <?php if ($canEditEnquiry): ?>
                <a class="shell-button shell-button--primary" href="<?= site_url('enquiries/' . $enquiry->id . '/edit') ?>">Edit enquiry</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="enquiry-workspace">
        <aside class="enquiry-rail">
            <section class="detail-card enquiry-profile-card">
                <div class="enquiry-profile-card__header">
                    <h3><?= esc($enquiry->student_name) ?></h3>
                    <span class="status-badge status-badge--good"><?= esc($enquiry->queue_status) ?></span>
                </div>

                <div class="enquiry-contact-list">
                    <div class="enquiry-contact-list__item">
                        <span>Mobile</span>
                        <strong><?= esc($enquiry->mobile_display) ?></strong>
                    </div>
                    <div class="enquiry-contact-list__item">
                        <span>Email</span>
                        <strong><?= esc($enquiry->email ?: '-') ?></strong>
                    </div>
                    <div class="enquiry-contact-list__item">
                        <span>WhatsApp</span>
                        <strong><?= esc($enquiry->whatsapp_display ?: '-') ?></strong>
                    </div>
                </div>

                <div class="enquiry-quick-facts">
                    <div><span>Course</span><strong><?= esc($enquiry->course_display) ?></strong></div>
                    <div><span>Source</span><strong><?= esc($enquiry->source_display) ?></strong></div>
                    <div><span>College</span><strong><?= esc($enquiry->college_name ?: '-') ?></strong></div>
                    <div><span>City</span><strong><?= esc($enquiry->city ?: '-') ?></strong></div>
                </div>

                <?php if (! empty($enquiry->notes)): ?>
                    <div class="form-note"><?= esc($enquiry->notes) ?></div>
                <?php endif; ?>
            </section>

            <section class="detail-card enquiry-actions-card">
                <div class="form-section-header">
                    <h3 class="module-title module-title--small">Quick actions</h3>
                    <p class="module-subtitle">Keep the main operational actions close to the lead profile.</p>
                </div>

                <div class="enquiry-action-stack">
                    <?php if ($canEditEnquiry): ?>
                        <a class="shell-button shell-button--primary enquiry-action-stack__button" href="<?= site_url('enquiries/' . $enquiry->id . '/edit') ?>">Edit enquiry</a>
                    <?php endif; ?>

                    <?php if ($canReopenEnquiry): ?>
                        <form method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/reopen') ?>">
                            <?= csrf_field() ?>
                            <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="submit">Reopen enquiry</button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ($canCloseEnquiry): ?>
                    <form class="form-stack" method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/close') ?>">
                        <?= csrf_field() ?>
                        <label class="field">
                            <span>Close reason</span>
                            <select name="closed_reason_id" required>
                                <option value="">Select reason</option>
                                <?php foreach ($closeReasons as $row): ?>
                                    <option value="<?= (int) $row->id ?>"><?= esc($row->label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Remarks</span>
                            <textarea name="closed_remarks" rows="3" placeholder="Why is this enquiry being closed?"></textarea>
                        </label>
                        <div class="form-actions">
                            <button class="shell-button shell-button--primary" type="submit">Close enquiry</button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($canAssignFromDetail): ?>
                    <form class="form-stack" method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/assign') ?>">
                        <?= csrf_field() ?>
                        <label class="field">
                            <span>Branch</span>
                            <select name="branch_id" required>
                                <option value="">Select branch</option>
                                <?php foreach ($assignableBranches as $branch): ?>
                                    <option value="<?= (int) $branch->id ?>" <?= (int) $enquiry->branch_id === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Assigned to</span>
                            <select name="owner_user_id" required>
                                <option value="">Select user</option>
                                <?php foreach ($assignableUsers as $user): ?>
                                    <option value="<?= (int) $user->id ?>" <?= (int) $enquiry->owner_user_id === (int) $user->id ? 'selected' : '' ?>>
                                        <?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->email) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Assignment reason</span>
                            <textarea name="assignment_reason" rows="3" placeholder="Why is this enquiry being moved?"></textarea>
                        </label>
                        <div class="form-actions">
                            <button class="shell-button shell-button--primary" type="submit"><?= $enquiry->lifecycle_status === 'closed' ? 'Assign closed enquiry' : 'Assign enquiry' ?></button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </aside>

        <div class="enquiry-main">
            <section class="detail-card enquiry-summary-strip">
                <div class="enquiry-summary-strip__item">
                    <span>Assigned to</span>
                    <strong><?= esc($enquiry->owner_display) ?></strong>
                </div>
                <div class="enquiry-summary-strip__item">
                    <span>Assigned on</span>
                    <strong><?= esc($enquiry->assigned_on ? date('d M Y h:i A', strtotime($enquiry->assigned_on)) : '-') ?></strong>
                </div>
                <div class="enquiry-summary-strip__item">
                    <span>Enquiry date</span>
                    <strong><?= esc($enquiry->created_at ? date('d M Y h:i A', strtotime($enquiry->created_at)) : '-') ?></strong>
                </div>
                <div class="enquiry-summary-strip__item">
                    <span>Branch</span>
                    <strong><?= esc($enquiry->branch_display) ?></strong>
                </div>
                <div class="enquiry-summary-strip__item">
                    <span>Next follow-up</span>
                    <strong><?= esc($enquiry->next_followup_at ? date('d M Y h:i A', strtotime($enquiry->next_followup_at)) : '-') ?></strong>
                </div>
                <div class="enquiry-summary-strip__item">
                    <span>Created by</span>
                    <strong><?= esc($enquiry->created_by_display) ?></strong>
                </div>
            </section>

            <section class="detail-card enquiry-detail-panel">
                <div class="enquiry-panel-tabs" role="tablist" aria-label="Enquiry detail panels">
                    <button class="enquiry-panel-tabs__button enquiry-panel-tabs__button--active" type="button" role="tab" aria-selected="true" aria-controls="enquiry-followups-panel" data-panel-target="enquiry-followups-panel">Follow-ups</button>
                    <button class="enquiry-panel-tabs__button" type="button" role="tab" aria-selected="false" aria-controls="enquiry-history-panel" data-panel-target="enquiry-history-panel">History</button>
                </div>

                <div id="enquiry-followups-panel" class="enquiry-panel-tab-content">
                    <?php if ($canAddFollowup): ?>
                        <section class="enquiry-inline-form">
                            <div class="form-section-header">
                                <h3 class="module-title module-title--small">Add follow-up</h3>
                                <p class="module-subtitle">Capture the latest conversation, outcome, and next promised touchpoint.</p>
                            </div>

                            <form class="form-stack" method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/followups') ?>">
                                <?= csrf_field() ?>
                                <div class="form-grid">
                                    <label class="field">
                                        <span>Communication mode</span>
                                        <select name="communication_type_id" required>
                                            <option value="">Select mode</option>
                                            <?php foreach ($communicationModes as $row): ?>
                                                <option value="<?= (int) $row->id ?>" <?= (int) old('communication_type_id') === (int) $row->id ? 'selected' : '' ?>>
                                                    <?= esc($row->label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="field">
                                        <span>Follow-up outcome</span>
                                        <select name="followup_outcome_id" required>
                                            <option value="">Select outcome</option>
                                            <?php foreach ($followupStatuses as $row): ?>
                                                <option value="<?= (int) $row->id ?>" <?= (int) old('followup_outcome_id') === (int) $row->id ? 'selected' : '' ?>>
                                                    <?= esc($row->label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="field">
                                        <span>Next follow-up</span>
                                        <input type="datetime-local" name="next_followup_at" value="<?= esc(old('next_followup_at')) ?>">
                                    </label>
                                    <label class="field field--full">
                                        <span>Remarks</span>
                                        <textarea name="remarks" rows="4" required><?= esc(old('remarks')) ?></textarea>
                                    </label>
                                </div>
                                <div class="form-actions">
                                    <button class="shell-button shell-button--primary" type="submit">Save follow-up</button>
                                </div>
                            </form>
                        </section>
                    <?php endif; ?>

                    <?php if ($canViewFollowups): ?>
                        <?php if ($followupHistory === []): ?>
                            <div class="empty-state">No follow-ups added yet for this enquiry.</div>
                        <?php else: ?>
                            <div class="timeline-list">
                                <?php foreach ($followupHistory as $row): ?>
                                    <article class="timeline-item">
                                        <div class="timeline-item__marker"></div>
                                        <div class="timeline-item__content">
                                            <div class="timeline-item__stamp"><?= esc($row->created_at ? date('d/m/y h:i a', strtotime($row->created_at)) : '-') ?></div>
                                            <div class="timeline-item__card">
                                                <div class="timeline-item__header">
                                                    <div>
                                                        <h4><?= esc(trim($row->created_by_name) ?: 'System') ?> created a follow-up</h4>
                                                        <p><?= esc($row->communication_mode_label ?: '-') ?></p>
                                                    </div>
                                                    <?php if (! empty($row->is_system_generated)): ?>
                                                        <span class="status-badge status-badge--neutral">System generated</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="timeline-item__meta">
                                                    <span><strong>Outcome:</strong> <?= esc($row->followup_outcome_label ?: '-') ?></span>
                                                    <?php if (! empty($row->next_followup_at)): ?>
                                                        <span><strong>Next follow-up:</strong> <?= esc(date('d M Y h:i A', strtotime($row->next_followup_at))) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (! empty($row->remarks)): ?>
                                                    <div class="timeline-item__body"><?= esc($row->remarks) ?></div>
                                                <?php endif; ?>
                                                <?php if ($canEditFollowups || $canDeleteFollowups): ?>
                                                    <div class="table-actions">
                                                        <?php if ($canEditFollowups): ?>
                                                            <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('enquiries/' . $enquiry->id . '/followups/' . $row->id . '/edit') ?>">Edit</a>
                                                        <?php endif; ?>
                                                        <?php if ($canDeleteFollowups): ?>
                                                            <form method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/followups/' . $row->id . '/delete') ?>" onsubmit="return confirm('Delete this follow-up?');">
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
                    <?php endif; ?>
                </div>

                <div id="enquiry-history-panel" class="enquiry-panel-tab-content" hidden>
                    <div class="settings-grid">
                        <section class="form-card form-card--nested">
                            <div class="form-section-header">
                                <h3 class="module-title module-title--small">Assignment history</h3>
                                <p class="module-subtitle">Keep every ownership change visible so the team can see how the lead moved.</p>
                            </div>

                            <?php if ($assignmentHistory === []): ?>
                                <div class="empty-state">No assignment history yet for this enquiry.</div>
                            <?php else: ?>
                                <div class="stack-list">
                                    <?php foreach ($assignmentHistory as $row): ?>
                                        <div class="stack-list__item">
                                            <strong><?= esc(trim(($row->from_user_name ?: 'Unassigned') . ' -> ' . ($row->to_user_name ?: 'Unassigned'))) ?></strong>
                                            <span><?= esc(($row->from_branch_name ?: 'No branch') . ' -> ' . ($row->to_branch_name ?: 'No branch')) ?></span>
                                            <span><?= esc($row->assigned_on ? date('d M Y h:i A', strtotime($row->assigned_on)) : '-') ?> | by <?= esc(trim($row->assigned_by_name) ?: 'System') ?></span>
                                            <?php if (! empty($row->reason)): ?>
                                                <span><?= esc($row->reason) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="form-card form-card--nested">
                            <div class="form-section-header">
                                <h3 class="module-title module-title--small">Status history</h3>
                                <p class="module-subtitle">Track lifecycle changes without mixing them into follow-up remarks.</p>
                            </div>

                            <?php if ($statusHistory === []): ?>
                                <div class="empty-state">No status changes yet for this enquiry.</div>
                            <?php else: ?>
                                <div class="stack-list">
                                    <?php foreach ($statusHistory as $row): ?>
                                        <div class="stack-list__item">
                                            <strong><?= esc(($row->from_status ?: 'Start') . ' -> ' . $row->to_status) ?></strong>
                                            <span><?= esc($row->created_at ? date('d M Y h:i A', strtotime($row->created_at)) : '-') ?> | by <?= esc(trim($row->changed_by_name) ?: 'System') ?></span>
                                            <?php if (! empty($row->reason)): ?>
                                                <span><?= esc($row->reason) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>

<script>
(() => {
    const tabButtons = Array.from(document.querySelectorAll('.enquiry-panel-tabs__button'));
    const panels = Array.from(document.querySelectorAll('.enquiry-panel-tab-content'));

    if (tabButtons.length === 0 || panels.length === 0) {
        return;
    }

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.getAttribute('data-panel-target');

            tabButtons.forEach((item) => {
                item.classList.remove('enquiry-panel-tabs__button--active');
                item.setAttribute('aria-selected', 'false');
            });

            panels.forEach((panel) => {
                panel.hidden = panel.id !== target;
            });

            button.classList.add('enquiry-panel-tabs__button--active');
            button.setAttribute('aria-selected', 'true');
        });
    });
})();
</script>
<?= $this->endSection() ?>
