<?php

declare(strict_types=1);

$portable = isset($view) ? $view : (object) ($props ?? []);
echo '<aside class="project-notice" data-project-smart="project.notice"><strong>' . $portable->title . '</strong><p>' . $portable->text . '</p></aside>';
