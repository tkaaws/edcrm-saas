<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Edit Follow-up</h2>
            <p class="module-subtitle">Update the latest conversation details without changing the rest of the enquiry record.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries/' . $enquiry->id) ?>">Back to enquiry</a>
    </div>

    <form class="form-card form-stack" method="post" action="<?= site_url('enquiries/' . $enquiry->id . '/followups/' . $followup->id) ?>">
        <?= csrf_field() ?>

        <section class="form-card form-card--nested">
            <div class="form-section-header">
                <h3 class="module-title module-title--small">Follow-up details</h3>
                <p class="module-subtitle">Keep the outcome, remarks, and next follow-up promise accurate.</p>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Communication mode</span>
                    <select name="communication_type_id" required>
                        <option value="">Select mode</option>
                        <?php $selected = (int) old('communication_type_id', $followup->communication_type_id ?? 0); ?>
                        <?php foreach ($communicationModes as $row): ?>
                            <option value="<?= (int) $row->id ?>" <?= $selected === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">
                    <span>Follow-up outcome</span>
                    <select name="followup_outcome_id" required>
                        <option value="">Select outcome</option>
                        <?php $selected = (int) old('followup_outcome_id', $followup->followup_outcome_id ?? 0); ?>
                        <?php foreach ($followupStatuses as $row): ?>
                            <option value="<?= (int) $row->id ?>" <?= $selected === (int) $row->id ? 'selected' : '' ?>><?= esc($row->label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">
                    <span>Next follow-up</span>
                    <input type="datetime-local" name="next_followup_at" value="<?= esc(old('next_followup_at', ! empty($followup->next_followup_at) ? date('Y-m-d\TH:i', strtotime($followup->next_followup_at)) : '')) ?>">
                </label>

                <label class="field field--full">
                    <span>Remarks</span>
                    <textarea name="remarks" rows="5" required><?= esc(old('remarks', $followup->remarks ?? '')) ?></textarea>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries/' . $enquiry->id) ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit">Save follow-up</button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
