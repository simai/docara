#!/usr/bin/env php
<?php

declare(strict_types=1);

$lockPath = $argv[1] ?? '';
$expectedRelease = $argv[2] ?? '';
$expectedRevision = $argv[3] ?? '';
if ($lockPath === '' || $expectedRelease === '' || $expectedRevision === '' || count($argv) !== 4) {
    fwrite(STDERR, "Usage: php scripts/verify-composer-release.php <composer.lock> <exact-release> <exact-revision>\n");

    exit(2);
}

try {
    $contents = file_get_contents($lockPath);
    if ($contents === false) {
        throw new RuntimeException('Composer lock is missing or unreadable.');
    }
    $lock = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($lock)) {
        throw new RuntimeException('Composer lock must decode to an object.');
    }

    $packages = array_merge(
        is_array($lock['packages'] ?? null) ? $lock['packages'] : [],
        is_array($lock['packages-dev'] ?? null) ? $lock['packages-dev'] : [],
    );
    $matches = array_values(array_filter(
        $packages,
        static fn (mixed $package): bool => is_array($package)
            && ($package['name'] ?? null) === 'simai/docara',
    ));
    if (count($matches) !== 1
        || ltrim((string) ($matches[0]['version'] ?? ''), 'v') !== ltrim($expectedRelease, 'v')
        || ($matches[0]['source']['reference'] ?? null) !== $expectedRevision
    ) {
        throw new RuntimeException('Composer release does not resolve to the requested Docara revision.');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");

    exit(1);
}

fwrite(STDOUT, "Composer release resolves to the requested Docara revision.\n");
