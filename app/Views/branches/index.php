<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php $codes = session()->get('user_privilege_codes') ?? []; ?>
    <?php $canCreateBranches = in_array('branches.create', $codes, true); ?>
    <?php $canEditBranches = in_array('branches.edit', $codes, true); ?>
    <?php $editableBranchesById = $editableBranchesById ?? []; ?>
    <div class="module-toolbar module-toolbar--compact">
        <div class="module-toolbar__copy">
            <p class="module-subtitle">Manage company locations and regional defaults.</p>
        </div>
        <?php if ($canCreateBranches): ?>
            <button class="shell-button shell-button--primary" type="button" data-modal-open="branch-create-modal">Add branch</button>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table data-table--cards">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Branch code</th>
                        <th>City</th>
                        <th>Timezone</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th class="data-table__actions">Quick actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($branches === []): ?>
                        <tr>
                            <td colspan="7" class="empty-state">No branches yet. Add the first branch for this company.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td data-label="Branch">
                                <div class="entity-cell">
                                    <strong><?= esc($branch->name) ?></strong>
                                    <span><?= esc($branch->type ?: 'General branch') ?></span>
                                </div>
                            </td>
                            <td data-label="Branch code"><?= esc($branch->code) ?></td>
                            <td data-label="City"><?= esc($branch->city ?: 'Not set') ?></td>
                            <td data-label="Timezone"><?= esc($branch->timezone ?: 'Company default') ?></td>
                            <td data-label="Currency"><?= esc($branch->currency_code ?: 'Company default') ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?= $branch->status === 'active' ? 'status-badge--good' : 'status-badge--neutral' ?>">
                                    <?= esc(ucfirst($branch->status)) ?>
                                </span>
                            </td>
                            <td class="data-table__actions" data-label="Actions">
                                <div class="table-actions">
                                    <?php if ($canEditBranches): ?>
                                        <a class="shell-button shell-button--ghost shell-button--sm" href="<?= site_url('branches/' . $branch->id . '/settings') ?>">Settings</a>
                                        <?php $editBranch = $editableBranchesById[(int) $branch->id] ?? $branch; ?>
                                        <button
                                            class="shell-button shell-button--ghost shell-button--sm"
                                            type="button"
                                            data-modal-open="branch-edit-modal-<?= (int) $branch->id ?>"
                                            data-edit-branch
                                            data-branch-id="<?= (int) $branch->id ?>"
                                            data-name="<?= esc($editBranch->name ?? '', 'attr') ?>"
                                            data-code="<?= esc($editBranch->code ?? '', 'attr') ?>"
                                            data-city="<?= esc($editBranch->city ?? '', 'attr') ?>"
                                            data-type="<?= esc($editBranch->type ?? '', 'attr') ?>"
                                            data-status="<?= esc($editBranch->status ?? 'active', 'attr') ?>"
                                            data-address-line-1="<?= esc($editBranch->address_line_1 ?? '', 'attr') ?>"
                                            data-state-code="<?= esc($editBranch->state_code ?? '', 'attr') ?>"
                                            data-timezone="<?= esc($editBranch->timezone ?? '', 'attr') ?>"
                                            data-currency-code="<?= esc($editBranch->currency_code ?? '', 'attr') ?>"
                                        >Edit</button>
                                        <form method="post" action="<?= site_url('branches/' . $branch->id . '/status') ?>">
                                            <?= csrf_field() ?>
                                            <button class="shell-button shell-button--soft shell-button--sm" type="submit">
                                                <?= $branch->status === 'active' ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php if ($canCreateBranches): ?>
    <div class="action-modal" id="branch-create-modal" hidden>
        <div class="action-modal__backdrop" data-modal-close></div>
        <div class="action-modal__dialog action-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="branch-create-modal-title">
            <div class="action-modal__header">
                <div>
                    <h3 id="branch-create-modal-title">Add branch</h3>
                    <p>Create a branch without leaving the branch list.</p>
                </div>
                <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form class="form-stack" method="post" action="<?= site_url('branches') ?>">
                <?= csrf_field() ?>
                <?php $formBranch = null; $useOldInput = true; ?>
                <?= $this->include('branches/_form_sections') ?>
                <div class="form-actions">
                    <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                    <button class="shell-button shell-button--primary" type="submit">Create branch</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($branches as $branch): ?>
    <?php if ($canEditBranches): ?>
        <?php $editBranch = $editableBranchesById[(int) $branch->id] ?? $branch; ?>
        <div class="action-modal" id="branch-edit-modal-<?= (int) $branch->id ?>" hidden>
            <div class="action-modal__backdrop" data-modal-close></div>
            <div class="action-modal__dialog action-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="branch-edit-modal-title-<?= (int) $branch->id ?>">
                <div class="action-modal__header">
                    <div>
                        <h3 id="branch-edit-modal-title-<?= (int) $branch->id ?>">Edit branch</h3>
                        <p>Update the branch details without leaving the branch list.</p>
                    </div>
                    <button class="action-modal__close" type="button" data-modal-close aria-label="Close">&times;</button>
                </div>
                <form class="form-stack" method="post" action="<?= site_url('branches/' . $branch->id) ?>">
                    <?= csrf_field() ?>
                    <?php $formBranch = $editBranch; $useOldInput = false; ?>
                    <?= $this->include('branches/_form_sections') ?>
                    <div class="form-actions">
                        <button class="shell-button shell-button--ghost" type="button" data-modal-close>Cancel</button>
                        <button class="shell-button shell-button--primary" type="submit">Save branch</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
<script>
(() => {
    const fillField = (scope, name, value) => {
        const field = scope.querySelector(`[name="${name}"]`);
        if (!field) {
            return;
        }

        field.value = value ?? '';
    };

    document.querySelectorAll('[data-edit-branch]').forEach((button) => {
        button.addEventListener('click', () => {
            const branchId = button.getAttribute('data-branch-id');
            const modal = branchId ? document.getElementById(`branch-edit-modal-${branchId}`) : null;
            if (!modal) {
                return;
            }

            fillField(modal, 'name', button.getAttribute('data-name'));
            fillField(modal, 'code', button.getAttribute('data-code'));
            fillField(modal, 'city', button.getAttribute('data-city'));
            fillField(modal, 'type', button.getAttribute('data-type'));
            fillField(modal, 'status', button.getAttribute('data-status'));
            fillField(modal, 'address_line_1', button.getAttribute('data-address-line-1'));
            fillField(modal, 'state_code', button.getAttribute('data-state-code'));
            fillField(modal, 'timezone', button.getAttribute('data-timezone'));
            fillField(modal, 'currency_code', button.getAttribute('data-currency-code'));
        });
    });
})();
</script>
<?= $this->endSection() ?>
