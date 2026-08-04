<?php

declare(strict_types=1);

// Canonical artifact ID: project.product-configurator.

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$title = $escape($props['title'] ?? 'Configuration');
$price = is_int($props['base_price'] ?? null) || is_float($props['base_price'] ?? null) ? (float) $props['base_price'] : 0.0;
$currency = $escape($props['currency'] ?? '₽');
?>
<section class="project-product-configurator flex flex-col gap-2 border border-outline-variant radius-2 p-2" data-project-product-configurator data-base-price="<?= $price ?>" data-currency="<?= $currency ?>">
    <h2 class="m-0 title-2"><?= $title ?></h2>
    <fieldset class="m-0 p-0 border-none flex flex-col gap-1"><legend class="weight-6">Возможности</legend><label class="flex items-cross-center gap-1"><input type="checkbox" data-config-option value="1200">Расширенная аналитика</label><label class="flex items-cross-center gap-1"><input type="checkbox" data-config-option value="800">Командная работа</label><label class="flex items-cross-center gap-1"><input type="checkbox" data-config-option value="500">Экспорт данных</label></fieldset>
    <p class="m-0">Итог примера: <strong data-config-total><?= number_format($price, 0, '.', ' ') ?> <?= $currency ?></strong></p>
    <p class="m-0 color-on-surface-variant text-small">Это демонстрация интерфейса: она не создаёт заказ, не принимает оплату и не отправляет данные.</p>
</section>
