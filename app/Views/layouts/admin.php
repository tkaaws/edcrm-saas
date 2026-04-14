<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'EDCRM SaaS') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body class="shell-body">
    <?php
    $activeNav       = $activeNav ?? 'dashboard';
    $isPlatformAdmin = $isPlatformAdmin ?? false;
    $navItems = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => site_url('dashboard'),        'meta' => 'Home',     'show' => true],
        ['key' => 'users',     'label' => 'Users',     'href' => site_url('users'),            'meta' => 'People',   'show' => ! $isPlatformAdmin],
        ['key' => 'branches',  'label' => 'Branches',  'href' => site_url('branches'),         'meta' => 'Locations','show' => ! $isPlatformAdmin],
        ['key' => 'roles',     'label' => 'Roles',     'href' => site_url('roles'),            'meta' => 'Access',   'show' => ! $isPlatformAdmin],
        ['key' => 'settings',  'label' => 'Settings',  'href' => site_url('settings'),         'meta' => 'Config',   'show' => ! $isPlatformAdmin],
        ['key' => 'tenants',   'label' => 'Tenants',   'href' => site_url('platform/tenants'), 'meta' => 'Platform', 'show' => $isPlatformAdmin],
    ];
    ?>
    <div class="shell">
        <aside class="shell-sidebar">
            <div class="shell-brand">
                <div class="shell-brand__mark">E</div>
                <div>
                    <div class="shell-brand__name">EDCRM SaaS</div>
                    <div class="shell-brand__meta"><?= esc($isPlatformAdmin ? 'Platform admin' : ($tenantLabel ?? 'Loading…')) ?></div>
                </div>
            </div>

            <nav class="shell-nav" aria-label="Primary">
                <?php foreach ($navItems as $item): ?>
                    <?php if (! $item['show']) continue; ?>
                    <?php $classes = 'shell-nav__item' . ($activeNav === $item['key'] ? ' shell-nav__item--active' : ''); ?>
                    <a class="<?= esc($classes) ?>" href="<?= esc($item['href']) ?>">
                        <span><?= esc($item['label']) ?></span>
                        <small><?= esc($item['meta']) ?></small>
                    </a>
                <?php endforeach; ?>
            </nav>

            <section class="shell-sidebar__section">
                <h2>Context</h2>
                <dl class="context-list">
                    <div>
                        <dt>Tenant</dt>
                        <dd><?= esc($tenantLabel ?? 'Not resolved') ?></dd>
                    </div>
                    <div>
                        <dt>Branch</dt>
                        <dd><?= esc($branchLabel ?? 'Not selected') ?></dd>
                    </div>
                    <div>
                        <dt>Role</dt>
                        <dd><?= esc($roleLabel ?? 'Unknown') ?></dd>
                    </div>
                </dl>
            </section>
        </aside>

        <div class="shell-main">
            <header class="shell-header">
                <div>
                    <p class="shell-header__eyebrow">Operations workspace</p>
                    <h1 class="shell-header__title"><?= esc($pageTitle ?? ($title ?? 'Dashboard')) ?></h1>
                </div>

                <div class="shell-header__actions">
                    <div class="shell-user">
                        <span class="shell-user__name"><?= esc($userDisplayName ?? 'User') ?></span>
                        <span class="shell-user__meta"><?= esc($userEmail ?? '') ?></span>
                    </div>
                    <a class="shell-button shell-button--ghost" href="<?= site_url('auth/logout') ?>">Logout</a>
                </div>
            </header>

            <?php if (session()->getFlashdata('message')): ?>
                <div class="shell-alert shell-alert--success">
                    <?= esc(session()->getFlashdata('message')) ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="shell-alert shell-alert--danger">
                    <?= esc(session()->getFlashdata('error')) ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('suspension_warning')): ?>
                <div class="shell-alert shell-alert--warning">
                    This tenant is in suspended mode. Read access is available, but operational writes are restricted.
                </div>
            <?php endif; ?>

            <main class="shell-content">
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>
</body>
</html>
