<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Work the enquiry from one place: identity, ownership, and follow-ups stay connected.</p>
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
                    <span class="status-badge <?= $enquiry->display_status === 'Active' ? 'status-badge--good' : 'status-badge--neutral' ?>"><?= esc($enquiry->display_status) ?></span>
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
                        <button class="shell-button shell-button--ghost enquiry-action-stack__button" type="button" data-modal-open="assign-modal"><?= $enquiry->lifecycle_status === 'closed' ? 'Assign closed enquiry' : 'Assign enquiry' ?></button>
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
                <div class="enquiry-summary-strip__item"><span>Assigned to</span><strong><?= esc($enquiry->owner_display) ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Assigned on</span><strong><?= esc($enquiry->assigned_on ? date('d M Y h:i A', strtotime($enquiry->assigned_on)) : '-') ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Enquiry date</span><strong><?= esc($enquiry->created_at ? date('d M Y h:i A', strtotime($enquiry->created_at)) : '-') ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Branch</span><strong><?= esc($enquiry->branch_display) ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Next follow-up</span><strong><?= esc($enquiry->next_followup_at ? date('d M Y h:i A', strtotime($enquiry->next_followup_at)) : '-') ?></strong></div>
                <div class="enquiry-summary-strip__item"><span>Created by</span><strong><?= esc($enquiry->created_by_display) ?></strong></div>
            </section>

            <section class="detail-card enquiry-detail-panel">
                <div class="module-toolbar module-toolbar--panel">
                    <div>
                        <h3 class="module-title module-title--small">Lead activity</h3>
                        <p class="module-subtitle">Follow-ups stay first. History stays separate when you need to inspect it.</p>
                    </div>
                    <?php if ($canAddFollowup): ?>
                        <div class="table-actions">
                            <button class="shell-button shell-button--primary" type="button" data-modal-open="followup-modal">Add follow-up</button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="settings-tabs history-tab">
                    <div class="settings-tabs__nav" role="tablist" aria-label="Enquiry activity tabs">
                        <button class="settings-tabs__button settings-tabs__button--active" type="button" data-tab-target="followups-panel" role="tab" aria-selected="true">Follow-ups</button>
                        <?php if ($canViewHistory): ?>
                            <button class="settings-tabs__button" type="button" data-tab-target="history-panel" role="tab" aria-selected="false">History</button>
                        <?php endif; ?>
                    </div>

                    <div class="settings-tabs__panel" id="followups-panel">
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

                    <?php if ($canViewHistory): ?>
                        <div class="settings-tabs__panel" id="history-panel" hidden>
                            <?php if ($historyEvents === []): ?>
                                <div class="empty-state">No history events for this enquiry yet.</div>
                            <?php else: ?>
                                <div class="timeline-list">
                                    <?php foreach ($historyEvents as $event): ?>
                                        <article class="timeline-item timeline-item--history">
                                            <div class="timeline-item__marker"></div>
                                            <div class="timeline-item__content">
                                                <div class="timeline-item__stamp"><?= esc($event->created_at ? date('d/m/y h:i a', strtotime($event->created_at)) : '-') ?></div>
                                                <div class="timeline-item__card">
                                                    <div class="timeline-item__header">
                                                        <div>
                                                            <h4><?= esc($event->summary ?: 'Enquiry updated') ?></h4>
                                                            <p><?= esc($event->actor_display ?? 'System') ?></p>
                                                        </div>
                                                    </div>
                                                    <?php if (! empty($event->changes)): ?>
                                                        <div class="history-change-list">
                                                            <?php foreach ($event->changes as $change): ?>
                                                                <div class="history-change-list__item">
                                                                    <strong><?= esc($change->field) ?></strong>
                                                                    <span><?= esc($change->old_value) ?></span>
                                                                    <em>&rarr;</em>
                                                                    <span><?= esc($change->new_value) ?></span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
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
                        <input type="text" name="student_name" value="<?= esc($enquiry->student_name ?? '') ?>" required>
                    </label>
                    <label class="field">
                        <span>Enquiry source</span>
                        <select name="source_id" required>
                            <option value="">Select source</option>
                            <?php $selectedSourceId = (int) ($enquiry->source_id ?? 0); ?>
                            <?php foreach ($sources as $row): ?>
                                <option value="<?= (int) $row->id ?>" <?= $selectedSourceId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Course</span>
                        <select name="primary_course_id" required>
                            <option value="">Select course</option>
                            <?php $selectedCourseId = (int) ($enquiry->primary_course_id ?? 0); ?>
                            <?php foreach ($courses as $row): ?>
                                <option value="<?= (int) $row->id ?>" <?= $selectedCourseId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Lead stage</span>
                        <select name="qualification_id">
                            <option value="">Select stage</option>
                            <?php $selectedQualificationId = (int) ($enquiry->qualification_id ?? 0); ?>
                            <?php foreach ($qualifications as $row): ?>
                                <option value="<?= (int) $row->id ?>" <?= $selectedQualificationId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Next follow-up</span>
                        <input type="datetime-local" name="next_followup_at" value="<?= esc($enquiry->next_followup_at ? date('Y-m-d\TH:i', strtotime($enquiry->next_followup_at)) : '') ?>">
                    </label>
                    <label class="field field--full">
                        <span>Remarks</span>
                        <textarea name="notes" rows="4"><?= esc($enquiry->notes ?? '') ?></textarea>
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
                        <input type="text" name="mobile" value="<?= esc($enquiry->mobile ?? '') ?>" required>
                    </label>
                    <label class="field">
                        <span>WhatsApp number</span>
                        <input type="text" name="whatsapp_number" value="<?= esc($enquiry->whatsapp_number ?? '') ?>">
                    </label>
                    <label class="field field--full">
                        <span>Email</span>
                        <input type="email" name="email" value="<?= esc($enquiry->email ?? '') ?>">
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
                            <?php $selectedCollegeId = (int) ($enquiry->college_id ?? 0); ?>
                            <?php foreach ($colleges as $row): ?>
                                <option value="<?= (int) $row->id ?>" <?= $selectedCollegeId === (int) $row->id ? 'selected' : '' ?>>
                                    <?= esc($row->name . ' - ' . $row->city_name . ', ' . $row->state_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>City</span>
                        <input type="text" name="city" value="<?= esc($enquiry->city ?? '') ?>">
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
                    <select name="branch_id" id="detail-branch-select" required>
                        <option value="">Select branch</option>
                        <?php foreach ($assignableBranches as $branch): ?>
                            <option value="<?= (int) $branch->id ?>" <?= (int) $enquiry->branch_id === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Assigned to</span>
                    <select name="owner_user_id" id="detail-owner-user-select" required>
                        <option value="">Choose branch first</option>
                        <?php foreach ($assignableUsers as $user): ?>
                            <option value="<?= (int) $user->id ?>" data-branch-ids="<?= esc(implode(',', $assignableUsersByBranch[(int) $user->id] ?? [])) ?>" <?= (int) $enquiry->owner_user_id === (int) $user->id ? 'selected' : '' ?>>
                                <?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->email) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Comment</span>
                    <textarea name="assignment_comment" rows="4" placeholder="Add a quick note. This will be saved as a system follow-up."></textarea>
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
    document.querySelectorAll('[data-tab-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-tab-target');
            document.querySelectorAll('[data-tab-target]').forEach((item) => {
                item.classList.toggle('settings-tabs__button--active', item === button);
                item.setAttribute('aria-selected', item === button ? 'true' : 'false');
            });
            document.querySelectorAll('.history-tab .settings-tabs__panel').forEach((panel) => {
                panel.hidden = panel.id !== targetId;
            });
        });
    });

    const branchSelect = document.getElementById('detail-branch-select');
    const userSelect = document.getElementById('detail-owner-user-select');
    if (branchSelect && userSelect) {
        const syncUsers = () => {
            const selectedBranch = branchSelect.value;
            const firstOption = userSelect.options[0] || null;
            userSelect.disabled = selectedBranch === '';
            if (firstOption) {
                firstOption.textContent = selectedBranch === '' ? 'Choose branch first' : 'Select user';
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
            });
        };

        branchSelect.addEventListener('change', syncUsers);
        syncUsers();
    }
})();
</script>
<?= $this->endSection() ?>
