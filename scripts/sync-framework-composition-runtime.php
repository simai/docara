#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Project the Composition Recipe runtime from the pinned SIMAI Framework
 * release, so a build needs no external Framework checkout.
 *
 * Every page is resolved through the Framework's Recipe runtime (Node.js). The
 * runtime is the import closure of the entry named in
 * resources/contracts/composition/runtime-lock.json, plus the Recipe contract
 * lock and the normative files its digest covers. They are read from Git
 * objects at the revision of the bundled runtime projection (never from a
 * working tree) and laid out as in the distribution, under
 * resources/framework/composition-runtime/<revision>/, so that directory is a
 * Framework root FileRecipeCompiler::fromFrameworkDistribution() accepts.
 * Files of an earlier projection are pruned. A runtime-manifest.json records
 * the source and the SHA-256 of every file.
 *
 * Usage: php scripts/sync-framework-composition-runtime.php /absolute/path/to/ui
 */
$root = dirname(__DIR__);
$uiRoot = $argv[1] ?? null;
if (! is_string($uiRoot)
    || $uiRoot === ''
    || (! is_dir($uiRoot . '/.git') && ! is_file($uiRoot . '/.git'))
) {
    fwrite(STDERR, "Usage: php scripts/sync-framework-composition-runtime.php /absolute/path/to/ui\n");
    exit(2);
}
$uiRoot = (string) realpath($uiRoot);

$lock = json_decode((string) file_get_contents($root . '/docs/site/simai-framework.lock.json'), true, 512, JSON_THROW_ON_ERROR);
$source = $lock['runtime_projection']['source'] ?? null;
$revision = is_array($source) ? ($source['revision'] ?? null) : null;
if (! is_string($revision) || preg_match('/^[a-f0-9]{40}$/D', $revision) !== 1) {
    throw new RuntimeException('FRAMEWORK_COMPOSITION_REVISION_INVALID');
}
$recipeLock = json_decode(
    (string) file_get_contents($root . '/resources/contracts/composition/runtime-lock.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

$git = static function (array $arguments) use ($uiRoot): string {
    $pipes = [];
    $process = proc_open(['git', '-C', $uiRoot, ...$arguments], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('FRAMEWORK_COMPOSITION_SOURCE_UNAVAILABLE');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || ! is_string($stdout)) {
        throw new RuntimeException('FRAMEWORK_COMPOSITION_SOURCE_UNAVAILABLE: ' . trim((string) $stderr));
    }

    return $stdout;
};
$read = static function (string $path) use ($git, $revision): string {
    $bytes = $git(['show', $revision . ':' . $path]);
    if ($bytes === '') {
        throw new RuntimeException('FRAMEWORK_COMPOSITION_FILE_EMPTY: ' . $path);
    }

    return $bytes;
};

// The module closure: static `from './x.mjs'` and dynamic `import('./x.mjs')`
// specifiers, followed from the entry. Only relative modules inside
// distr/core/js/ are allowed (the composition directory and, since
// ui-1cb0739a8423, core modules beside it such as ../schema-name.mjs);
// anything else would need files this projection does not carry.
$files = [];
$entry = (string) ($recipeLock['runtime']['entry'] ?? '');
$pending = [$entry];
while ($pending !== []) {
    $path = array_shift($pending);
    if (isset($files[$path])) {
        continue;
    }
    if (preg_match('#^distr/core/js/(?:composition/)?[a-z0-9-]+\.mjs$#D', $path) !== 1) {
        throw new RuntimeException('FRAMEWORK_COMPOSITION_MODULE_PATH_INVALID: ' . $path);
    }
    $bytes = $read($path);
    $files[$path] = $bytes;
    $specifiers = [];
    foreach ([
        '#^\s*(?:import|export)\s[^;\'"]*?\bfrom\s*([\'"])([^\'"]+)\1#ms',
        '#^\s*import\s*([\'"])([^\'"]+)\1#m',
        '#\bimport\s*\(\s*([\'"])([^\'"]+)\1\s*\)#',
    ] as $pattern) {
        preg_match_all($pattern, $bytes, $matches);
        array_push($specifiers, ...$matches[2]);
    }
    foreach ($specifiers as $specifier) {
        if (preg_match('#^\./[a-z0-9-]+\.mjs$#D', $specifier) === 1) {
            $pending[] = dirname($path) . '/' . substr($specifier, 2);
        } elseif (preg_match('#^\.\./[a-z0-9-]+\.mjs$#D', $specifier) === 1) {
            $pending[] = dirname($path, 2) . '/' . substr($specifier, 3);
        } else {
            throw new RuntimeException('FRAMEWORK_COMPOSITION_IMPORT_UNSUPPORTED: ' . $path . ' -> ' . $specifier);
        }
    }
}
foreach ((array) ($recipeLock['runtime']['modules'] ?? []) as $module) {
    if (! isset($files[$module])) {
        throw new RuntimeException('FRAMEWORK_COMPOSITION_LOCKED_MODULE_UNREACHED: ' . $module);
    }
}

// The specification: the contract lock and every normative file its digest
// covers, beside it as in the distribution.
$contractLockPath = (string) ($recipeLock['contract']['lock_path'] ?? '');
if (preg_match('#^distr/core/contracts/[a-z0-9.-]+/contract\.lock\.json$#D', $contractLockPath) !== 1) {
    throw new RuntimeException('FRAMEWORK_COMPOSITION_CONTRACT_PATH_INVALID');
}
$contractLockBytes = $read($contractLockPath);
$files[$contractLockPath] = $contractLockBytes;
$contractLock = json_decode($contractLockBytes, true, 512, JSON_THROW_ON_ERROR);
if (($contractLock['version'] ?? null) !== ($recipeLock['contract']['edition'] ?? null)
    || ($contractLock['contractDigest'] ?? null) !== ($recipeLock['contract']['contract_digest'] ?? null)
    || ! is_array($contractLock['normativeFiles'] ?? null)
) {
    throw new RuntimeException('FRAMEWORK_COMPOSITION_CONTRACT_EDITION_MISMATCH');
}
foreach (array_keys($contractLock['normativeFiles']) as $relative) {
    if (! is_string($relative) || preg_match('#^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*(?:\.[A-Za-z0-9]+)+$#D', $relative) !== 1) {
        throw new RuntimeException('FRAMEWORK_COMPOSITION_NORMATIVE_PATH_INVALID');
    }
    $path = dirname($contractLockPath) . '/' . $relative;
    $files[$path] = $read($path);
}
ksort($files, SORT_STRING);

$target = $root . '/resources/framework/composition-runtime/' . $revision;
foreach ($files as $path => $bytes) {
    $file = $target . '/' . $path;
    if (! is_dir(dirname($file)) && ! mkdir(dirname($file), 0755, true) && ! is_dir(dirname($file))) {
        throw new RuntimeException('FRAMEWORK_COMPOSITION_DIRECTORY_FAILED: ' . $path);
    }
    file_put_contents($file, $bytes, LOCK_EX);
    chmod($file, 0644);
}

// Remove earlier projections and anything this one does not own.
$projectionRoot = dirname($target);
$pruned = [];
foreach (new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectionRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST,
) as $entry) {
    $path = $entry->getPathname();
    if ($entry->isLink()) {
        throw new RuntimeException('FRAMEWORK_COMPOSITION_SYMLINK_FORBIDDEN: ' . $path);
    }
    if ($entry->isDir()) {
        @rmdir($path);

        continue;
    }
    $relative = str_replace('\\', '/', substr($path, strlen($projectionRoot) + 1));
    $owned = str_starts_with($relative, $revision . '/')
        && (isset($files[substr($relative, 41)]) || substr($relative, 41) === 'runtime-manifest.json');
    if (! $owned) {
        if (! unlink($path)) {
            throw new RuntimeException('FRAMEWORK_COMPOSITION_PRUNE_FAILED: ' . $relative);
        }
        $pruned[] = $relative;
    }
}
sort($pruned, SORT_STRING);

$ledger = '';
$records = [];
foreach ($files as $path => $bytes) {
    $sha256 = hash('sha256', $bytes);
    $records[$path] = ['sha256' => $sha256];
    $ledger .= $sha256 . '  ' . $path . "\n";
}
$manifest = [
    'schema' => 'docara.framework_composition_runtime.v1',
    'source' => [
        'provider' => 'simai/ui',
        'revision' => $revision,
        'tree_sha256' => $source['tree_sha256'] ?? null,
    ],
    'contract' => [
        'edition' => $contractLock['version'],
        'contract_digest' => $contractLock['contractDigest'],
    ],
    'files' => $records,
    'packet_sha256' => hash('sha256', $ledger),
];
file_put_contents(
    $target . '/runtime-manifest.json',
    json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    LOCK_EX,
);

fwrite(STDOUT, json_encode([
    'schema' => 'docara.framework_composition_projection.v1',
    'revision' => $revision,
    'files' => count($files),
    'packet_sha256' => $manifest['packet_sha256'],
    'pruned' => $pruned,
], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
