<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($enquiry->student_name) ?></h2>
            <p class="module-subtitle">Open the lead, update the record, and take the next operational action from here.</p>
        </div>
        <div class="table-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries') ?>">Back to enquiries</a>
            <?php if ($canEditEnquiry): ?>
                <a class="shell-button shell-button--primary" href="<?= site_url('enquiries/' . $enquiry->id . '/edit') ?>">Edit enquiry</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="settings-grid">
        <section class="form-card">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Overview</h3>
                <p class="module-subtitle">Keep the lead context, ownership, and audit trail visible in one place.</p>
            </div>

            <div class="fact-grid">
                <div class="fact-card"><span>Name</span><strong><?= esc($enquiry->student_name) ?></strong></div>
                <div class="fact-card"><span>Mobile</span><strong><?= esc($enquiry->mobile_display) ?></strong></div>
                <div class="fact-card"><span>Source</span><strong><?= esc($enquiry->source_display) ?></strong></div>
                <div class="fact-card"><span>Course</span><strong><?= esc($enquiry->course_display) ?></strong></div>
                <div class="fact-card"><span>Branch</span><strong><?= esc($enquiry->branch_display) ?></strong></div>
                <div class="fact-card"><span>Assigned to</span><strong><?= esc($enquiry->owner_display) ?></strong></div>
                <div class="fact-card"><span>Status</span><strong><?= esc($enquiry->queue_status) ?></strong></div>
                <div class="fact-card"><span>Next follow-up</span><strong><?= esc($enquiry->next_followup_at ? date('d M Y h:i A', strtotime($enquiry->next_followup_at)) : '-') ?></strong></div>
                <div class="fact-card"><span>College</span><strong><?= esc($enquiry->college_name ?: '-') ?></strong></div>
                <div class="fact-card"><span>Created on</span><strong><?= esc($enquiry->created_at ? date('d M Y h:i A', strtotime($enquiry->created_at)) : '-') ?></strong></div>
                <div class="fact-card"><span>Created by</span><strong><?= esc($enquiry->created_by_display) ?></strong></div>
                <div class="fact-card"><span>Modified by</span><strong><?= esc($enquiry->updated_by_display) ?></strong></div>
            </div>

            <?php if (! empty($enquiry->notes)): ?>
                <div class="form-note"><?= esc($enquiry->notes) ?></div>
            <?php endif; ?>
        </section>

        <?php if ($canCloseEnquiry || $canReopenEnquiry || $canAssignFromDetail): ?>
            <section class="form-card">
                <div class="form-section-header">
                    <h3 class="module-title module-title--small">Actions</h3>
                    <p class="module-subtitle">Use this area for operational recovery and closure actions.</p>
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
                            <textarea name="closed_remarks" rows="3" placeholder="Add the closure reason in business language"></textarea>
                        </label>
                        <div class="form-actions">
                            <button class="shell-button shell-button--primary" type="submit">Close enquiry</button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($canReopenEnquiry): ?>
                    <form method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/reopen') ?>">
                        <?= csrf_field() ?>
                        <button class="shell-button shell-button--ghost" type="submit">Reopen enquiry</button>
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
        <?php endif; ?>
    </div>

    <div class="settings-grid">
        <?php if ($canAddFollowup): ?>
            <section class="form-card">
                <div class="form-section-header">
                    <h3 class="module-title module-title--small">Add follow-up</h3>
                    <p class="module-subtitle">Capture the latest conversation, outcome, and next step without leaving the enquiry.</p>
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
            <section class="form-card">
                <div class="form-section-header">
                    <h3 class="module-title module-title--small">Follow-up timeline</h3>
                    <p class="module-subtitle">See the latest conversations in order, along with outcomes and the next promised touchpoint.</p>
                </div>

                <?php if ($followupHistory === []): ?>
                    <div class="empty-state">No follow-ups added yet for this enquiry.</div>
                <?php else: ?>
                    <div class="stack-list">
                        <?php foreach ($followupHistory as $row): ?>
                            <div class="stack-list__item">
                                <strong><?= esc($row->followup_outcome_label ?: 'Follow-up') ?></strong>
                                <span>
                                    <?= esc($row->communication_mode_label ?: '-') ?>
                                    | <?= esc($row->created_at ? date('d M Y h:i A', strtotime($row->created_at)) : '-') ?>
                                    | by <?= esc(trim($row->created_by_name) ?: 'System') ?>
                                </span>
                                <?php if (! empty($row->next_followup_at)): ?>
                                    <span>Next follow-up: <?= esc(date('d M Y h:i A', strtotime($row->next_followup_at))) ?></span>
                                <?php endif; ?>
                                <?php if (! empty($row->remarks)): ?>
                                    <span><?= esc($row->remarks) ?></span>
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
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>

    <div class="settings-grid">
        <section class="form-card">
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
                            <strong>
                                <?= esc(trim(($row->from_user_name ?: 'Unassigned') . ' -> ' . ($row->to_user_name ?: 'Unassigned'))) ?>
                            </strong>
                            <span>
                                <?= esc(($row->from_branch_name ?: 'No branch') . ' -> ' . ($row->to_branch_name ?: 'No branch')) ?>
                                | <?= esc($row->assigned_on ? date('d M Y h:i A', strtotime($row->assigned_on)) : '-') ?>
                                | by <?= esc(trim($row->assigned_by_name) ?: 'System') ?>
                            </span>
                            <?php if (! empty($row->reason)): ?>
                                <span><?= esc($row->reason) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="form-card">
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
                            <span>
                                <?= esc($row->created_at ? date('d M Y h:i A', strtotime($row->created_at)) : '-') ?>
                                | by <?= esc(trim($row->changed_by_name) ?: 'System') ?>
                            </span>
                            <?php if (! empty($row->reason)): ?>
                                <span><?= esc($row->reason) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
<?= $this->endSection() ?>
