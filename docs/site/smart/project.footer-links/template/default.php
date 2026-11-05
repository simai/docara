<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = static fn (string $name, string $fallback): string => $escape(is_string($props[$name] ?? null) ? $props[$name] : $fallback);
$title = $escape($props['title'] ?? 'Links');
?>
<nav class="project-footer-links flex flex-wrap items-cross-center gap-2" aria-label="<?= $title ?>" data-docara-smart="project.footer-links" data-project-footer-links>
    <strong><?= $title ?></strong>
    <a href="../"><?= $text('docs_link', 'Documentation') ?></a>
    <a href="./"><?= $text('components_link', 'Project components') ?></a>
</nav>
