<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="dashboard-grid">
    <div class="metrics-grid">
        <article class="metric-card">
            <p class="metric-card__eyebrow">Tenant context</p>
            <p class="metric-card__value"><?= esc((string) ($tenantId ?? '0')) ?></p>
            <p class="metric-card__caption">Current tenant resolved into session.</p>
        </article>

        <article class="metric-card">
            <p class="metric-card__eyebrow">Branch context</p>
            <p class="metric-card__value"><?= esc((string) ($branchId ?? '0')) ?></p>
            <p class="metric-card__caption">Primary branch available for branch-aware modules.</p>
        </article>

        <article class="metric-card">
            <p class="metric-card__eyebrow">Role code</p>
            <p class="metric-card__value"><?= esc($roleCode ?: 'Pending') ?></p>
            <p class="metric-card__caption">Used by privilege resolution and menu gating.</p>
        </article>

        <article class="metric-card">
            <p class="metric-card__eyebrow">Deployment</p>
            <p class="metric-card__value">Live</p>
            <p class="metric-card__caption">GitHub Actions and droplet auto-deploy are active.</p>
        </article>
    </div>

    <div class="panel-grid">
        <article class="panel-card">
            <h2>Foundation snapshot</h2>
            <p>
                The base multi-tenant shell is now in place. This dashboard is the anchor
                for the next implementation blocks: users, branches, roles, and tenant settings.
            </p>

            <ul class="module-list">
                <li>
                    <div>
                        <strong>Authentication</strong>
                        <div class="module-list__meta">Login, logout, password reset, session loading</div>
                    </div>
                    <span class="status-badge status-badge--good">Ready</span>
                </li>
                <li>
                    <div>
                        <strong>Tenant and branch context</strong>
                        <div class="module-list__meta">Session-based tenant and branch resolution</div>
                    </div>
                    <span class="status-badge status-badge--good">Ready</span>
                </li>
                <li>
                    <div>
                        <strong>Admin shell</strong>
                        <div class="module-list__meta">Responsive base layout for all secure modules</div>
                    </div>
                    <span class="status-badge status-badge--warm">In Progress</span>
                </li>
                <li>
                    <div>
                        <strong>Operational modules</strong>
                        <div class="module-list__meta">Users, branches, roles, settings, enquiry, admissions</div>
                    </div>
                    <span class="status-badge status-badge--neutral">Next</span>
                </li>
            </ul>
        </article>

        <aside class="list-card">
            <h2>Current runtime</h2>
            <ul>
                <li>
                    <span>User</span>
                    <span><?= esc($firstName ?: 'User') ?></span>
                </li>
                <li>
                    <span>Email</span>
                    <span><?= esc((string) (session()->get('user_email') ?? '')) ?></span>
                </li>
                <li>
                    <span>Tenant</span>
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

    <div class="panel-grid">
        <article class="panel-card">
            <h2>Execution lane</h2>
            <ul class="timeline-list">
                <li>
                    <strong>1. Admin shell</strong>
                    <div>Responsive layout, sidebar, topbar, and status messages are in place.</div>
                </li>
                <li>
                    <strong>2. User management</strong>
                    <div>Create the first CRUD flow on top of this shell.</div>
                </li>
                <li>
                    <strong>3. Branch and role management</strong>
                    <div>Complete core tenant operations before module work begins.</div>
                </li>
            </ul>
        </article>

        <article class="list-card">
            <h2>What comes next</h2>
            <p>
                This shell is intentionally simple. It gives us one consistent frame for
                permissions, branch switching, alerts, and later module navigation.
            </p>
            <ul>
                <li>
                    <span>User CRUD</span>
                    <span>Next build</span>
                </li>
                <li>
                    <span>Branch CRUD</span>
                    <span>Queued</span>
                </li>
                <li>
                    <span>Role CRUD</span>
                    <span>Queued</span>
                </li>
                <li>
                    <span>Tenant settings</span>
                    <span>Queued</span>
                </li>
            </ul>
        </article>
    </div>
</section>
<?= $this->endSection() ?>
