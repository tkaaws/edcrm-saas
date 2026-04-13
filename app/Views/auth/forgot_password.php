<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Forgot Password') ?></title>
</head>
<body>
    <main>
        <h1>Forgot Password</h1>
        <form method="post" action="<?= site_url('auth/forgot-password') ?>">
            <?= csrf_field() ?>
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= esc(old('email')) ?>" required>
            </div>
            <button type="submit">Send reset link</button>
        </form>
    </main>
</body>
</html>
