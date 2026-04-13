<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Login') ?></title>
</head>
<body>
    <main>
        <h1>Login</h1>
        <form method="post" action="<?= site_url('auth/login') ?>">
            <?= csrf_field() ?>
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= esc(old('email')) ?>" required>
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>
