<?php

$icon = match ($props['type']) {
    'success' => 'check_circle',
    'warning' => 'warning',
    'danger' => 'error',
    'clear' => 'notifications',
    default => 'info',
};
$role = $props['type'] === 'danger' ? 'alert' : 'status';
if ($title === '') {
    // No heading: a one-line notice, the former banner.
    ?><aside data-docara-block="alert" role="<?= $role ?>" class="sf-alert sf-alert--<?= $props['type'] ?> sf-alert--<?= $props['variant'] ?> flex items-start m-bottom-1"><sf-icon icon="<?= $icon ?>" aria-hidden="true"></sf-icon><div class="sf-alert-wrap flex-1"><div class="sf-alert-content"><?= $content ?></div></div></aside><?php

    return;
}
?><section data-docara-block="alert" role="<?= $role ?>" aria-label="<?= $title ?>" class="sf-alert sf-alert--<?= $props['type'] ?> sf-alert--<?= $props['variant'] ?> flex items-start m-bottom-1"><sf-icon icon="<?= $icon ?>" aria-hidden="true"></sf-icon><div class="sf-alert-wrap flex flex-col flex-1"><div class="sf-alert-content flex flex-col flex-1"><div class="sf-alert-text"><?= $title ?></div><div class="sf-alert-supporting-text"><?= $content ?></div></div></div></section>
