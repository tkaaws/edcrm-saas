<?php
$formAdmission = $formAdmission ?? ($admission ?? null);
$sourceEnquiry = $sourceEnquiry ?? null;
$courses = $courses ?? [];
$colleges = $colleges ?? [];
$assignableBranches = $assignableBranches ?? [];
$assignableUsers = $assignableUsers ?? [];
$assignableUsersByBranch = $assignableUsersByBranch ?? [];
$modeOfClassOptions = $modeOfClassOptions ?? [];
$paymentModeOptions = $paymentModeOptions ?? [];
$useOldInput = (bool) ($useOldInput ?? true);
$fieldValue = static function (string $key, mixed $default = '') use ($useOldInput) {
    return $useOldInput ? old($key, $default) : $default;
};

$selectedBranchId = (int) $fieldValue('branch_id', $formAdmission->branch_id ?? ($sourceEnquiry->branch_id ?? 0));
$selectedAssigneeId = (int) $fieldValue('assigned_user_id', $formAdmission->assigned_user_id ?? ($sourceEnquiry->owner_user_id ?? 0));
?>

<?php if ($sourceEnquiry): ?>
    <section class="form-card form-card--nested">
        <div class="summary-grid">
            <div class="summary-card">
                <span>Source enquiry</span>
                <strong><?= esc($sourceEnquiry->student_name) ?></strong>
                <small><?= esc($sourceEnquiry->mobile_display ?? $sourceEnquiry->mobile) ?></small>
            </div>
            <div class="summary-card">
                <span>Current owner</span>
                <strong><?= esc($sourceEnquiry->owner_display ?? '-') ?></strong>
                <small><?= esc($sourceEnquiry->branch_display ?? '-') ?></small>
            </div>
            <div class="summary-card">
                <span>Lead status</span>
                <strong><?= esc($sourceEnquiry->display_status ?? ucfirst((string) ($sourceEnquiry->lifecycle_status ?? 'active'))) ?></strong>
                <small>We’ll mark this enquiry as admitted once the admission is created.</small>
            </div>
        </div>
        <input type="hidden" name="enquiry_id" value="<?= (int) $sourceEnquiry->id ?>">
    </section>
<?php endif; ?>

<section class="form-card form-card--nested">
    <div class="form-section-header">
        <h3 class="module-title module-title--small">Student details</h3>
        <p class="module-subtitle">Capture the student and ownership details first.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Student name</span>
            <input type="text" name="student_name" value="<?= esc($fieldValue('student_name', $formAdmission->student_name ?? ($sourceEnquiry->student_name ?? ''))) ?>" required>
        </label>
        <label class="field">
            <span>Mobile</span>
            <input type="text" name="mobile" value="<?= esc($fieldValue('mobile', $formAdmission->mobile ?? ($sourceEnquiry->mobile ?? ''))) ?>" required>
        </label>
        <label class="field">
            <span>Email</span>
            <input type="email" name="email" value="<?= esc($fieldValue('email', $formAdmission->email ?? ($sourceEnquiry->email ?? ''))) ?>">
        </label>
        <label class="field">
            <span>WhatsApp number</span>
            <input type="text" name="whatsapp_number" value="<?= esc($fieldValue('whatsapp_number', $formAdmission->whatsapp_number ?? ($sourceEnquiry->whatsapp_number ?? ''))) ?>">
        </label>
        <label class="field">
            <span>College</span>
            <select name="college_id" required>
                <option value="">Select college</option>
                <?php $selectedCollegeId = (int) $fieldValue('college_id', $formAdmission->college_id ?? ($sourceEnquiry->college_id ?? 0)); ?>
                <?php foreach ($colleges as $row): ?>
                    <option value="<?= (int) $row->id ?>" <?= $selectedCollegeId === (int) $row->id ? 'selected' : '' ?>>
                        <?= esc($row->name . ' - ' . $row->city_name . ', ' . $row->state_name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Course</span>
            <select name="course_id" required>
                <option value="">Select course</option>
                <?php $selectedCourseId = (int) $fieldValue('course_id', $formAdmission->course_id ?? ($sourceEnquiry->primary_course_id ?? 0)); ?>
                <?php foreach ($courses as $row): ?>
                    <option value="<?= (int) $row->id ?>" <?= $selectedCourseId === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>City</span>
            <input type="text" name="city" value="<?= esc($fieldValue('city', $formAdmission->city ?? ($sourceEnquiry->city ?? ''))) ?>">
        </label>
        <label class="field">
            <span>Mode of class</span>
            <select name="mode_of_class">
                <option value="">Select class mode</option>
                <?php $selectedMode = (string) $fieldValue('mode_of_class', $formAdmission->mode_of_class ?? ''); ?>
                <?php foreach ($modeOfClassOptions as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= $selectedMode === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Admission date</span>
            <input type="datetime-local" name="admission_date" value="<?= esc($fieldValue('admission_date', isset($formAdmission->admission_date) && $formAdmission->admission_date ? date('Y-m-d\TH:i', strtotime($formAdmission->admission_date)) : date('Y-m-d\TH:i'))) ?>">
        </label>
        <label class="field">
            <span>Branch</span>
            <select name="branch_id" data-branch-select data-user-target="admission_assigned_user_id">
                <option value="">Select branch</option>
                <?php foreach ($assignableBranches as $branch): ?>
                    <option value="<?= (int) $branch->id ?>" <?= $selectedBranchId === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Assigned to</span>
            <select name="assigned_user_id" id="admission_assigned_user_id" data-branch-user-select data-selected-user="<?= esc((string) $selectedAssigneeId) ?>" <?= $selectedBranchId > 0 ? '' : 'disabled' ?>>
                <option value=""><?= $selectedBranchId > 0 ? 'Select team member' : 'Choose branch first' ?></option>
                <?php foreach ($assignableUsers as $user): ?>
                    <option value="<?= (int) $user->id ?>" data-branch-ids="<?= esc(implode(',', $assignableUsersByBranch[(int) $user->id] ?? [])) ?>" <?= $selectedAssigneeId === (int) $user->id ? 'selected' : '' ?>>
                        <?= esc(trim($user->first_name . ' ' . $user->last_name) ?: $user->email) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field field--full">
            <span>Remarks</span>
            <textarea name="remarks" rows="3"><?= esc($fieldValue('remarks', $formAdmission->remarks ?? ($sourceEnquiry->notes ?? ''))) ?></textarea>
        </label>
    </div>
</section>

<section class="form-card form-card--nested">
    <div class="form-section-header">
        <h3 class="module-title module-title--small">Fee snapshot</h3>
        <p class="module-subtitle">Freeze the commercial plan that applies to this student today.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Fee plan label</span>
            <input type="text" name="fee_plan_label" value="<?= esc($fieldValue('fee_plan_label', 'Standard admission plan')) ?>" required>
        </label>
        <label class="field">
            <span>Fee head label</span>
            <input type="text" name="fee_item_label" value="<?= esc($fieldValue('fee_item_label', 'Course Fees')) ?>">
        </label>
        <label class="field">
            <span>Gross fees</span>
            <input type="number" step="0.01" min="0" name="gross_amount" value="<?= esc($fieldValue('gross_amount', '0')) ?>" required>
        </label>
        <label class="field">
            <span>Discount</span>
            <input type="number" step="0.01" min="0" name="discount_amount" value="<?= esc($fieldValue('discount_amount', '0')) ?>">
        </label>
    </div>
</section>

<section class="form-card form-card--nested">
    <div class="form-section-header">
        <h3 class="module-title module-title--small">First payment and installments</h3>
        <p class="module-subtitle">Capture the first payment now and generate the balance schedule in one step.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Initial payment</span>
            <input type="number" step="0.01" min="0" name="initial_payment_amount" value="<?= esc($fieldValue('initial_payment_amount', '0')) ?>">
        </label>
        <label class="field">
            <span>Payment date</span>
            <input type="datetime-local" name="payment_date" value="<?= esc($fieldValue('payment_date', date('Y-m-d\TH:i'))) ?>">
        </label>
        <label class="field">
            <span>Payment mode</span>
            <select name="payment_mode">
                <option value="">Select payment mode</option>
                <?php $selectedPaymentMode = (string) $fieldValue('payment_mode', ''); ?>
                <?php foreach ($paymentModeOptions as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= $selectedPaymentMode === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Reference number</span>
            <input type="text" name="transaction_reference" value="<?= esc($fieldValue('transaction_reference', '')) ?>">
        </label>
        <label class="field">
            <span>Installments</span>
            <input type="number" min="0" name="installment_count" value="<?= esc($fieldValue('installment_count', '1')) ?>">
        </label>
        <label class="field">
            <span>First due date</span>
            <input type="date" name="first_due_date" value="<?= esc($fieldValue('first_due_date', date('Y-m-d', strtotime('+30 days')))) ?>">
        </label>
        <label class="field">
            <span>Gap between installments (days)</span>
            <input type="number" min="1" name="installment_gap_days" value="<?= esc($fieldValue('installment_gap_days', '30')) ?>">
        </label>
        <label class="field field--full">
            <span>Payment remarks</span>
            <textarea name="payment_remarks" rows="3"><?= esc($fieldValue('payment_remarks', '')) ?></textarea>
        </label>
    </div>
</section>

<script>
(() => {
    const branchSelect = document.querySelector('[data-branch-select]');
    const userSelect = document.querySelector('[data-branch-user-select]');
    if (!branchSelect || !userSelect) {
        return;
    }

    const updateOptions = () => {
        const selectedBranch = branchSelect.value;
        const selectedUser = userSelect.getAttribute('data-selected-user') || userSelect.value;
        const firstOption = userSelect.options[0] || null;
        let hasSelectedUser = false;

        userSelect.disabled = selectedBranch === '';
        if (firstOption) {
            firstOption.textContent = selectedBranch === '' ? 'Choose branch first' : 'Select team member';
        }

        Array.from(userSelect.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            const branchIds = (option.dataset.branchIds || '').split(',').filter(Boolean);
            const visible = selectedBranch !== '' && branchIds.includes(selectedBranch);
            option.hidden = !visible;

            if (visible && option.value === selectedUser) {
                hasSelectedUser = true;
            }

            if (!visible && option.selected) {
                option.selected = false;
            }
        });

        if (selectedBranch !== '' && hasSelectedUser) {
            userSelect.value = selectedUser;
        } else if (selectedBranch === '') {
            userSelect.value = '';
        }
    };

    branchSelect.addEventListener('change', updateOptions);
    updateOptions();
})();
</script>
