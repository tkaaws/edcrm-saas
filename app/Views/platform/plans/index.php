<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Billing plans</h2>
            <p class="module-subtitle">Manage plan catalog, pricing, feature entitlements, and capacity limits.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="shell-alert shell-alert--success"><?= esc(session()->getFlashdata('message')) ?></div>
    <?php endif; ?>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Code</th>
                    <th>Monthly (INR)</th>
                    <th>Yearly (INR)</th>
                    <th>Max users</th>
                    <th>Max branches</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plans)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">No plans found.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($plans as $plan): ?>
                    <?php
                        $db       = db_connect();
                        $monthly  = $db->table('plan_prices')->where('plan_id', $plan->id)->where('billing_cycle', 'monthly')->get()->getRow();
                        $yearly   = $db->table('plan_prices')->where('plan_id', $plan->id)->where('billing_cycle', 'yearly')->get()->getRow();
                        $maxUsers = $db->table('plan_limits')->where('plan_id', $plan->id)->where('limit_code', 'max_users')->get()->getRow();
                        $maxBranches = $db->table('plan_limits')->where('plan_id', $plan->id)->where('limit_code', 'max_branches')->get()->getRow();
                    ?>
                    <tr>
                        <td>
                            <div class="entity-cell">
                                <strong><?= esc($plan->name) ?></strong>
                                <span><?= esc($plan->description ?: '—') ?></span>
                            </div>
                        </td>
                        <td><code><?= esc($plan->code) ?></code></td>
                        <td>
                            <?php if ($monthly): ?>
                                ₹<?= number_format($monthly->price_amount / 100, 0) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($yearly): ?>
                                ₹<?= number_format($yearly->price_amount / 100, 0) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $maxUsers ? ($maxUsers->limit_value == -1 ? '∞' : $maxUsers->limit_value) : '—' ?></td>
                        <td><?= $maxBranches ? ($maxBranches->limit_value == -1 ? '∞' : $maxBranches->limit_value) : '—' ?></td>
                        <td>
                            <span class="status-badge <?= $plan->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                <?= esc(ucfirst($plan->status)) ?>
                            </span>
                            <?php if (! $plan->is_public): ?>
                                <span class="status-badge status-badge--neutral">Private</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= site_url("platform/plans/{$plan->id}") ?>" class="shell-button shell-button--ghost shell-button--sm">Manage</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
