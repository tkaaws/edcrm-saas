<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Subscriptions</h2>
            <p class="module-subtitle">All tenant subscriptions across the platform.</p>
        </div>
    </div>

    <!-- ATTACH SUBSCRIPTION FORM -->
    <div class="detail-card" style="margin-bottom:1.5rem">
        <h3 class="detail-card__title">Attach Subscription</h3>
        <form method="post" action="<?= site_url('platform/subscriptions/attach') ?>" class="inline-form" style="flex-wrap:wrap;gap:.75rem">
            <?= csrf_field() ?>
            <select name="tenant_id" class="shell-input" required style="min-width:180px">
                <option value="">— Select tenant —</option>
                <?php foreach ($tenants as $t): ?>
                    <option value="<?= $t->id ?>"><?= esc($t->name) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="plan_id" class="shell-input" required style="min-width:160px">
                <option value="">— Select plan —</option>
                <?php foreach ($plans as $p): ?>
                    <option value="<?= $p->id ?>"><?= esc($p->name) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="billing_cycle" class="shell-input">
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
            <input type="number" name="trial_days" class="shell-input" value="14" min="0" max="90" style="width:90px" placeholder="Trial days">
            <button type="submit" class="shell-button">Attach</button>
        </form>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tenant</th>
                    <th>Plan</th>
                    <th>Cycle</th>
                    <th>Status</th>
                    <th>Starts</th>
                    <th>Renews / Expires</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscriptions)): ?>
                    <tr><td colspan="8" class="empty-state">No subscriptions found.</td></tr>
                <?php endif; ?>
                <?php foreach ($subscriptions as $s): ?>
                    <?php
                        $statusClass = match($s->status) {
                            'active'    => 'status-badge--good',
                            'trial'     => 'status-badge--info',
                            'grace'     => 'status-badge--warning',
                            'suspended' => 'status-badge--warning',
                            default     => 'status-badge--neutral',
                        };
                    ?>
                    <tr>
                        <td><?= $s->id ?></td>
                        <td>
                            <div class="entity-cell">
                                <strong><?= esc($s->tenant_name) ?></strong>
                                <span><?= esc($s->tenant_slug) ?></span>
                            </div>
                        </td>
                        <td><?= esc($s->plan_name) ?> <small class="text-muted">(<?= esc($s->plan_code) ?>)</small></td>
                        <td><?= esc(ucfirst($s->billing_cycle)) ?></td>
                        <td><span class="status-badge <?= $statusClass ?>"><?= esc(ucfirst($s->status)) ?></span></td>
                        <td><?= $s->starts_at ? date('d M Y', strtotime($s->starts_at)) : '—' ?></td>
                        <td>
                            <?php if ($s->status === 'trial' && $s->trial_ends_at): ?>
                                Trial ends <?= date('d M Y', strtotime($s->trial_ends_at)) ?>
                            <?php elseif ($s->renews_at): ?>
                                <?= date('d M Y', strtotime($s->renews_at)) ?>
                            <?php elseif ($s->expires_at): ?>
                                Exp <?= date('d M Y', strtotime($s->expires_at)) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= site_url("platform/subscriptions/{$s->id}") ?>" class="shell-button shell-button--ghost shell-button--sm">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
