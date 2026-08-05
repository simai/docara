<?php

declare(strict_types=1);

// Canonical artifact ID: project.install-builder.

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$title = $escape($props['title'] ?? 'Install Docara');
$package = $escape($props['package'] ?? 'simai/docara');
$version = $escape($props['version'] ?? '^2.0');
$command = "# macOS\ncomposer require '" . $package . ':' . $version . "'";
?>
<section class="project-install-builder flex flex-col gap-2 border border-outline-variant radius-2 p-2" data-project-install-builder data-default-os="macOS" data-default-method="Composer" data-default-package="<?= $package ?>" data-default-version="<?= $version ?>" data-state="valid">
    <h2 class="m-0 title-2"><?= $title ?></h2>
    <div class="grid gap-1" data-install-framework-controls><?= $childrenHtml ?></div>
    <div class="flex items-cross-center gap-1"><code data-install-command class="flex-1 min-w-0 overflow-x-auto whitespace-pre-wrap p-1 bg-surface-container radius-1"><?= $escape($command) ?></code><button type="button" data-install-copy class="sf-button sf-button--outline sf-button--size-1 radius-default"><span class="sf-button-text-container">Копировать</span></button></div>
    <p class="m-0 color-on-surface-variant text-small" data-install-status aria-live="polite">Команда только формируется и никогда не выполняется страницей.</p>
</section>
