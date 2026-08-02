#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Documentation/MarkdownLocalLinkVerifier.php';

use Simai\Docara\Documentation\MarkdownLocalLinkVerifier;

$manifestPath = $argv[1] ?? '';
if ($manifestPath === '' || count($argv) !== 2) {
    fwrite(STDERR, "Usage: php scripts/verify-release-package.php <release-manifest.json>\n");
    exit(2);
}

try {
    $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($manifest) || ($manifest['schema'] ?? null) !== 'docara.release_artifact_manifest.v1') {
        throw new RuntimeException('Release artifact manifest is invalid.');
    }
    $archive = dirname($manifestPath) . '/' . (string) ($manifest['archive'] ?? '');
    $actualArchiveHash = hash_file('sha256', $archive);
    if (! is_string($actualArchiveHash) || ! hash_equals((string) ($manifest['archive_sha256'] ?? ''), $actualArchiveHash)) {
        throw new RuntimeException('Release archive checksum does not match its manifest.');
    }
    $zip = new ZipArchive;
    if ($zip->open($archive, ZipArchive::RDONLY) !== true) {
        throw new RuntimeException('Release archive cannot be opened.');
    }
    $names = [];
    $contentsByName = [];
    $caseNames = [];
    $normalizedMtime = null;
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $stat = $zip->statIndex($index);
        $name = is_array($stat) ? (string) ($stat['name'] ?? '') : '';
        if ($name === '' || str_starts_with($name, '/') || str_contains($name, '\\') || preg_match('#(^|/)\.\.(/|$)#', $name) === 1) {
            throw new RuntimeException("Unsafe archive path [{$name}].");
        }
        $lower = strtolower($name);
        if (isset($caseNames[$lower])) {
            throw new RuntimeException("Duplicate or case-colliding archive path [{$name}].");
        }
        $caseNames[$lower] = true;
        $names[] = $name;
        $operationsSystem = 0;
        $externalAttributes = 0;
        if (! $zip->getExternalAttributesIndex($index, $operationsSystem, $externalAttributes)
            || $operationsSystem !== ZipArchive::OPSYS_UNIX) {
            throw new RuntimeException("Archive entry has no normalized Unix permissions [{$name}].");
        }
        $mode = ($externalAttributes >> 16) & 0xFFFF;
        $expectedMode = $name === 'docara' ? 0100755 : 0100644;
        if ($mode !== $expectedMode) {
            throw new RuntimeException(sprintf('Archive mode mismatch [%s]: %o.', $name, $mode));
        }
        $mtime = (int) ($stat['mtime'] ?? 0);
        $normalizedMtime ??= $mtime;
        if ($mtime !== $normalizedMtime || $mtime < 315500000 || $mtime > 315600000) {
            throw new RuntimeException("Archive timestamp is not normalized [{$name}].");
        }
        $contents = $zip->getFromIndex($index);
        if (! is_string($contents) || ! hash_equals((string) ($manifest['files'][$name] ?? ''), hash('sha256', $contents))) {
            throw new RuntimeException("Archive file hash mismatch [{$name}].");
        }
        $contentsByName[$name] = $contents;
    }
    $zip->close();
    sort($names, SORT_STRING);
    $expected = array_keys(is_array($manifest['files'] ?? null) ? $manifest['files'] : []);
    sort($expected, SORT_STRING);
    if ($names !== $expected || count($names) !== (int) ($manifest['file_count'] ?? -1)) {
        throw new RuntimeException('Release archive inventory does not match its manifest.');
    }
    foreach (['RELEASE-MANIFEST.json', 'RELEASE-SBOM.cdx.json', 'LICENSE', 'composer.json', 'docara'] as $required) {
        if (! in_array($required, $names, true)) {
            throw new RuntimeException("Required release file is missing [{$required}].");
        }
    }
    foreach (['.git/', '.github/', 'graph/', 'source/', 'tests/', 'vendor/'] as $forbidden) {
        foreach ($names as $name) {
            if (str_starts_with($name, $forbidden)) {
                throw new RuntimeException("Developer-only path leaked into release [{$name}].");
            }
        }
    }
    (new MarkdownLocalLinkVerifier)->verify($contentsByName);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Release package verified: {$actualArchiveHash}; files=" . count($names) . "\n");
