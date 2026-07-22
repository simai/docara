#!/usr/bin/env php
<?php

declare(strict_types=1);

use Simai\Docara\Template\TemplateMirror;

$root = dirname(__DIR__);
if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    require $root . '/src/Portable/CanonicalJson.php';
    require $root . '/src/Portable/FilesystemPath.php';
    require $root . '/src/Template/TemplateMirror.php';
}

$destination = $argv[1] ?? '';
$sourceRevision = $argv[2] ?? '';
if ($destination === '' || $sourceRevision === '' || count($argv) !== 3) {
    fwrite(STDERR, "Usage: php scripts/export-template.php <empty-destination> <exact-docara-revision>\n");

    exit(2);
}

try {
    $written = (new TemplateMirror(dirname(__DIR__), $sourceRevision))->export($destination);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");

    exit(1);
}

fwrite(STDOUT, sprintf("Exported %d generated template files to %s.\n", count($written), $destination));
