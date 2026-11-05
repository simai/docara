<?php

declare(strict_types=1);

// Canonical artifact ID: project.install-builder.

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = static fn (string $name, string $fallback): string => $escape(is_string($props[$name] ?? null) ? $props[$name] : $fallback);
$title = $escape($props['title'] ?? 'Install Docara');
$package = $escape($props['package'] ?? 'simai/docara');
$version = $escape($props['version'] ?? '^3.0');
$command = "# macOS\ncomposer require '" . $package . ':' . $version . "'";
$ready = $text('status_ready', 'The page only builds the command and never runs it.');
?>
<section class="project-install-builder flex flex-col gap-2 border border-outline-variant radius-surface p-2" data-project-install-builder data-default-os="macOS" data-default-method="Composer" data-default-package="<?= $package ?>" data-default-version="<?= $version ?>" data-text-ready="<?= $ready ?>" data-text-copied="<?= $text('status_copied', 'Command copied.') ?>" data-text-copy-failed="<?= $text('status_copy_failed', 'Select the command and copy it manually.') ?>" data-text-unavailable="<?= $text('command_unavailable', 'Command unavailable: fix the parameters.') ?>" data-text-error-selection="<?= $text('error_selection', 'Choose a supported operating system and installation method.') ?>" data-text-error-input="<?= $text('error_input', 'Check package and version: only safe Composer values are allowed.') ?>" data-state="valid">
    <h2 class="m-0 title-2"><?= $title ?></h2>
    <div class="grid gap-1" data-install-framework-controls><?= $childrenHtml ?></div>
    <div class="flex items-cross-center gap-1"><code data-install-command class="flex-1 min-w-0 overflow-x-auto whitespace-pre-wrap p-1 bg-surface-container radius-1"><?= $escape($command) ?></code><button type="button" data-install-copy class="sf-button sf-button--outline sf-button--size-1 radius-default"><span class="sf-button-text-container"><?= $text('copy_label', 'Copy') ?></span></button></div>
    <p class="m-0 color-on-surface-variant text-small" data-install-status aria-live="polite"><?= $ready ?></p>
</section>
