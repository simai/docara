<?php

declare(strict_types=1);

$portable = isset($view) ? $view : (object) ($props ?? []);
echo '<aside data-fixture-notice><strong>' . $portable->title . '</strong><p>' . $portable->text . '</p></aside>';
