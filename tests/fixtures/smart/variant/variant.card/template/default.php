<?php

declare(strict_types=1);

$title = htmlspecialchars((string) ($props['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$viewCode = htmlspecialchars((string) ($view['code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
echo '<article data-variant-card data-variant-view="' . $viewCode . '"><strong>' . $title . '</strong></article>';
