<?php
$admissionsSubnav = $admissionsSubnav ?? 'admissions';
$navItems = [
    'admissions' => [
        'label' => 'Admissions',
        'url' => site_url('admissions'),
        'show' => service('permissions')->has('admissions.view') || service('permissions')->has('admissions.create'),
    ],
    'fee_structures' => [
        'label' => 'Fee Structures',
        'url' => site_url('admissions/fee-structures'),
        'show' => service('permissions')->has('fees.view') || service('permissions')->has('fees.structure'),
    ],
];
?>
<nav class="queue-nav" aria-label="Admissions navigation">
    <div class="queue-nav__group">
        <?php foreach ($navItems as $code => $item): ?>
            <?php if (! $item['show']) continue; ?>
            <a class="queue-nav__link <?= $admissionsSubnav === $code ? 'queue-nav__link--active' : '' ?>" href="<?= esc($item['url']) ?>">
                <?= esc($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
