<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="dashboard-grid">
    <div class="metrics-grid">
        <article class="metric-card">
            <p class="metric-card__eyebrow">Institute</p>
            <p class="metric-card__value"><?= esc($tenantLabel ?? 'Not resolved') ?></p>
            <p class="metric-card__caption">Current tenant resolved into session.</p>
        </article>

        <article class="metric-card">
            <p class="metric-card__eyebrow">Active branch</p>
            <p class="metric-card__value"><?= esc($branchLabel ?? 'Not assigned') ?></p>
            <p class="metric-card__caption">Primary branch for branch-aware operations.</p>
        </article>

        <article class="metric-card">
            <p class="metric-card__eyebrow">Role</p>
            <p class="metric-card__value"><?= esc($roleLabel ?: 'Pending') ?></p>
            <p class="metric-card__caption">Your access level for this tenant.</p>
        </article>

        <article class="metric-card">
            <p class="metric-card__eyebrow">Session</p>
            <p class="metric-card__value">Active</p>
            <p class="metric-card__caption"><?= esc($userEmail ?? '') ?></p>
        </article>
    </div>

    <div class="panel-grid">
        <article class="panel-card">
            <h2>Phase 1A status</h2>
            <ul class="module-list">
                <li>
                    <div>
                        <strong>Authentication</strong>
                        <div class="module-list__meta">Login, logout, password reset, password history, audit log</div>
                    </div>
                    <span class="status-badge status-badge--good">Done</span>
                </li>
                <li>
                    <div>
                        <strong>Multi-tenant foundation</strong>
                        <div class="module-list__meta">Tenant scoping, session context, branch resolution</div>
                    </div>
                    <span class="status-badge status-badge--good">Done</span>
                </li>
                <li>
                    <div>
                        <strong>Admin shell</strong>
                        <div class="module-list__meta">Layout, sidebar, platform admin isolation</div>
                    </div>
                    <span class="status-badge status-badge--good">Done</span>
                </li>
                <li>
                    <div>
                        <strong>Users / Branches / Roles / Settings</strong>
                        <div class="module-list__meta">CRUD and assignment flows wired</div>
                    </div>
                    <span class="status-badge status-badge--warm">Validate</span>
                </li>
                <li>
                    <div>
                        <strong>Platform tenant onboarding</strong>
                        <div class="module-list__meta">Provisioning, validation, status management</div>
                    </div>
                    <span class="status-badge status-badge--good">Done</span>
                </li>
                <li>
                    <div>
                        <strong>Billing and subscriptions</strong>
                        <div class="module-list__meta">Plans, entitlements, usage limits</div>
                    </div>
                    <span class="status-badge status-badge--neutral">Phase 1B</span>
                </li>
            </ul>
        </article>

        <aside class="list-card">
            <h2>Session context</h2>
            <ul>
                <li>
                    <span>User</span>
                    <span><?= esc(trim($userDisplayName ?? $firstName ?? 'User')) ?></span>
                </li>
                <li>
                    <span>Email</span>
                    <span><?= esc($userEmail ?? '') ?></span>
                </li>
                <li>
                    <span>Institute</span>
                    <span><?= esc($tenantLabel ?? '') ?></span>
                </li>
                <li>
                    <span>Branch</span>
                    <span><?= esc($branchLabel ?? '') ?></span>
                </li>
                <li>
                    <span>Role</span>
                    <span><?= esc($roleLabel ?? '') ?></span>
                </li>
            </ul>
        </aside>
    </div>
</section>
<?= $this->endSection() ?>
