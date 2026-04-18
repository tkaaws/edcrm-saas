<?php
$formEnquiry = $formEnquiry ?? ($enquiry ?? null);
$sources = $sources ?? [];
$courses = $courses ?? [];
$colleges = $colleges ?? [];
$qualifications = $qualifications ?? [];
$assignableBranches = $assignableBranches ?? [];
$assignableUsers = $assignableUsers ?? [];
$assignableUsersByBranch = $assignableUsersByBranch ?? [];
$showAssignmentSection = (bool) ($showAssignmentSection ?? false);
$useOldInput = (bool) ($useOldInput ?? true);
$fieldValue = static function (string $key, mixed $default = '') use ($useOldInput) {
    return $useOldInput ? old($key, $default) : $default;
};

$selectedBranchId = (int) $fieldValue('branch_id', $formEnquiry->branch_id ?? 0);
$selectedOwnerId = (int) $fieldValue('owner_user_id', $formEnquiry->owner_user_id ?? 0);

$hasSelectedBranchOption = false;
foreach ($assignableBranches as $branch) {
    if ((int) $branch->id === $selectedBranchId) {
        $hasSelectedBranchOption = true;
        break;
    }
}

$hasSelectedOwnerOption = false;
foreach ($assignableUsers as $user) {
    if ((int) $user->id === $selectedOwnerId) {
        $hasSelectedOwnerOption = true;
        break;
    }
}
?>
<section class="form-card form-card--nested">
    <div class="form-section-header">
        <h3 class="module-title module-title--small">Lead details</h3>
        <p class="module-subtitle">Keep the first capture quick and consistent.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Student name</span>
            <input type="text" name="student_name" value="<?= esc($fieldValue('student_name', $formEnquiry->student_name ?? '')) ?>" required>
        </label>
        <label class="field">
            <span>Mobile</span>
            <input type="text" name="mobile" value="<?= esc($fieldValue('mobile', $formEnquiry->mobile ?? '')) ?>" required>
        </label>
        <label class="field">
            <span>Email</span>
            <input type="email" name="email" value="<?= esc($fieldValue('email', $formEnquiry->email ?? '')) ?>">
        </label>
        <label class="field">
            <span>WhatsApp number</span>
            <input type="text" name="whatsapp_number" value="<?= esc($fieldValue('whatsapp_number', $formEnquiry->whatsapp_number ?? '')) ?>">
        </label>
        <label class="field">
            <span>Enquiry source</span>
            <select name="source_id" required>
                <option value="">Select source</option>
                <?php $selectedSourceId = (int) $fieldValue('source_id', $formEnquiry->source_id ?? 0); ?>
                <?php foreach ($sources as $row): ?>
                    <option value="<?= (int) $row->id ?>" <?= $selectedSourceId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Course</span>
            <select name="primary_course_id" required>
                <option value="">Select course</option>
                <?php $selectedCourseId = (int) $fieldValue('primary_course_id', $formEnquiry->primary_course_id ?? 0); ?>
                <?php foreach ($courses as $row): ?>
                    <option value="<?= (int) $row->id ?>" <?= $selectedCourseId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>College</span>
            <select name="college_id" required>
                <option value="">Select college</option>
                <?php $selectedCollegeId = (int) $fieldValue('college_id', $formEnquiry->college_id ?? 0); ?>
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
                <?php $selectedQualificationId = (int) $fieldValue('qualification_id', $formEnquiry->qualification_id ?? 0); ?>
                <?php foreach ($qualifications as $row): ?>
                    <option value="<?= (int) $row->id ?>" <?= $selectedQualificationId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>City</span>
            <input type="text" name="city" value="<?= esc($fieldValue('city', $formEnquiry->city ?? '')) ?>">
        </label>
        <label class="field">
            <span>Next follow-up</span>
            <input type="datetime-local" name="next_followup_at" value="<?= esc($fieldValue('next_followup_at', isset($formEnquiry->next_followup_at) && $formEnquiry->next_followup_at ? date('Y-m-d\TH:i', strtotime($formEnquiry->next_followup_at)) : '')) ?>">
        </label>
        <label class="field field--full">
            <span>Remarks</span>
            <textarea name="notes" rows="4"><?= esc($fieldValue('notes', $formEnquiry->notes ?? '')) ?></textarea>
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
                <select name="branch_id" data-branch-select data-user-target="owner_user_id_<?= esc((string) ($formEnquiry->id ?? 'create')) ?>">
                    <option value="">Keep current branch</option>
                    <?php if ($selectedBranchId > 0 && ! $hasSelectedBranchOption): ?>
                        <option value="<?= esc((string) $selectedBranchId) ?>" selected>
                            <?= esc($formEnquiry->branch_name ?? $formEnquiry->branch_display ?? ('Current branch #' . $selectedBranchId)) ?>
                        </option>
                    <?php endif; ?>
                    <?php foreach ($assignableBranches as $branch): ?>
                        <option value="<?= (int) $branch->id ?>" <?= $selectedBranchId === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Assigned to</span>
                <?php $selectedBranchText = $selectedBranchId > 0 ? 'Keep current owner' : 'Choose branch first'; ?>
                <select name="owner_user_id" id="owner_user_id_<?= esc((string) ($formEnquiry->id ?? 'create')) ?>" data-branch-user-select data-selected-user="<?= esc((string) $selectedOwnerId) ?>" <?= $selectedBranchId > 0 ? '' : 'disabled' ?>>
                    <option value=""><?= esc($selectedBranchText) ?></option>
                    <?php if ($selectedOwnerId > 0 && ! $hasSelectedOwnerOption): ?>
                        <option value="<?= esc((string) $selectedOwnerId) ?>" selected>
                            <?= esc($formEnquiry->owner_name ?? $formEnquiry->owner_display ?? ('Current owner #' . $selectedOwnerId)) ?>
                        </option>
                    <?php endif; ?>
                    <?php foreach ($assignableUsers as $user): ?>
                        <option value="<?= (int) $user->id ?>" data-branch-ids="<?= esc(implode(',', $assignableUsersByBranch[(int) $user->id] ?? [])) ?>" <?= $selectedOwnerId === (int) $user->id ? 'selected' : '' ?>>
                            <?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->email) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </section>
<?php endif; ?>
