<?php
$classes = ['sf-icon', 'sf-icon-loaded', 'sf-icon-' . $props['weight']];
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
// An icon in a square or a circle is the Framework featured icon: its size,
// its plate and its glyph colour are the Framework's. The Framework has a
// tonal and a solid look and the primary, neutral and status tones; an
// outlined container and the secondary and tertiary tones stay Docara's
// own composition until it has them.
$tones = ['primary' => 'primary', 'neutral' => 'neutral', 'on-surface' => 'neutral', 'info' => 'info', 'success' => 'success', 'warning' => 'warning', 'danger' => 'error'];
$looks = ['tonal' => 'soft', 'main' => 'solid'];
if ($props['container'] !== 'none' && isset($tones[$props['scheme']], $looks[$props['variant']])) {
    $featured = ['sf-featured-icon', 'sf-featured-icon--size-' . $props['size'], 'sf-featured-icon--' . $props['container'], 'sf-featured-icon--' . $tones[$props['scheme']], 'sf-featured-icon--' . $looks[$props['variant']]];
    ?><span class="<?= implode(' ', $featured) ?>" data-docara-icon-container="<?= $props['container'] ?>" data-docara-icon-variant="<?= $props['variant'] ?>" data-docara-icon-scheme="<?= $props['scheme'] ?>" data-docara-icon-size="<?= $props['size'] ?>"><i class="<?= implode(' ', $classes) ?>"<?= $accessibility ?>><?= $label ?></i></span><?php
} else {
    $classes[] = 'sf-icon--size-' . $props['size'];
    ?><span class="docara-icon inline-grid" data-docara-icon-container="<?= $props['container'] ?>" data-docara-icon-variant="<?= $props['variant'] ?>" data-docara-icon-scheme="<?= $props['scheme'] ?>" data-docara-icon-size="<?= $props['size'] ?>"><i class="<?= implode(' ', $classes) ?>"<?= $accessibility ?>><?= $label ?></i></span><?php
}
