<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <p class="module-subtitle">Add or update a branch for your company.</p>
        </div>
        <a class="shell-button shell-button--ghost" href="<?= site_url('branches') ?>">Back to branches</a>
    </div>

    <form class="form-card form-stack" method="post" action="<?= esc($formAction) ?>">
        <?= csrf_field() ?>
        <?php $formBranch = $branch ?? null; ?>
        <?= $this->include('branches/_form_sections') ?>

        <div class="form-actions">
            <a class="shell-button shell-button--ghost" href="<?= site_url('branches') ?>">Cancel</a>
            <button class="shell-button shell-button--primary" type="submit"><?= esc($submitText) ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
