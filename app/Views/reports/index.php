<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-layout">
    <header class="module-toolbar">
        <div class="report-toolbar">
            <h2 class="module-title">Activity Reports</h2>
            <p class="module-subtitle">Review daily work in one compact view.</p>
        </div>
    </header>

    <section class="detail-card report-card">
        <div class="report-filters__head">
            <div>
                <h3>Filters</h3>
                <p>Choose whose work and which dates you want to review.</p>
            </div>
            <div class="settings-tabs report-scope">
                <div class="settings-tabs__nav settings-tabs__nav--compact">
                <?php if ($canViewSelf): ?>
                    <a class="settings-tabs__button <?= $scope === 'self' ? 'settings-tabs__button--active' : '' ?>" href="<?= site_url('reports?scope=self&from=' . urlencode($fromDate) . '&to=' . urlencode($toDate)) ?>">My Activity</a>
                <?php endif; ?>
                <?php if ($canViewTeam): ?>
                    <a class="settings-tabs__button <?= $scope === 'team' ? 'settings-tabs__button--active' : '' ?>" href="<?= site_url('reports?scope=team&from=' . urlencode($fromDate) . '&to=' . urlencode($toDate)) ?>">Team Activity</a>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <form class="module-form-grid report-filters" method="get" action="<?= site_url('reports') ?>">
            <input type="hidden" name="scope" value="<?= esc($scope) ?>">
            <div>
                <label>From</label>
                <input type="date" name="from" value="<?= esc($fromDate) ?>">
            </div>
            <div>
                <label>To</label>
                <input type="date" name="to" value="<?= esc($toDate) ?>">
            </div>
            <?php if ($scope === 'team' && $canViewTeam): ?>
                <div>
                    <label>Employee</label>
                    <select name="user_id">
                        <option value="">All visible team members</option>
                        <?php foreach ($userOptions as $user): ?>
                            <?php $label = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')); ?>
                            <option value="<?= esc((string) $user->id) ?>" <?= $selectedUserId === (int) $user->id ? 'selected' : '' ?>>
                                <?= esc($label ?: ($user->email ?? 'User #' . $user->id)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="form-actions">
                <button class="shell-button shell-button--ghost" type="submit">Apply filters</button>
            </div>
        </form>
    </section>

    <section class="detail-card report-card">
        <header class="report-section-head report-section-head--table">
            <div>
                <h3>Activity</h3>
                <p><?= esc((string) ($summary['total'] ?? 0)) ?> actions found for the selected filters.</p>
            </div>
        </header>

        <?php if ($activities === []): ?>
            <div class="empty-state">No activity found for the selected filters.</div>
        <?php else: ?>
            <div class="table-card report-table-card">
                <table class="data-table report-data-table">
                    <thead>
                        <tr>
                            <th>Date and time</th>
                            <th>Module</th>
                            <th>Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td>
                                    <div class="report-table-meta">
                                        <strong><?= esc($activity->created_at ? date('d/m/y h:i a', strtotime($activity->created_at)) : '-') ?></strong>
                                        <span><?= esc($activity->actor_display) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-badge--neutral"><?= esc($activity->module_label) ?></span>
                                </td>
                                <td>
                                    <div class="report-table-activity">
                                        <strong><?= esc($activity->display_title) ?></strong>
                                        <span><?= esc($activity->display_summary) ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>
<?= $this->endSection() ?>
