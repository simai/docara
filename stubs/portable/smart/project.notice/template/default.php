<?php

declare(strict_types=1);

$title = htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = htmlspecialchars((string) ($props['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
echo '<aside class="project-notice" data-project-smart="project.notice"><strong>' . $title . '</strong><p>' . $text . '</p></aside>';
