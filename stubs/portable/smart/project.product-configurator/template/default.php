<?php

declare(strict_types=1);

// Canonical artifact ID: project.product-configurator.

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = static fn (string $name, string $fallback): string => $escape(is_string($props[$name] ?? null) ? $props[$name] : $fallback);
$title = $escape($props['title'] ?? 'Configuration');
$price = is_int($props['base_price'] ?? null) || is_float($props['base_price'] ?? null) ? (float) $props['base_price'] : 0.0;
$teamPrice = is_int($props['team_price'] ?? null) || is_float($props['team_price'] ?? null) ? (float) $props['team_price'] : 4500.0;
$businessPrice = is_int($props['business_price'] ?? null) || is_float($props['business_price'] ?? null) ? (float) $props['business_price'] : 8000.0;
$currency = $escape($props['currency'] ?? '₽');
// Plan ids follow the order of the view's dropdown options, so the script maps a choice to a price in any language.
$variants = is_array($view['children']['product']['children'] ?? null) ? $view['children']['product']['children'] : [];
$variantKeys = implode(' ', array_map('strval', array_keys($variants)));
$starter = $variants['starter']['props']['text'] ?? 'Starter';
$starter = $escape(is_string($starter) ? $starter : 'Starter');
$noOptions = $text('no_options', 'no extra options');
?>
<section class="project-product-configurator flex flex-col gap-2 border border-outline-variant radius-surface p-2" data-project-product-configurator data-starter-price="<?= $price ?>" data-team-price="<?= $teamPrice ?>" data-business-price="<?= $businessPrice ?>" data-default-variant="<?= $starter ?>" data-variant-keys="<?= $escape($variantKeys) ?>" data-currency="<?= $currency ?>" data-text-no-options="<?= $noOptions ?>" data-text-unavailable="<?= $text('unavailable', 'Unavailable') ?>" data-text-error-variant="<?= $text('error_variant', 'Choose a supported product plan.') ?>" data-text-error-option="<?= $text('error_option', 'The configuration options are damaged.') ?>" data-state="valid">
    <h2 class="m-0 title-2"><?= $title ?></h2>
    <fieldset class="m-0 p-0 border-none flex flex-col gap-1"><legend class="weight-6"><?= $text('legend', 'Plan and options') ?></legend><?= $childrenHtml ?></fieldset>
    <p class="m-0"><?= $text('selected_label', 'Selected:') ?> <strong data-config-summary><?= $starter ?> — <?= $noOptions ?></strong></p>
    <p class="m-0"><?= $text('total_label', 'Sample total:') ?> <strong data-config-total><?= number_format($price, 0, '.', ' ') ?> <?= $currency ?></strong></p>
    <p class="m-0 color-on-surface-variant text-small"><?= $text('disclaimer', 'This is an interface demo: it creates no order, takes no payment and sends no data.') ?></p>
</section>
