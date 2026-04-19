<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <p class="module-subtitle">Convert an enquiry into an admission, capture the first payment, and generate the remaining schedule in one clean flow.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('admissions') ?>">Back to admissions</a>
    </div>

    <form class="form-card form-stack" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>
        <?= $this->include('admissions/_form_sections') ?>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('admissions') ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit"><?= esc($submitText) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
