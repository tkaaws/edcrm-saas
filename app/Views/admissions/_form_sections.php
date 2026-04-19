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
$feeStructureOptionsUrl = $feeStructureOptionsUrl ?? site_url('admissions/fee-structures/options');
$useOldInput = (bool) ($useOldInput ?? true);
$fieldValue = static function (string $key, mixed $default = '') use ($useOldInput) {
    return $useOldInput ? old($key, $default) : $default;
};

$selectedBranchId = (int) $fieldValue('branch_id', $formAdmission->branch_id ?? ($sourceEnquiry->branch_id ?? 0));
$selectedAssigneeId = (int) $fieldValue('assigned_user_id', $formAdmission->assigned_user_id ?? ($sourceEnquiry->owner_user_id ?? 0));
$selectedCourseId = (int) $fieldValue('course_id', $formAdmission->course_id ?? ($sourceEnquiry->primary_course_id ?? 0));
$selectedFeeStructureId = (int) $fieldValue('fee_structure_id', 0);
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
                <small>We'll mark this enquiry as admitted once the admission is created.</small>
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
            <select name="course_id" data-fee-course-select data-fee-source-url="<?= esc($feeStructureOptionsUrl) ?>" required>
                <option value="">Select course</option>
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
        <p class="module-subtitle">Choose the course-wise fee structure and let admissions load the plan details automatically.</p>
    </div>

    <div class="form-grid">
        <label class="field">
            <span>Fee structure</span>
            <select name="fee_structure_id" data-fee-structure-select data-selected-structure="<?= esc((string) $selectedFeeStructureId) ?>" <?= $selectedCourseId > 0 ? '' : 'disabled' ?> required>
                <option value=""><?= $selectedCourseId > 0 ? 'Select fee structure' : 'Choose course first' ?></option>
            </select>
        </label>
        <label class="field">
            <span>Gross fees</span>
            <input type="text" data-fee-gross-display value="0.00" readonly>
        </label>
        <label class="field">
            <span>Discount</span>
            <input type="number" step="0.01" min="0" name="discount_amount" value="<?= esc($fieldValue('discount_amount', '0')) ?>">
        </label>
        <label class="field">
            <span>Recommended installments</span>
            <input type="text" data-fee-installment-display value="-" readonly>
        </label>
        <div class="field field--full">
            <span>Fee heads in this structure</span>
            <div class="table-card table-card--plain">
                <div class="table-wrap">
                    <table class="data-table" data-fee-items-preview>
                        <thead>
                            <tr>
                                <th>Fee head</th>
                                <th>Amount</th>
                                <th>Discount allowed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-fee-preview-empty>
                                <td colspan="3" class="empty-state">Choose a course and fee structure to preview the fee heads.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
    if (branchSelect && userSelect) {
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
    }

    const courseSelect = document.querySelector('[data-fee-course-select]');
    const structureSelect = document.querySelector('[data-fee-structure-select]');
    const grossDisplay = document.querySelector('[data-fee-gross-display]');
    const installmentDisplay = document.querySelector('[data-fee-installment-display]');
    const previewTable = document.querySelector('[data-fee-items-preview] tbody');
    let structuresById = {};

    const renderPreview = (structureId, preserveSchedule) => {
        if (!structureSelect || !grossDisplay || !installmentDisplay || !previewTable) {
            return;
        }

        const structure = structuresById[structureId] || null;
        if (!structure) {
            grossDisplay.value = '0.00';
            installmentDisplay.value = '-';
            previewTable.innerHTML = '<tr data-fee-preview-empty><td colspan="3" class="empty-state">Choose a course and fee structure to preview the fee heads.</td></tr>';
            return;
        }

        grossDisplay.value = Number(structure.total_amount || 0).toFixed(2);
        installmentDisplay.value = `${structure.default_installment_count} installments every ${structure.default_installment_gap_days} days`;
        previewTable.innerHTML = '';

        (structure.items || []).forEach((item) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.fee_head_name}</td>
                <td>${Number(item.amount || 0).toFixed(2)}</td>
                <td>${item.allow_discount ? 'Yes' : 'No'}</td>
            `;
            previewTable.appendChild(row);
        });

        const installmentCountField = document.querySelector('input[name="installment_count"]');
        const installmentGapField = document.querySelector('input[name="installment_gap_days"]');
        if (!preserveSchedule) {
            if (installmentCountField) {
                installmentCountField.value = structure.default_installment_count;
            }
            if (installmentGapField) {
                installmentGapField.value = structure.default_installment_gap_days;
            }
        }
    };

    const loadStructures = (courseId, preserveSchedule) => {
        if (!courseSelect || !structureSelect) {
            return;
        }

        structureSelect.innerHTML = `<option value="">${courseId ? 'Select fee structure' : 'Choose course first'}</option>`;
        structureSelect.disabled = !courseId;
        structuresById = {};
        renderPreview('', preserveSchedule);

        if (!courseId) {
            return;
        }

        fetch(`${courseSelect.dataset.feeSourceUrl}?course_id=${encodeURIComponent(courseId)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then((response) => response.ok ? response.json() : Promise.reject(response))
            .then((payload) => {
                (payload.structures || []).forEach((structure) => {
                    structuresById[String(structure.id)] = structure;
                    const option = document.createElement('option');
                    option.value = structure.id;
                    option.textContent = `${structure.name} (${Number(structure.total_amount || 0).toFixed(2)})`;
                    if (String(structure.id) === (structureSelect.dataset.selectedStructure || '')) {
                        option.selected = true;
                    }
                    structureSelect.appendChild(option);
                });

                if (structureSelect.value) {
                    renderPreview(structureSelect.value, preserveSchedule);
                }
            })
            .catch(() => {
                previewTable.innerHTML = '<tr><td colspan="3" class="empty-state">Fee structures could not be loaded right now.</td></tr>';
            });
    };

    if (courseSelect && structureSelect) {
        courseSelect.addEventListener('change', () => {
            structureSelect.dataset.selectedStructure = '';
            loadStructures(courseSelect.value, false);
        });

        structureSelect.addEventListener('change', () => {
            renderPreview(structureSelect.value, false);
        });

        loadStructures(courseSelect.value, true);
    }
})();
</script>
