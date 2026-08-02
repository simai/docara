#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Simai\Docara\Release\DeterministicZipWriter;
use Simai\Docara\Release\ReleasePackageBuilder;

$options = getopt('', ['revision:', 'version:', 'tag:', 'output:']);
if (! is_array($options) || count($options) !== 4) {
    fwrite(STDERR, "Usage: php scripts/build-release-package.php --revision=<40-char-sha> --version=<semver> --tag=<planned-tag> --output=<directory>\n");
    exit(2);
}

try {
    $result = (new ReleasePackageBuilder(new DeterministicZipWriter))->build(
        dirname(__DIR__),
        (string) $options['revision'],
        (string) $options['version'],
        (string) $options['tag'],
        (string) $options['output'],
    );
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
