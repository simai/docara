<?php
$classes = ['sf-icon', 'sf-icon-loaded', 'sf-icon-' . $props['weight'], 'sf-icon--size-' . $props['size']];
if ($props['family'] === 'rounded') {
    $classes[] = 'sf-icon-rounded';
} elseif ($props['family'] === 'sharp') {
    $classes[] = 'sf-icon-shape';
}
if ($props['filled'] === 'true') {
    $classes[] = 'sf-icon-filled';
}
$accessibility = $props['label'] === ''
    ? ' aria-hidden="true"'
    : ' role="img" aria-label="' . htmlspecialchars($props['label'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '"';
?><span class="docara-icon inline-grid" data-docara-icon-container="<?= $props['container'] ?>" data-docara-icon-variant="<?= $props['variant'] ?>" data-docara-icon-scheme="<?= $props['scheme'] ?>" data-docara-icon-size="<?= $props['size'] ?>"><i class="<?= implode(' ', $classes) ?>"<?= $accessibility ?>><?= $label ?></i></span>
