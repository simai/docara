<?php

declare(strict_types=1);

/**
 * Rewrites the list of every icon name on docs/site/content/ru/components/icon.md
 * from the bundled icon catalogue. Run it after regenerating the catalogue with
 * scripts/build-icon-catalog.cjs.
 *
 * Usage: php scripts/capture-icon-names.php [--check]
 *   --check  exit 1 if the page holds an outdated list.
 */

use Tests\Support\IconNameReference;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (! class_exists(IconNameReference::class)) {
    fwrite(STDERR, "This script runs in a Docara repository checkout with tests/ and dev dependencies.\n");
    exit(1);
}
$check = in_array('--check', array_slice($argv, 1), true);
$reference = new IconNameReference($root);
$page = $reference->page();
$updated = $reference->withList($page);

if ($check) {
    if ($updated !== $page) {
        fwrite(STDERR, IconNameReference::PAGE . " has an outdated icon list; run scripts/capture-icon-names.php.\n");
        exit(1);
    }
    fwrite(STDOUT, "The icon list is current.\n");
    exit(0);
}

file_put_contents($root . '/' . IconNameReference::PAGE, $updated);
fwrite(STDOUT, sprintf("Wrote %d icon names to %s.\n", count($reference->names()), IconNameReference::PAGE));
