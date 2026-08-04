<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$title = $escape($props['title'] ?? 'Links');
?>
<nav class="project-footer-links flex flex-wrap items-cross-center gap-2" aria-label="<?= $title ?>" data-docara-smart="project.footer-links" data-project-footer-links>
    <strong><?= $title ?></strong>
    <a href="/ru/">Документация</a>
    <a href="/ru/project-demos/">Компоненты проекта</a>
</nav>
