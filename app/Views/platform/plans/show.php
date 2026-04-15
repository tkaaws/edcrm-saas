<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title"><?= esc($plan->name) ?></h2>
            <p class="module-subtitle"><?= esc($plan->description ?: 'No description.') ?></p>
        </div>
        <a href="<?= site_url('platform/plans') ?>" class="shell-button shell-button--ghost">← All plans</a>
    </div>

    <div class="detail-grid">

        <!-- PRICING -->
        <div class="detail-card">
            <h3 class="detail-card__title">Plan price</h3>
            <?php
                $monthlyPrice = null;
                $yearlyPrice  = null;
                foreach ($plan->prices as $p) {
                    if ($p->billing_cycle === 'monthly') $monthlyPrice = $p;
                    if ($p->billing_cycle === 'yearly')  $yearlyPrice = $p;
                }
            ?>

            <div class="detail-card__row">
                <strong>Monthly</strong>
                <span><?= $monthlyPrice ? '₹' . number_format($monthlyPrice->price_amount / 100, 0) : '—' ?></span>
                <form method="post" action="<?= site_url("platform/plans/{$plan->id}/price") ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="billing_cycle" value="monthly">
                    <input type="number" name="price_amount" class="shell-input shell-input--sm"
                           value="<?= $monthlyPrice ? (int)($monthlyPrice->price_amount / 100) : '' ?>"
                           placeholder="Amount" min="0" step="1" style="width:110px">
                    <button type="submit" class="shell-button shell-button--sm">Update</button>
                </form>
            </div>

            <div class="detail-card__row">
                <strong>Yearly</strong>
                <span><?= $yearlyPrice ? '₹' . number_format($yearlyPrice->price_amount / 100, 0) : '—' ?></span>
                <form method="post" action="<?= site_url("platform/plans/{$plan->id}/price") ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="billing_cycle" value="yearly">
                    <input type="number" name="price_amount" class="shell-input shell-input--sm"
                           value="<?= $yearlyPrice ? (int)($yearlyPrice->price_amount / 100) : '' ?>"
                           placeholder="Amount" min="0" step="1" style="width:110px">
                    <button type="submit" class="shell-button shell-button--sm">Update</button>
                </form>
            </div>
        </div>

        <!-- LIMITS -->
        <div class="detail-card">
            <h3 class="detail-card__title">Team capacity</h3>
            <?php foreach ($allLimits as $lim): ?>
                <div class="detail-card__row">
                    <strong><?= esc($lim->name) ?></strong>
                    <code><?= esc($lim->code) ?></code>
                    <span class="text-muted">
                        <?php $cur = $limitMap[$lim->code] ?? null; ?>
                        <?= $cur === null ? 'Not set' : ($cur == -1 ? '∞' : $cur) ?>
                    </span>
                    <form method="post" action="<?= site_url("platform/plans/{$plan->id}/limit") ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="limit_code" value="<?= esc($lim->code) ?>">
                        <input type="number" name="limit_value" class="shell-input shell-input--sm"
                               value="<?= isset($limitMap[$lim->code]) ? $limitMap[$lim->code] : '' ?>"
                               placeholder="-1 = unlimited" min="-1" step="1" style="width:100px">
                        <button type="submit" class="shell-button shell-button--sm">Update</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- MODULE FEATURES -->
        <div class="detail-card detail-card--wide">
            <h3 class="detail-card__title">Included module access</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Code</th>
                        <th>Current status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allFeatures as $feat): ?>
                        <?php $enabled = $featureMap[$feat->code] ?? false; ?>
                        <tr>
                            <td><?= esc($feat->name) ?></td>
                            <td><code><?= esc($feat->code) ?></code></td>
                            <td>
                                <span class="status-badge <?= $enabled ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= $enabled ? 'Included' : 'Not included' ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="<?= site_url("platform/plans/{$plan->id}/feature") ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="feature_code" value="<?= esc($feat->code) ?>">
                                    <input type="hidden" name="is_enabled" value="<?= $enabled ? 0 : 1 ?>">
                                    <button type="submit" class="shell-button shell-button--sm <?= $enabled ? 'shell-button--danger' : '' ?>">
                                        <?= $enabled ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</section>
<?= $this->endSection() ?>
