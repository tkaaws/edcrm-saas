<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <p class="module-subtitle">Capture the lead first, then enrich the record without slowing down the sales team.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('enquiries') ?>">Back to enquiries</a>
    </div>

    <form class="form-card form-stack" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>
        <?= $this->include('enquiries/_form_sections') ?>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url(empty($enquiry) ? 'enquiries' : 'enquiries/' . $enquiry->id) ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit"><?= esc($submitText) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
