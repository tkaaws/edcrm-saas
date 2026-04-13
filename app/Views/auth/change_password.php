<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Change Password') ?></title>
</head>
<body>
    <main>
        <h1>Change Password</h1>
        <form method="post" action="<?= site_url('auth/change-password') ?>">
            <?= csrf_field() ?>
            <div>
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" required>
            </div>
            <div>
                <label for="password">New password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div>
                <label for="password_confirm">Confirm password</label>
                <input id="password_confirm" name="password_confirm" type="password" required>
            </div>
            <button type="submit">Update password</button>
        </form>
    </main>
</body>
</html>
