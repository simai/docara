<?php

declare(strict_types=1);

/**
 * Writes docs/diagnostic-code-coverage.json: how many diagnostic codes in src/
 * have a cause-and-fix entry on the error reference pages
 * (docs/site/content/ru/reference/errors*), and the list of codes that do not
 * have one yet. Run it after adding a code or documenting one.
 *
 * Usage: php scripts/capture-diagnostic-code-coverage.php [--check]
 *   --check  exit 1 if the committed file differs from a fresh capture.
 */

use Tests\Support\DiagnosticCodeInventory;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (! class_exists(DiagnosticCodeInventory::class)) {
    fwrite(STDERR, "This script runs in a Docara repository checkout with tests/ and dev dependencies.\n");
    exit(1);
}
$check = in_array('--check', array_slice($argv, 1), true);
$inventory = new DiagnosticCodeInventory($root);
$coverage = $inventory->coverage();
$encoded = json_encode($coverage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
$path = $root . '/' . DiagnosticCodeInventory::COVERAGE_FILE;

if ($check) {
    if (! is_file($path) || file_get_contents($path) !== $encoded) {
        fwrite(STDERR, DiagnosticCodeInventory::COVERAGE_FILE . " is out of date; run scripts/capture-diagnostic-code-coverage.php.\n");
        exit(1);
    }
    fwrite(STDOUT, sprintf("Diagnostic code coverage is current: %d of %d codes documented.\n", $coverage['codes']['documented'], $coverage['codes']['in_source']));
    exit(0);
}

file_put_contents($path, $encoded);
fwrite(STDOUT, sprintf(
    "Documented %d of %d codes and %d of %d verify-static markers.\n",
    $coverage['codes']['documented'],
    $coverage['codes']['in_source'],
    $coverage['verify_static_markers']['documented'],
    $coverage['verify_static_markers']['in_source'],
));
