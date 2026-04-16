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
    $enabledModules  = $enabledModules ?? [];
    $privilegeCodes  = session()->get('user_privilege_codes') ?? [];

    // Helper: is a feature module enabled for this tenant?
    $feat = static fn(string $code): bool => in_array($code, $enabledModules, true);
    $canCode = static fn(string $code): bool => in_array($code, $privilegeCodes, true);
    $canPrefix = static function (string $prefix) use ($privilegeCodes): bool {
        foreach ($privilegeCodes as $code) {
            if (str_starts_with($code, $prefix)) {
                return true;
            }
        }

        return false;
    };

    $navItems = [
        // Always visible to tenant users
        ['key' => 'dashboard',   'label' => 'Dashboard',   'href' => site_url('dashboard'),          'meta' => 'Home',       'show' => true],

        // Core ops - always visible once logged in as tenant user
        ['key' => 'users',       'label' => 'Users',       'href' => site_url('users'),              'meta' => 'People',     'show' => ! $isPlatformAdmin && $canPrefix('users.')],
        ['key' => 'branches',    'label' => 'Branches',    'href' => site_url('branches'),           'meta' => 'Locations',  'show' => ! $isPlatformAdmin && $canPrefix('branches.')],
        ['key' => 'roles',       'label' => 'Roles',       'href' => site_url('roles'),              'meta' => 'Access',     'show' => ! $isPlatformAdmin && $canPrefix('roles.')],
        ['key' => 'colleges',    'label' => 'Colleges',    'href' => site_url('colleges'),           'meta' => 'Campus',     'show' => ! $isPlatformAdmin && $canPrefix('colleges.')],
        ['key' => 'master_data', 'label' => 'Business Lookup Data', 'href' => site_url('settings/master-data'),'meta' => 'Lists',   'show' => ! $isPlatformAdmin && $canPrefix('settings.')],

        // Feature-gated modules - only shown when plan includes the module
        ['key' => 'enquiries',   'label' => 'Enquiries',   'href' => site_url('enquiries'),          'meta' => 'CRM',        'show' => ! $isPlatformAdmin && $feat('crm_core') && ($canPrefix('enquiries.') || $canPrefix('followups.'))],
        ['key' => 'admissions',  'label' => 'Admissions',  'href' => site_url('admissions'),         'meta' => 'Enrolment',  'show' => ! $isPlatformAdmin && $feat('admissions') && ($canPrefix('admissions.') || $canPrefix('fees.'))],
        ['key' => 'batches',     'label' => 'Batches',     'href' => site_url('batches'),            'meta' => 'Scheduling', 'show' => ! $isPlatformAdmin && $feat('batch_management') && ($canPrefix('batches.') || $canPrefix('students.'))],
        ['key' => 'service',     'label' => 'Service',     'href' => site_url('service'),            'meta' => 'Tickets',    'show' => ! $isPlatformAdmin && $feat('service_tickets') && $canPrefix('tickets.')],
        ['key' => 'placement',   'label' => 'Placement',   'href' => site_url('placement'),          'meta' => 'Jobs',       'show' => ! $isPlatformAdmin && $feat('placement') && $canPrefix('placement.')],
        ['key' => 'reports',     'label' => 'Reports',     'href' => site_url('reports'),            'meta' => 'Analytics',  'show' => ! $isPlatformAdmin && $feat('advanced_reports') && $canPrefix('reports.')],

        // Always at bottom for tenant users
        ['key' => 'settings',    'label' => 'Settings',    'href' => site_url('settings'),           'meta' => 'Config',     'show' => ! $isPlatformAdmin && $canPrefix('settings.')],
        ['key' => 'billing',     'label' => 'Billing',     'href' => site_url('billing'),            'meta' => 'Plan',       'show' => ! $isPlatformAdmin && ($canCode('billing.view') || $canCode('billing.manage'))],

        // Platform admin only
        ['key' => 'tenants',       'label' => 'Companies',     'href' => site_url('platform/tenants'),       'meta' => 'Platform',  'show' => $isPlatformAdmin],
        ['key' => 'plans',         'label' => 'Plans',         'href' => site_url('platform/plans'),         'meta' => 'Billing',   'show' => $isPlatformAdmin],
        ['key' => 'subscriptions', 'label' => 'Subscriptions', 'href' => site_url('platform/subscriptions'), 'meta' => 'Accounts',  'show' => $isPlatformAdmin],
        ['key' => 'platform_master_data', 'label' => 'Business Lookup Data', 'href' => site_url('platform/master-data'), 'meta' => 'Catalogs', 'show' => $isPlatformAdmin],
    ];
    ?>
    <div class="shell">
        <button class="shell-overlay" type="button" aria-label="Close menu"></button>
        <aside class="shell-sidebar" id="primary-nav">
            <div class="shell-brand">
                <div class="shell-brand__mark">E</div>
                <div>
                    <div class="shell-brand__name">EDCRM SaaS</div>
                    <div class="shell-brand__meta"><?= esc($isPlatformAdmin ? 'Platform admin' : ($tenantLabel ?? 'Loading...')) ?></div>
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
                        <dt>Company</dt>
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
                <div class="shell-header__main">
                    <button class="shell-button shell-button--ghost shell-menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">Menu</button>
                    <div>
                        <p class="shell-header__eyebrow"><?= $isPlatformAdmin ? 'Platform administration' : 'Operations workspace' ?></p>
                        <h1 class="shell-header__title"><?= esc($pageTitle ?? ($title ?? 'Dashboard')) ?></h1>
                    </div>
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

            <?php if (session()->get('impersonation_active')): ?>
                <?php
                $impersonationPath = session()->get('impersonation_path') ?? [];
                $impersonationLevel = (int) (session()->get('impersonation_level') ?? 1);
                $impersonationMaxDepth = (int) (session()->get('impersonation_max_depth') ?? 4);
                $impersonationPathText = '';
                if (is_array($impersonationPath) && $impersonationPath !== []) {
                    $impersonationPathText = implode(' > ', array_map(static fn($item): string => (string) $item, $impersonationPath));
                }
                ?>
                <div class="shell-alert shell-alert--warning shell-alert--split">
                    <div>
                        <strong>Support session active.</strong>
                        You are viewing the workspace as <?= esc($userDisplayName ?? 'target user') ?>.
                        <?php if (session()->get('impersonation_actor_name')): ?>
                            Original account: <?= esc((string) session()->get('impersonation_actor_name')) ?>.
                        <?php endif; ?>
                        <?php if ($impersonationPathText !== ''): ?>
                            Path: <?= esc($impersonationPathText) ?>.
                        <?php endif; ?>
                        Level <?= esc((string) $impersonationLevel) ?> of <?= esc((string) $impersonationMaxDepth) ?>.
                        <?php if (session()->get('impersonation_reason')): ?>
                            Reason: <?= esc((string) session()->get('impersonation_reason')) ?>.
                        <?php endif; ?>
                    </div>
                    <div class="shell-alert__actions">
                        <form method="post" action="<?= site_url('impersonation/stop') ?>">
                            <?= csrf_field() ?>
                            <button class="shell-button shell-button--ghost" type="submit">
                                <?= $impersonationLevel > 1 ? 'Back one level' : 'Return to previous account' ?>
                            </button>
                        </form>
                        <?php if ($impersonationLevel > 1): ?>
                            <form method="post" action="<?= site_url('impersonation/stop-all') ?>">
                                <?= csrf_field() ?>
                                <button class="shell-button shell-button--ghost" type="submit">Return to original account</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php $accessWarning = session()->getFlashdata('access_warning_context'); ?>
            <?php if ($accessWarning === 'grace'): ?>
                <div class="shell-alert shell-alert--warning">
                    <strong>Subscription expired.</strong>
                    Your subscription has expired - you are in a grace period. Please renew to avoid service interruption.
                    <?php if (in_array($roleCode ?? '', ['tenant_owner', 'tenant_admin'])): ?>
                        <a href="<?= site_url('billing') ?>" class="shell-alert__link">Renew now &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php elseif ($accessWarning === 'suspended'): ?>
                <div class="shell-alert shell-alert--warning">
                    <strong>Account suspended.</strong>
                    Read access is available but operational writes are restricted. Contact your administrator.
                </div>
            <?php endif; ?>

            <main class="shell-content">
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>
    <script>
    (() => {
        const body = document.body;
        const toggle = document.querySelector('.shell-menu-toggle');
        const overlay = document.querySelector('.shell-overlay');
        const navLinks = document.querySelectorAll('.shell-nav a');
        const mobileBreakpoint = window.matchMedia('(max-width: 920px)');
        const modalOpeners = Array.from(document.querySelectorAll('[data-modal-open]'));
        const modalClosers = Array.from(document.querySelectorAll('[data-modal-close]'));

        if (toggle && overlay) {
            const closeNav = () => {
                body.classList.remove('shell-body--nav-open');
                toggle.setAttribute('aria-expanded', 'false');
            };

            const openNav = () => {
                body.classList.add('shell-body--nav-open');
                toggle.setAttribute('aria-expanded', 'true');
            };

            toggle.addEventListener('click', () => {
                if (!mobileBreakpoint.matches) {
                    return;
                }

                if (body.classList.contains('shell-body--nav-open')) {
                    closeNav();
                } else {
                    openNav();
                }
            });

            overlay.addEventListener('click', closeNav);

            navLinks.forEach((link) => {
                link.addEventListener('click', () => {
                    if (mobileBreakpoint.matches) {
                        closeNav();
                    }
                });
            });

            window.addEventListener('resize', () => {
                if (!mobileBreakpoint.matches) {
                    closeNav();
                }
            });
        }

        const closeModal = (modal) => {
            if (!modal) {
                return;
            }

            modal.hidden = true;
            body.classList.remove('shell-body--modal-open');
        };

        modalOpeners.forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.getAttribute('data-modal-open'));
                if (!modal) {
                    return;
                }

                modal.hidden = false;
                body.classList.add('shell-body--modal-open');
            });
        });

        modalClosers.forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('.action-modal');
                closeModal(modal);
            });
        });

        window.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('.action-modal').forEach((modal) => {
                if (!modal.hidden) {
                    closeModal(modal);
                }
            });
        });
    })();
    </script>
</body>
</html>

