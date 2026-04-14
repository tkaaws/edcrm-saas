<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Billing plans</h2>
            <p class="module-subtitle">Manage plan catalog, pricing, feature entitlements, and capacity limits.</p>
        </div>
        <a class="shell-button shell-button--primary" href="<?= site_url('platform/plans/create') ?>">Create plan</a>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Code</th>
                    <th>Monthly (₹)</th>
                    <th>Yearly (₹)</th>
                    <th>Users</th>
                    <th>Branches</th>
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
                <?php foreach ($plans as $plan):
                    $db          = db_connect();
                    $monthly     = $db->table('plan_prices')->where('plan_id', $plan->id)->where('billing_cycle', 'monthly')->get()->getRow();
                    $yearly      = $db->table('plan_prices')->where('plan_id', $plan->id)->where('billing_cycle', 'yearly')->get()->getRow();
                    $maxUsers    = $db->table('plan_limits')->where('plan_id', $plan->id)->where('limit_code', 'max_users')->get()->getRow();
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
                            <?= $monthly ? '₹' . number_format($monthly->price_amount / 100, 0) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td>
                            <?= $yearly ? '₹' . number_format($yearly->price_amount / 100, 0) : '<span class="text-muted">—</span>' ?>
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
                        <td class="table-actions">
                            <a href="<?= site_url("platform/plans/{$plan->id}") ?>" class="shell-button shell-button--ghost shell-button--sm">Manage</a>
                            <form method="post" action="<?= site_url("platform/plans/{$plan->id}/delete") ?>" style="display:inline;"
                                  onsubmit="return confirm('Delete plan <?= esc(addslashes($plan->name)) ?>? This cannot be undone.')">
                                <?= csrf_field() ?>
                                <button type="submit" class="shell-button shell-button--danger shell-button--sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
