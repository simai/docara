<?php

declare(strict_types=1);

$title = htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = htmlspecialchars((string) ($props['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$viewCode = htmlspecialchars((string) ($view['code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$presetCode = htmlspecialchars((string) ($preset['code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
echo '<aside data-fixture-notice data-view="' . $viewCode . '" data-preset="' . $presetCode . '"><strong>' . $title . '</strong><p>' . $text . '</p></aside>';
