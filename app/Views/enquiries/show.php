<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($enquiry->student_name) ?></h2>
            <p class="module-subtitle">Work the enquiry from one place: identity, ownership, follow-ups, and history stay connected.</p>
        </div>
        <div class="table-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries') ?>">Back to enquiries</a>
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
                    <p class="module-subtitle">Open a focused popup only when you need to take action.</p>
                </div>

                <div class="enquiry-action-stack">
                    <?php if ($canEditEnquiry): ?>
                        <button class="shell-button shell-button--primary enquiry-action-stack__button" type="button" data-modal-open="edit-enquiry-modal">Edit enquiry</button>
                    <?php endif; ?>

                    <?php if ($canEditContactInfo): ?>
                        <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="button" data-modal-open="contact-info-modal">Change contact info</button>
                    <?php endif; ?>

                    <?php if ($canEditCollegeInfo): ?>
                        <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="button" data-modal-open="college-info-modal">Update college info</button>
                    <?php endif; ?>

                    <?php if ($canCloseEnquiry): ?>
                        <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="button" data-modal-open="close-modal">Close enquiry</button>
                    <?php endif; ?>

                    <?php if ($canAssignFromDetail): ?>
                        <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="button" data-modal-open="assign-modal">
                            <?= $enquiry->lifecycle_status === 'closed' ? 'Assign closed enquiry' : 'Assign enquiry' ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($canReopenEnquiry): ?>
                        <form method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/reopen') ?>">
                            <?= csrf_field() ?>
                            <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="submit">Reopen enquiry</button>
                        </form>
                    <?php endif; ?>
                </div>
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
                    <div class="module-toolbar module-toolbar--panel">
                        <div>
                            <h3 class="module-title module-title--small">Follow-up timeline</h3>
                            <p class="module-subtitle">The first note from enquiry capture appears here, and every next touch stays in the same timeline.</p>
                        </div>
                        <?php if ($canAddFollowup): ?>
                            <div class="table-actions">
                                <button class="shell-button shell-button--primary" type="button" data-modal-open="followup-modal">Add follow-up</button>
                            </div>
                        <?php endif; ?>
                    </div>

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
                                                        <p><?= esc($row->communication_mode_label ?: 'Initial enquiry note') ?></p>
                                                    </div>
                                                    <?php if (! empty($row->is_system_generated)): ?>
                                                        <span class="status-badge status-badge--neutral">System generated</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="timeline-item__meta">
                                                    <?php if (! empty($row->followup_outcome_label)): ?>
                                                        <span><strong>Outcome:</strong> <?= esc($row->followup_outcome_label) ?></span>
                                                    <?php endif; ?>
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
                                                            <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('enquiries/' . $enquiry->id . '/followups/' . $row->id . '/edit') ?>">Edit follow-up</a>
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
                    <div class="history-feed">
                        <?php foreach ($assignmentHistory as $row): ?>
                            <article class="history-feed__item">
                                <h4>Assignment updated</h4>
                                <p><?= esc(trim(($row->from_user_name ?: 'Unassigned') . ' -> ' . ($row->to_user_name ?: 'Unassigned'))) ?></p>
                                <span><?= esc(($row->from_branch_name ?: 'No branch') . ' -> ' . ($row->to_branch_name ?: 'No branch')) ?> | <?= esc($row->assigned_on ? date('d M Y h:i A', strtotime($row->assigned_on)) : '-') ?> | by <?= esc(trim($row->assigned_by_name) ?: 'System') ?></span>
                                <?php if (! empty($row->reason)): ?>
                                    <small><?= esc($row->reason) ?></small>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>

                        <?php foreach ($statusHistory as $row): ?>
                            <article class="history-feed__item">
                                <h4>Status changed</h4>
                                <p><?= esc(($row->from_status ?: 'Start') . ' -> ' . $row->to_status) ?></p>
                                <span><?= esc($row->created_at ? date('d M Y h:i A', strtotime($row->created_at)) : '-') ?> | by <?= esc(trim($row->changed_by_name) ?: 'System') ?></span>
                                <?php if (! empty($row->reason)): ?>
                                    <small><?= esc($row->reason) ?></small>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>

                        <?php if ($assignmentHistory === [] && $statusHistory === []): ?>
                            <div class="empty-state">No history entries yet for this enquiry.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>

<?php if ($canEditEnquiry): ?>
    <div class="action-modal" id="edit-enquiry-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog action-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="edit-enquiry-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="edit-enquiry-modal-title">Edit enquiry</h3>
                    <p>Update core enquiry details without leaving the working desk.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>

            <form class="form-stack" method="post" action="<?= site_url('enquiries/' . $enquiry->id) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="branch_id" value="<?= esc((string) ($enquiry->branch_id ?? '')) ?>">
                <input type="hidden" name="owner_user_id" value="<?= esc((string) ($enquiry->owner_user_id ?? '')) ?>">
                <div class="form-grid">
                    <label class="field">
                        <span>Student name</span>
                        <input type="text" name="student_name" value="<?= esc(old('student_name', $enquiry->student_name ?? '')) ?>" required>
                    </label>
                    <label class="field">
                        <span>Enquiry source</span>
                        <select name="source_id" required>
                            <option value="">Select source</option>
                            <?php $selectedSourceId = (int) old('source_id', $enquiry->source_id ?? 0); ?>
                            <?php foreach ($sources as $row): ?>
                                <option value="<?= (int) $row->id ?>" <?= $selectedSourceId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Course</span>
                        <select name="primary_course_id" required>
                            <option value="">Select course</option>
                            <?php $selectedCourseId = (int) old('primary_course_id', $enquiry->primary_course_id ?? 0); ?>
                            <?php foreach ($courses as $row): ?>
                                <option value="<?= (int) $row->id ?>" <?= $selectedCourseId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Lead stage</span>
                        <select name="qualification_id">
                            <option value="">Select stage</option>
                            <?php $selectedQualificationId = (int) old('qualification_id', $enquiry->qualification_id ?? 0); ?>
                            <?php foreach ($qualifications as $row): ?>
                                <option value="<?= (int) $row->id ?>" <?= $selectedQualificationId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>College</span>
                        <select name="college_id" required>
                            <option value="">Select college</option>
                            <?php $selectedCollegeId = (int) old('college_id', $enquiry->college_id ?? 0); ?>
                            <?php foreach ($colleges as $row): ?>
                                <option value="<?= (int) $row->id ?>" <?= $selectedCollegeId === (int) $row->id ? 'selected' : '' ?>>
                                    <?= esc($row->name . ' - ' . $row->city_name . ', ' . $row->state_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>City</span>
                        <input type="text" name="city" value="<?= esc(old('city', $enquiry->city ?? '')) ?>">
                    </label>
                    <label class="field">
                        <span>Next follow-up</span>
                        <input type="datetime-local" name="next_followup_at" value="<?= esc(old('next_followup_at', $enquiry->next_followup_at ? date('Y-m-d\TH:i', strtotime($enquiry->next_followup_at)) : '')) ?>">
                    </label>
                    <label class="field field--full">
                        <span>Remarks</span>
                        <textarea name="notes" rows="4"><?= esc(old('notes', $enquiry->notes ?? '')) ?></textarea>
                    </label>
                </div>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Save enquiry</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($canEditContactInfo): ?>
    <div class="action-modal" id="contact-info-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="contact-info-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="contact-info-modal-title">Change contact info</h3>
                    <p>Update the student’s contact details without opening the full enquiry form.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>

            <form class="form-stack" method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/contact') ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="field">
                        <span>Mobile</span>
                        <input type="text" name="mobile" value="<?= esc(old('mobile', $enquiry->mobile ?? '')) ?>" required>
                    </label>
                    <label class="field">
                        <span>WhatsApp number</span>
                        <input type="text" name="whatsapp_number" value="<?= esc(old('whatsapp_number', $enquiry->whatsapp_number ?? '')) ?>">
                    </label>
                    <label class="field field--full">
                        <span>Email</span>
                        <input type="email" name="email" value="<?= esc(old('email', $enquiry->email ?? '')) ?>">
                    </label>
                </div>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Save contact info</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($canEditCollegeInfo): ?>
    <div class="action-modal" id="college-info-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="college-info-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="college-info-modal-title">Update college info</h3>
                    <p>Keep college and city in sync without exposing the whole enquiry form.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>

            <form class="form-stack" method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/college') ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="field field--full">
                        <span>College</span>
                        <select name="college_id" required>
                            <option value="">Select college</option>
                            <?php $selectedCollegeId = (int) old('college_id', $enquiry->college_id ?? 0); ?>
                            <?php foreach ($colleges as $row): ?>
                                <option value="<?= (int) $row->id ?>" <?= $selectedCollegeId === (int) $row->id ? 'selected' : '' ?>>
                                    <?= esc($row->name . ' - ' . $row->city_name . ', ' . $row->state_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>City</span>
                        <input type="text" name="city" value="<?= esc(old('city', $enquiry->city ?? '')) ?>">
                    </label>
                </div>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Save college info</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($canAddFollowup): ?>
    <div class="action-modal" id="followup-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="followup-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="followup-modal-title">Add follow-up</h3>
                    <p>Capture the latest conversation without leaving the enquiry view.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
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
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Save follow-up</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($canCloseEnquiry): ?>
    <div class="action-modal" id="close-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="close-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="close-modal-title">Close enquiry</h3>
                    <p>Ask for the closure reason only when the user chooses to close the lead.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>

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
                    <textarea name="closed_remarks" rows="4" placeholder="Why is this enquiry being closed?"></textarea>
                </label>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Close enquiry</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($canAssignFromDetail): ?>
    <div class="action-modal" id="assign-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="assign-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="assign-modal-title"><?= esc($enquiry->lifecycle_status === 'closed' ? 'Assign closed enquiry' : 'Assign enquiry') ?></h3>
                    <p>Choose branch and owner only when reassignment is actually needed.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>

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
                    <textarea name="assignment_reason" rows="4" placeholder="Why is this enquiry being moved?"></textarea>
                </label>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit"><?= esc($enquiry->lifecycle_status === 'closed' ? 'Assign closed enquiry' : 'Assign enquiry') ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
(() => {
    const tabButtons = Array.from(document.querySelectorAll('.enquiry-panel-tabs__button'));
    const panels = Array.from(document.querySelectorAll('.enquiry-panel-tab-content'));

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
