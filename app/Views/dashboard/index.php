<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard') ?></title>
</head>
<body>
    <main>
        <h1>EDCRM Dashboard</h1>
        <p>Welcome <?= esc($firstName ?: 'User') ?>.</p>
        <ul>
            <li>Tenant ID: <?= esc((string) ($tenantId ?? '')) ?></li>
            <li>Branch ID: <?= esc((string) ($branchId ?? '')) ?></li>
            <li>Role Code: <?= esc((string) ($roleCode ?? '')) ?></li>
        </ul>
    </main>
</body>
</html>
