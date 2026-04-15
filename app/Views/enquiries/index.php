<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Enquiries</h2>
            <p class="module-subtitle">This screen is being prepared for enquiry entry and follow-up work.</p>
        </div>
    </div>

    <div class="settings-grid">
        <form class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Create enquiry</h3>
                    <p class="module-subtitle">Preview of the business lists that will be used while creating and following up enquiries.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Student name</span>
                    <input type="text" placeholder="Example: Aditi Sharma" disabled>
                </label>
                <label class="field">
                    <span>Mobile number</span>
                    <input type="text" placeholder="Coming next" disabled>
                </label>
                <label class="field">
                    <span>Enquiry source</span>
                    <select disabled><?php foreach ($sources as $row): ?><option><?= esc($row->label) ?></option><?php endforeach; ?></select>
                </label>
                <label class="field">
                    <span>Purpose category</span>
                    <select disabled><?php foreach ($purposes as $row): ?><option><?= esc($row->label) ?></option><?php endforeach; ?></select>
                </label>
                <label class="field">
                    <span>Lead qualification</span>
                    <select disabled><?php foreach ($qualifications as $row): ?><option><?= esc($row->label) ?></option><?php endforeach; ?></select>
                </label>
                <label class="field">
                    <span>Course</span>
                    <select disabled><?php foreach ($courses as $row): ?><option><?= esc($row->label) ?></option><?php endforeach; ?></select>
                </label>
                <label class="field">
                    <span>Preferred communication mode</span>
                    <select disabled><?php foreach ($modes as $row): ?><option><?= esc($row->label) ?></option><?php endforeach; ?></select>
                </label>
                <label class="field">
                    <span>Follow-up status</span>
                    <select disabled><?php foreach ($followups as $row): ?><option><?= esc($row->label) ?></option><?php endforeach; ?></select>
                </label>
                <label class="field">
                    <span>Closure reason</span>
                    <select disabled><?php foreach ($closureReasons as $row): ?><option><?= esc($row->label) ?></option><?php endforeach; ?></select>
                </label>
                <label class="field">
                    <span>Lost reason</span>
                    <select disabled><?php foreach ($lostReasons as $row): ?><option><?= esc($row->label) ?></option><?php endforeach; ?></select>
                </label>
            </div>

            <div class="form-actions">
                <button class="shell-button shell-button--primary" type="button" disabled>Enquiry form coming next</button>
            </div>
        </form>

        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Available lists</h3>
                    <p class="module-subtitle">These lists are already ready for use in the enquiry form.</p>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Catalog</th>
                            <th>Values loaded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Enquiry source</td><td><?= count($sources) ?></td></tr>
                        <tr><td>Lead qualification</td><td><?= count($qualifications) ?></td></tr>
                        <tr><td>Follow-up status</td><td><?= count($followups) ?></td></tr>
                        <tr><td>Communication mode</td><td><?= count($modes) ?></td></tr>
                        <tr><td>Lost reasons</td><td><?= count($lostReasons) ?></td></tr>
                        <tr><td>Closure reasons</td><td><?= count($closureReasons) ?></td></tr>
                        <tr><td>Purpose categories</td><td><?= count($purposes) ?></td></tr>
                        <tr><td>Courses</td><td><?= count($courses) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
<?= $this->endSection() ?>
