#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Fail-closed same-filesystem static-directory cutover helper.
 *
 * The parent path is operator input and is never printed. Only basename labels
 * and content digests are emitted so logs remain portable.
 */
$command = $argv[1] ?? '';
$options = [];
foreach (array_slice($argv, 2) as $argument) {
    if (! str_starts_with($argument, '--') || ! str_contains($argument, '=')) {
        fwrite(STDERR, "[CUTOVER_BLOCKED] Options must use --name=value syntax.\n");
        exit(1);
    }
    [$key, $value] = explode('=', substr($argument, 2), 2);
    if (array_key_exists($key, $options)) {
        fwrite(STDERR, "[CUTOVER_BLOCKED] Duplicate option --{$key}.\n");
        exit(1);
    }
    $options[$key] = $value;
}

try {
    if (! in_array($command, ['preflight', 'cutover', 'rollback'], true)) {
        throw new InvalidArgumentException('Command must be preflight, cutover or rollback.');
    }

    $parentInput = requiredOption($options, 'parent');
    if (! str_starts_with($parentInput, DIRECTORY_SEPARATOR)) {
        throw new InvalidArgumentException('Parent must be an absolute path.');
    }
    $parent = realpath($parentInput);
    if ($parent === false || ! is_dir($parent) || is_link($parent)) {
        throw new RuntimeException('Parent must be an existing non-symlink directory.');
    }

    $names = [];
    foreach (['active', 'candidate', 'backup'] as $key) {
        $names[$key] = safeBasename(requiredOption($options, $key), $key);
    }
    if (count(array_unique($names)) !== 3) {
        throw new InvalidArgumentException('Active, candidate and backup names must be distinct.');
    }

    $paths = array_map(static fn (string $name): string => $parent . DIRECTORY_SEPARATOR . $name, $names);
    $expectedActive = requiredDigest($options, 'expected-active-sha256');
    $expectedCandidate = requiredDigest($options, 'expected-candidate-sha256');

    if ($command === 'preflight' || $command === 'cutover') {
        assertDirectory($paths['active'], 'active');
        assertDirectory($paths['candidate'], 'candidate');
        if (file_exists($paths['backup']) || is_link($paths['backup'])) {
            throw new RuntimeException('Backup target already exists.');
        }
        assertSameDevice($parent, $paths['active'], $paths['candidate']);
        assertDigest($paths['active'], $expectedActive, 'active');
        assertDigest($paths['candidate'], $expectedCandidate, 'candidate');

        if ($command === 'cutover') {
            checkedRename($paths['active'], $paths['backup'], 'active to backup');
            try {
                checkedRename($paths['candidate'], $paths['active'], 'candidate to active');
            } catch (Throwable $exception) {
                checkedRename($paths['backup'], $paths['active'], 'automatic recovery');
                throw $exception;
            }
            assertDigest($paths['active'], $expectedCandidate, 'new active');
            assertDigest($paths['backup'], $expectedActive, 'backup');
        }
    } else {
        assertDirectory($paths['active'], 'active');
        assertDirectory($paths['backup'], 'backup');
        if (file_exists($paths['candidate']) || is_link($paths['candidate'])) {
            throw new RuntimeException('Candidate recovery target already exists.');
        }
        assertSameDevice($parent, $paths['active'], $paths['backup']);
        assertDigest($paths['active'], $expectedCandidate, 'active candidate');
        assertDigest($paths['backup'], $expectedActive, 'backup');

        checkedRename($paths['active'], $paths['candidate'], 'active candidate to recovery target');
        try {
            checkedRename($paths['backup'], $paths['active'], 'backup to active');
        } catch (Throwable $exception) {
            checkedRename($paths['candidate'], $paths['active'], 'automatic recovery');
            throw $exception;
        }
        assertDigest($paths['active'], $expectedActive, 'restored active');
        assertDigest($paths['candidate'], $expectedCandidate, 'preserved candidate');
    }

    fwrite(STDOUT, json_encode([
        'schema' => 'docara.atomic_static_cutover.v1',
        'command' => $command,
        'status' => 'pass',
        'active' => $names['active'],
        'candidate' => $names['candidate'],
        'backup' => $names['backup'],
        'expected_active_sha256' => $expectedActive,
        'expected_candidate_sha256' => $expectedCandidate,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, '[CUTOVER_BLOCKED] ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

/** @param array<string, mixed> $options */
function requiredOption(array $options, string $key): string
{
    $value = $options[$key] ?? null;
    if (! is_string($value) || $value === '') {
        throw new InvalidArgumentException('Missing --' . $key . '.');
    }

    return $value;
}

function safeBasename(string $value, string $key): string
{
    if ($value === '.' || $value === '..' || basename($value) !== $value || str_contains($value, "\0")) {
        throw new InvalidArgumentException('--' . $key . ' must be a safe basename.');
    }

    return $value;
}

/** @param array<string, mixed> $options */
function requiredDigest(array $options, string $key): string
{
    $digest = strtolower(requiredOption($options, $key));
    if (preg_match('/^[a-f0-9]{64}$/', $digest) !== 1) {
        throw new InvalidArgumentException('--' . $key . ' must be a SHA-256 digest.');
    }

    return $digest;
}

function assertDirectory(string $path, string $label): void
{
    if (! is_dir($path) || is_link($path)) {
        throw new RuntimeException(ucfirst($label) . ' must be an existing non-symlink directory.');
    }
}

function assertSameDevice(string ...$paths): void
{
    $devices = [];
    foreach ($paths as $path) {
        $stat = stat($path);
        if ($stat === false) {
            throw new RuntimeException('Unable to inspect filesystem device.');
        }
        $devices[] = $stat['dev'];
    }
    if (count(array_unique($devices, SORT_REGULAR)) !== 1) {
        throw new RuntimeException('All directories must be on the same filesystem.');
    }
}

function assertDigest(string $path, string $expected, string $label): void
{
    $actual = treeDigest($path);
    if (! hash_equals($expected, $actual)) {
        throw new RuntimeException(ucfirst($label) . ' digest mismatch.');
    }
}

function treeDigest(string $root): string
{
    $lines = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if ($file->isLink()) {
            throw new RuntimeException('Directory tree contains a symlink.');
        }
        if (! $file->isFile()) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $hash = hash_file('sha256', $file->getPathname());
        if ($hash === false) {
            throw new RuntimeException('Unable to hash directory tree.');
        }
        $lines[$relative] = $hash . '  ' . $relative;
    }
    ksort($lines, SORT_STRING);

    return hash('sha256', implode("\n", $lines) . "\n");
}

function checkedRename(string $from, string $to, string $operation): void
{
    if (! rename($from, $to)) {
        throw new RuntimeException('Atomic rename failed: ' . $operation . '.');
    }
}
