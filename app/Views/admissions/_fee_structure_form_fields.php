<?php
$formRow = $formRow ?? null;
$courses = $courses ?? [];
$formItems = $formItems ?? [];
$selectedCourseId = (int) ($selectedCourseId ?? 0);
if ($formItems === []) {
    $formItems = [[
        'fee_head_name' => '',
        'fee_head_code' => '',
        'amount' => '',
        'allow_discount' => 1,
        'display_order' => 1,
    ]];
}
$fieldPrefix = $fieldPrefix ?? 'items';
$selectedCourseId = (int) ($formRow->course_id ?? old('course_id', $selectedCourseId));
?>
<div class="form-grid">
    <label class="field">
        <span>Course</span>
        <select name="course_id" required>
            <option value="">Select course</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= (int) $course->id ?>" <?= $selectedCourseId === (int) $course->id ? 'selected' : '' ?>>
                    <?= esc($course->label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">
        <span>Structure name</span>
        <input type="text" name="name" value="<?= esc($formRow->name ?? old('name', '')) ?>" required>
    </label>
    <label class="field">
        <span>Default installments</span>
        <input type="number" name="default_installment_count" min="1" value="<?= esc((string) ($formRow->default_installment_count ?? old('default_installment_count', 1))) ?>" required>
    </label>
    <label class="field">
        <span>Gap between installments (days)</span>
        <input type="number" name="default_installment_gap_days" min="1" value="<?= esc((string) ($formRow->default_installment_gap_days ?? old('default_installment_gap_days', 30))) ?>" required>
    </label>
    <label class="field">
        <span>Status</span>
        <?php $statusValue = (string) ($formRow->status ?? old('status', 'active')); ?>
        <select name="status">
            <option value="active" <?= $statusValue === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $statusValue === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </label>
    <label class="field field--full">
        <span>Description</span>
        <textarea name="description" rows="2"><?= esc($formRow->description ?? old('description', '')) ?></textarea>
    </label>
</div>

<div class="form-card form-card--nested" data-fee-items-builder data-field-prefix="<?= esc($fieldPrefix) ?>">
    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <h3 class="module-title module-title--small">Fee heads</h3>
            <p class="module-subtitle">Add the fee rows that make up this course-wise structure.</p>
        </div>
        <button class="shell-button shell-button--ghost shell-button--sm" type="button" data-add-fee-item>Add fee head</button>
    </div>

    <div class="form-stack" data-fee-items-list>
        <?php foreach ($formItems as $index => $item): ?>
            <div class="summary-card" data-fee-item-row>
                <div class="form-grid">
                    <label class="field">
                        <span>Fee head</span>
                        <input type="text" name="<?= esc($fieldPrefix) ?>[<?= (int) $index ?>][fee_head_name]" value="<?= esc($item['fee_head_name'] ?? $item->fee_head_name ?? '') ?>" required>
                    </label>
                    <label class="field">
                        <span>Code</span>
                        <input type="text" name="<?= esc($fieldPrefix) ?>[<?= (int) $index ?>][fee_head_code]" value="<?= esc($item['fee_head_code'] ?? $item->fee_head_code ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>Amount</span>
                        <input type="number" step="0.01" min="0.01" name="<?= esc($fieldPrefix) ?>[<?= (int) $index ?>][amount]" value="<?= esc((string) ($item['amount'] ?? $item->amount ?? '')) ?>" required>
                    </label>
                    <label class="field">
                        <span>Display order</span>
                        <input type="number" min="1" name="<?= esc($fieldPrefix) ?>[<?= (int) $index ?>][display_order]" value="<?= esc((string) ($item['display_order'] ?? $item->display_order ?? ($index + 1))) ?>">
                    </label>
                    <label class="field">
                        <span>Discount allowed</span>
                        <select name="<?= esc($fieldPrefix) ?>[<?= (int) $index ?>][allow_discount]">
                            <?php $allowDiscount = (int) ($item['allow_discount'] ?? $item->allow_discount ?? 1); ?>
                            <option value="1" <?= $allowDiscount === 1 ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= $allowDiscount === 0 ? 'selected' : '' ?>>No</option>
                        </select>
                    </label>
                </div>
                <div class="form-actions">
                    <button class="shell-button shell-button--soft shell-button--sm" type="button" data-remove-fee-item>Remove</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <template data-fee-item-template>
        <div class="summary-card" data-fee-item-row>
            <div class="form-grid">
                <label class="field">
                    <span>Fee head</span>
                    <input type="text" data-fee-name required>
                </label>
                <label class="field">
                    <span>Code</span>
                    <input type="text" data-fee-code>
                </label>
                <label class="field">
                    <span>Amount</span>
                    <input type="number" step="0.01" min="0.01" data-fee-amount required>
                </label>
                <label class="field">
                    <span>Display order</span>
                    <input type="number" min="1" data-fee-order>
                </label>
                <label class="field">
                    <span>Discount allowed</span>
                    <select data-fee-discount>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </label>
            </div>
            <div class="form-actions">
                <button class="shell-button shell-button--soft shell-button--sm" type="button" data-remove-fee-item>Remove</button>
            </div>
        </div>
    </template>
</div>
