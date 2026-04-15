<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="dashboard-grid">
    <div class="metrics-grid">
        <article class="metric-card">
            <p class="metric-card__eyebrow">Institute</p>
            <p class="metric-card__value"><?= esc($tenantLabel ?? 'Not resolved') ?></p>
            <p class="metric-card__caption">Your current workspace.</p>
        </article>

        <article class="metric-card">
            <p class="metric-card__eyebrow">Current branch</p>
            <p class="metric-card__value"><?= esc($branchLabel ?? 'Not assigned') ?></p>
            <p class="metric-card__caption">The branch you are working in right now.</p>
        </article>

        <article class="metric-card">
            <p class="metric-card__eyebrow">Access profile</p>
            <p class="metric-card__value"><?= esc($roleLabel ?: 'Pending') ?></p>
            <p class="metric-card__caption">What you are allowed to do in this workspace.</p>
        </article>

        <article class="metric-card">
            <p class="metric-card__eyebrow">Signed in</p>
            <p class="metric-card__value">Yes</p>
            <p class="metric-card__caption"><?= esc($userEmail ?? '') ?></p>
        </article>
    </div>

    <div class="panel-grid">
        <article class="panel-card">
            <h2>Workspace setup</h2>
            <ul class="module-list">
                <li>
                    <div>
                        <strong>Sign-in and account access</strong>
                        <div class="module-list__meta">People can sign in and reach the workspace securely.</div>
                    </div>
                    <span class="status-badge status-badge--good">Ready</span>
                </li>
                <li>
                    <div>
                        <strong>Institute and branch setup</strong>
                        <div class="module-list__meta">Institute context, branch routing, and access boundaries are in place.</div>
                    </div>
                    <span class="status-badge status-badge--good">Ready</span>
                </li>
                <li>
                    <div>
                        <strong>Workspace layout</strong>
                        <div class="module-list__meta">Menu, navigation, and page structure are available.</div>
                    </div>
                    <span class="status-badge status-badge--good">Ready</span>
                </li>
                <li>
                    <div>
                        <strong>Team, branches, access profiles, and settings</strong>
                        <div class="module-list__meta">Core setup screens are available and being refined for day-to-day use.</div>
                    </div>
                    <span class="status-badge status-badge--warm">In review</span>
                </li>
                <li>
                    <div>
                        <strong>Institute onboarding</strong>
                        <div class="module-list__meta">New institutes can be created and activated by the EDCRM team.</div>
                    </div>
                    <span class="status-badge status-badge--good">Ready</span>
                </li>
                <li>
                    <div>
                        <strong>Plan and usage</strong>
                        <div class="module-list__meta">Subscription, included features, and usage limits are visible.</div>
                    </div>
                    <span class="status-badge status-badge--neutral">Available</span>
                </li>
            </ul>
        </article>

        <aside class="list-card">
            <h2>Current account</h2>
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
