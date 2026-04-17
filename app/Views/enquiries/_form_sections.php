<?php
$enquiry = $enquiry ?? null;
$sources = $sources ?? [];
$courses = $courses ?? [];
$colleges = $colleges ?? [];
$qualifications = $qualifications ?? [];
$assignableBranches = $assignableBranches ?? [];
$assignableUsers = $assignableUsers ?? [];
$showAssignmentSection = (bool) ($showAssignmentSection ?? false);
?>
<section class="form-card form-card--nested">
    <div class="form-section-header">
        <h3 class="module-title module-title--small">Lead details</h3>
        <p class="module-subtitle">Keep the first capture quick and consistent.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Student name</span>
            <input type="text" name="student_name" value="<?= esc(old('student_name', $enquiry->student_name ?? '')) ?>" required>
        </label>
        <label class="field">
            <span>Mobile</span>
            <input type="text" name="mobile" value="<?= esc(old('mobile', $enquiry->mobile ?? '')) ?>" required>
        </label>
        <label class="field">
            <span>Email</span>
            <input type="email" name="email" value="<?= esc(old('email', $enquiry->email ?? '')) ?>">
        </label>
        <label class="field">
            <span>WhatsApp number</span>
            <input type="text" name="whatsapp_number" value="<?= esc(old('whatsapp_number', $enquiry->whatsapp_number ?? '')) ?>">
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
            <span>City</span>
            <input type="text" name="city" value="<?= esc(old('city', $enquiry->city ?? '')) ?>">
        </label>
        <label class="field">
            <span>Next follow-up</span>
            <input type="datetime-local" name="next_followup_at" value="<?= esc(old('next_followup_at', isset($enquiry->next_followup_at) && $enquiry->next_followup_at ? date('Y-m-d\TH:i', strtotime($enquiry->next_followup_at)) : '')) ?>">
        </label>
        <label class="field field--full">
            <span>Remarks</span>
            <textarea name="notes" rows="4"><?= esc(old('notes', $enquiry->notes ?? '')) ?></textarea>
        </label>
    </div>
</section>

<?php if ($showAssignmentSection): ?>
    <section class="form-card form-card--nested">
        <div class="form-section-header">
            <h3 class="module-title module-title--small">Assignment</h3>
            <p class="module-subtitle">Only show ownership controls here for active leads when you are allowed to move them.</p>
        </div>

        <div class="form-grid">
            <label class="field">
                <span>Branch</span>
                <select name="branch_id">
                    <option value="">Keep current branch</option>
                    <?php $selectedBranchId = (int) old('branch_id', $enquiry->branch_id ?? 0); ?>
                    <?php foreach ($assignableBranches as $branch): ?>
                        <option value="<?= (int) $branch->id ?>" <?= $selectedBranchId === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Assigned to</span>
                <select name="owner_user_id">
                    <option value="">Keep current owner</option>
                    <?php $selectedOwnerId = (int) old('owner_user_id', $enquiry->owner_user_id ?? 0); ?>
                    <?php foreach ($assignableUsers as $user): ?>
                        <option value="<?= (int) $user->id ?>" <?= $selectedOwnerId === (int) $user->id ? 'selected' : '' ?>>
                            <?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->email) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </section>
<?php endif; ?>
