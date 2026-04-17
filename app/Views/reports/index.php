<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-layout">
    <header class="module-toolbar">
        <div class="report-toolbar">
            <h2 class="module-title">Activity Reports</h2>
            <p class="module-subtitle">Review daily work clearly across enquiries, follow-ups, people, and settings changes.</p>
        </div>
    </header>

    <section class="detail-card report-card">
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

    <section class="stats-grid report-stats-grid">
        <article class="stat-card report-stat-card">
            <span>Total actions</span>
            <strong><?= esc((string) ($summary['total'] ?? 0)) ?></strong>
            <small>Everything recorded in the selected period.</small>
        </article>
        <article class="stat-card report-stat-card">
            <span>Enquiry work</span>
            <strong><?= esc((string) ($summary['enquiries'] ?? 0)) ?></strong>
            <small>Lead updates, closures, assignments, and edits.</small>
        </article>
        <article class="stat-card report-stat-card">
            <span>Follow-ups</span>
            <strong><?= esc((string) ($summary['followups'] ?? 0)) ?></strong>
            <small>Follow-up creation, edits, and removals.</small>
        </article>
        <article class="stat-card report-stat-card">
            <span>People / config</span>
            <strong><?= esc((string) (($summary['people'] ?? 0) + ($summary['settings'] ?? 0))) ?></strong>
            <small>Users, branches, colleges, and settings changes.</small>
        </article>
    </section>

    <section class="detail-card report-card">
        <header class="report-section-head">
            <div>
                <h3>Activity timeline</h3>
                <p>Each entry shows what changed, who changed it, and when it happened.</p>
            </div>
        </header>

        <?php if ($activities === []): ?>
            <div class="empty-state">No activity found for the selected filters.</div>
        <?php else: ?>
            <div class="timeline-list timeline-list--compact">
                <?php foreach ($activities as $activity): ?>
                    <article class="timeline-item timeline-item--history">
                        <div class="timeline-item__marker"></div>
                        <div class="timeline-item__content">
                            <div class="timeline-item__stamp"><?= esc($activity->created_at ? date('d/m/y h:i a', strtotime($activity->created_at)) : '-') ?></div>
                            <div class="timeline-item__card">
                                <div class="timeline-item__header">
                                    <div>
                                        <h4><?= esc($activity->display_title) ?></h4>
                                        <p><?= esc($activity->actor_display) ?> • <?= esc($activity->module_label) ?></p>
                                    </div>
                                </div>
                                <?php if (! empty($activity->changes)): ?>
                                    <div class="history-change-list">
                                        <?php foreach ($activity->changes as $change): ?>
                                            <div class="history-change-list__item">
                                                <strong><?= esc($change->field) ?></strong>
                                                <span><?= esc($change->old_value) ?></span>
                                                <em>&rarr;</em>
                                                <span><?= esc($change->new_value) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="module-subtitle report-card__summary"><?= esc($activity->summary ?: 'Activity recorded') ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
<?= $this->endSection() ?>
