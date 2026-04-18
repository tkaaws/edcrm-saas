<?php
$pagination = $pagination ?? null;

if (! is_array($pagination) || ($pagination['total'] ?? 0) === 0) {
    return;
}
?>
<div class="pagination-bar" aria-label="Pagination">
    <div class="pagination-bar__summary">
        <span>Showing <?= esc((string) $pagination['start']) ?>-<?= esc((string) $pagination['end']) ?> of <?= esc((string) $pagination['total']) ?></span>
    </div>

    <form class="pagination-bar__per-page" method="get">
        <?php foreach (($pagination['query'] ?? []) as $key => $value): ?>
            <?php if (is_array($value)): ?>
                <?php foreach ($value as $nestedValue): ?>
                    <input type="hidden" name="<?= esc((string) $key) ?>[]" value="<?= esc((string) $nestedValue) ?>">
                <?php endforeach; ?>
            <?php else: ?>
                <input type="hidden" name="<?= esc((string) $key) ?>" value="<?= esc((string) $value) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <input type="hidden" name="<?= esc((string) $pagination['pageParam']) ?>" value="1">
        <label>
            <span>Rows</span>
            <select name="<?= esc((string) $pagination['perPageParam']) ?>" onchange="this.form.submit()">
                <?php foreach (($pagination['options'] ?? []) as $option): ?>
                    <option value="<?= esc((string) $option) ?>" <?= (int) $pagination['perPage'] === (int) $option ? 'selected' : '' ?>>
                        <?= esc((string) $option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <nav class="pagination-bar__links" aria-label="Page navigation">
        <?php if (! empty($pagination['hasPrev'])): ?>
            <a class="shell-button shell-button--ghost shell-button--sm" href="<?= esc((string) $pagination['prevUrl']) ?>">Previous</a>
        <?php endif; ?>

        <?php foreach (($pagination['links'] ?? []) as $link): ?>
            <a
                class="shell-button shell-button--sm <?= ! empty($link['active']) ? 'shell-button--primary' : 'shell-button--ghost' ?>"
                href="<?= esc((string) $link['url']) ?>"
                <?= ! empty($link['active']) ? 'aria-current="page"' : '' ?>
            >
                <?= esc((string) $link['label']) ?>
            </a>
        <?php endforeach; ?>

        <?php if (! empty($pagination['hasNext'])): ?>
            <a class="shell-button shell-button--ghost shell-button--sm" href="<?= esc((string) $pagination['nextUrl']) ?>">Next</a>
        <?php endif; ?>
    </nav>
</div>
