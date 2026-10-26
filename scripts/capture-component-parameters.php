<?php

declare(strict_types=1);

/**
 * Rewrites the «Параметры» table on every component page from the component
 * catalogue and resources/component-catalog/descriptions/ru.json. Run it after
 * adding, renaming or describing a component parameter.
 *
 * Usage: php scripts/capture-component-parameters.php [--check]
 *   --check  exit 1 if a page holds an outdated table.
 */

use Tests\Support\ComponentParameterReference;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (! class_exists(ComponentParameterReference::class)) {
    fwrite(STDERR, "This script runs in a Docara repository checkout with tests/ and dev dependencies.\n");
    exit(1);
}
$check = in_array('--check', array_slice($argv, 1), true);
$outdated = [];
$reference = new ComponentParameterReference($root);
$pages = $reference->updatedPages();
$pending = $reference->pendingPages();
foreach ($pages as $page => $text) {
    if ((string) file_get_contents($root . '/' . $page) === $text) {
        continue;
    }
    if ($check) {
        $outdated[] = $page;

        continue;
    }
    file_put_contents($root . '/' . $page, $text);
}

if ($check) {
    if ($outdated !== []) {
        fwrite(STDERR, "Outdated component parameter tables; run scripts/capture-component-parameters.php:\n  " . implode("\n  ", $outdated) . "\n");
        exit(1);
    }
    fwrite(STDOUT, sprintf("The component parameter tables are current on %d pages; %d pages are not moved to the page standard yet.\n", count($pages), count($pending)));
    exit(0);
}

fwrite(STDOUT, sprintf("Wrote the component parameter tables on %d pages; not moved yet: %s\n", count($pages), implode(', ', array_map('basename', $pending))));
