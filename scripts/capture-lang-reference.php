<?php

declare(strict_types=1);

/**
 * Rewrites the table of lang.json strings on
 * docs/site/content/ru/authoring/language-packs.md from UiCopy and the init
 * templates. Run it after adding or renaming an interface string.
 *
 * Usage: php scripts/capture-lang-reference.php [--check]
 *   --check  exit 1 if the page holds an outdated table.
 */

use Tests\Support\LangReference;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (! class_exists(LangReference::class)) {
    fwrite(STDERR, "This script runs in a Docara repository checkout with tests/ and dev dependencies.\n");
    exit(1);
}
$check = in_array('--check', array_slice($argv, 1), true);
$reference = new LangReference($root);
$page = $reference->page();
$updated = $reference->withTable($page);

if ($check) {
    if ($updated !== $page) {
        fwrite(STDERR, LangReference::PAGE . " has an outdated lang.json table; run scripts/capture-lang-reference.php.\n");
        exit(1);
    }
    fwrite(STDOUT, "The lang.json table is current.\n");
    exit(0);
}

file_put_contents($root . '/' . LangReference::PAGE, $updated);
fwrite(STDOUT, sprintf("Wrote %d lang.json strings to %s.\n", count($reference->ids()), LangReference::PAGE));
