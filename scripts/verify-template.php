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
    fwrite(STDERR, "Usage: php scripts/verify-template.php <template-mirror> <exact-docara-revision>\n");

    exit(2);
}

try {
    $diff = (new TemplateMirror(dirname(__DIR__), $sourceRevision))->diff($destination);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");

    exit(1);
}

if ($diff['missing'] !== [] || $diff['changed'] !== [] || $diff['unexpected'] !== []) {
    fwrite(STDERR, json_encode($diff, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

    exit(1);
}

fwrite(STDOUT, "Template mirror matches the canonical Docara starter.\n");
