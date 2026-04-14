<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Login') ?> — EDCRM SaaS</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body class="auth-body">
    <main class="auth-card">
        <div class="auth-brand">
            <div class="shell-brand__mark">E</div>
            <div>
                <div class="shell-brand__name">EDCRM SaaS</div>
                <div class="shell-brand__meta">Sign in to your institute</div>
            </div>
        </div>

        <?php if (session()->getFlashdata('message')): ?>
            <div class="shell-alert shell-alert--success"><?= esc(session()->getFlashdata('message')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="shell-alert shell-alert--danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="shell-alert shell-alert--danger">
                <?php foreach ((array) session()->getFlashdata('errors') as $e): ?>
                    <div><?= esc($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('auth/login') ?>">
            <?= csrf_field() ?>

            <div class="form-grid form-grid--single">
                <label class="field">
                    <span>Institute slug</span>
                    <input type="text" name="tenant_slug" value="<?= esc(old('tenant_slug')) ?>" placeholder="e.g. demo-institute" autocomplete="organization">
                    <small class="field-hint">Leave blank if you have a dedicated login URL.</small>
                </label>

                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= esc(old('email')) ?>" required autocomplete="email">
                </label>

                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" required autocomplete="current-password">
                </label>
            </div>

            <div class="form-actions form-actions--auth">
                <button class="shell-button shell-button--primary shell-button--full" type="submit">Sign in</button>
            </div>

            <div class="auth-links">
                <a href="<?= site_url('auth/forgot-password') ?>">Forgot password?</a>
            </div>
        </form>
    </main>
</body>
</html>
