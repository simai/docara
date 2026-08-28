#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Project the exact Loader rule registry from the pinned SIMAI Framework commit.
 *
 * This is a deterministic maintainer command. It does not invent rules and it
 * refuses a dirty/mismatched source revision. The projected file is consumed by
 * the production Asset Planner; the dynamic Loader keeps using the same bytes.
 */
$root = dirname(__DIR__);
$uiRoot = $argv[1] ?? null;
$useWorkingRegistry = ($argv[2] ?? null) === '--registry-working-tree';
if (! is_string($uiRoot) || $uiRoot === '' || ! is_dir($uiRoot . '/.git')) {
    fwrite(STDERR, "Usage: php scripts/sync-framework-rule-registry.php /absolute/path/to/ui\n");
    exit(2);
}

$lockPath = $root . '/docs/site/simai-framework.lock.json';
$lock = json_decode((string) file_get_contents($lockPath), true, 512, JSON_THROW_ON_ERROR);
$projection = $lock['runtime_projection'] ?? null;
$revision = $projection['source']['revision'] ?? null;
if (! is_array($projection) || ! is_string($revision) || preg_match('/^[a-f0-9]{40}$/D', $revision) !== 1) {
    throw new RuntimeException('FRAMEWORK_RUNTIME_PROJECTION_INVALID');
}

$git = static function (array $arguments) use ($uiRoot): string {
    $command = ['git', '-C', realpath($uiRoot), ...$arguments];
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('FRAMEWORK_SOURCE_UNAVAILABLE');
    }
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0 || ! is_string($output)) {
        throw new RuntimeException('FRAMEWORK_SOURCE_UNAVAILABLE: ' . trim((string) $error));
    }

    return $output;
};
$bytes = $useWorkingRegistry
    ? file_get_contents(realpath($uiRoot) . '/distr/rule/rule.json')
    : $git(['show', $revision . ':distr/rule/rule.json']);
if (! is_string($bytes)) {
    throw new RuntimeException('FRAMEWORK_RULE_REGISTRY_SOURCE_UNAVAILABLE');
}
if ($bytes === '') {
    throw new RuntimeException('FRAMEWORK_RULE_REGISTRY_SOURCE_UNAVAILABLE');
}
$rules = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
if (! is_array($rules) || ! array_is_list($rules) || $rules === []) {
    throw new RuntimeException('FRAMEWORK_RULE_REGISTRY_INVALID');
}

$runtimeBase = $root . '/resources/portable/vendor/simai-framework/runtime/' . $revision;
$distribution = $runtimeBase . '/distr';
$target = $distribution . '/rule/rule.json';
if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
    throw new RuntimeException('FRAMEWORK_RULE_REGISTRY_DIRECTORY_FAILED');
}
$temporary = $target . '.tmp-' . bin2hex(random_bytes(6));
file_put_contents($temporary, $bytes, LOCK_EX);
chmod($temporary, 0644);
rename($temporary, $target);

// Production planning needs every utility stylesheet that the canonical rule
// registry may select. JavaScript and precompressed/minified variants are not
// copied here; the exact page plan publishes only selected unminified CSS.
$tree = $git(['ls-tree', '-r', '--name-only', $revision, 'distr/utility']);
foreach (preg_split('/\R/', trim($tree)) ?: [] as $sourcePath) {
    if (preg_match('#^distr/utility/[A-Za-z0-9/-]+/css/[A-Za-z0-9-]+\.css$#D', $sourcePath) !== 1
        || str_contains($sourcePath, '.min.')
    ) {
        continue;
    }
    $relativePath = substr($sourcePath, strlen('distr/'));
    $assetTarget = $distribution . '/' . $relativePath;
    if (! is_dir(dirname($assetTarget))
        && ! mkdir(dirname($assetTarget), 0755, true)
        && ! is_dir(dirname($assetTarget))
    ) {
        throw new RuntimeException('FRAMEWORK_RUNTIME_DIRECTORY_FAILED: ' . $relativePath);
    }
    $assetBytes = $git(['show', $revision . ':' . $sourcePath]);
    if ($assetBytes === '' || preg_match('//u', $assetBytes) !== 1) {
        throw new RuntimeException('FRAMEWORK_RUNTIME_ASSET_INVALID: ' . $relativePath);
    }
    file_put_contents($assetTarget, $assetBytes, LOCK_EX);
    chmod($assetTarget, 0644);
}

// Project conventional component entrypoints as well. Very large editor
// runtimes remain dynamic; the planner reports that fallback instead of
// turning every documentation build into a monolithic JavaScript bundle.
$componentTree = $git(['ls-tree', '-r', '-l', $revision, 'distr/component']);
foreach (preg_split('/\R/', trim($componentTree)) ?: [] as $line) {
    if (preg_match('/^[0-9]+\s+blob\s+[a-f0-9]{40}\s+([0-9-]+)\t(.+)$/D', $line, $match) !== 1) {
        continue;
    }
    $size = (int) $match[1];
    $sourcePath = $match[2];
    if ($size === 0
        || $size > 4 * 1024 * 1024
        || preg_match('#^distr/component/([a-z0-9-]+)/(css|js)/\1\.(css|js)$#D', $sourcePath) !== 1
        || str_contains($sourcePath, '.min.')
    ) {
        continue;
    }
    $relativePath = substr($sourcePath, strlen('distr/'));
    $assetTarget = $distribution . '/' . $relativePath;
    if (! is_dir(dirname($assetTarget))
        && ! mkdir(dirname($assetTarget), 0755, true)
        && ! is_dir(dirname($assetTarget))
    ) {
        throw new RuntimeException('FRAMEWORK_RUNTIME_DIRECTORY_FAILED: ' . $relativePath);
    }
    $assetBytes = $git(['show', $revision . ':' . $sourcePath]);
    if ($assetBytes === '' || preg_match('//u', $assetBytes) !== 1) {
        throw new RuntimeException('FRAMEWORK_RUNTIME_ASSET_INVALID: ' . $relativePath);
    }
    file_put_contents($assetTarget, $assetBytes, LOCK_EX);
    chmod($assetTarget, 0644);
}

// Working-tree corrections are deliberately projected last so the pinned
// revision copy above cannot overwrite the exact files under verification.
if ($useWorkingRegistry) {
    foreach ([
        'core/js/core-rules.js',
        'core/js/core-loader.js',
        'component/icons/css/icons.css',
        'component/icon-buttons/css/icon-buttons.css',
        'component/menu/css/menu.css',
        'component/menu/js/menu.js',
        'component/highlight/js/highlight.js',
    ] as $runtimeFile) {
        $workingRuntime = realpath($uiRoot) . '/distr/' . $runtimeFile;
        $runtimeTarget = $distribution . '/' . $runtimeFile;
        $runtimeBytes = file_get_contents($workingRuntime);
        if (! is_string($runtimeBytes) || $runtimeBytes === '' || preg_match('//u', $runtimeBytes) !== 1) {
            throw new RuntimeException('FRAMEWORK_CORE_RUNTIME_SOURCE_UNAVAILABLE: ' . $runtimeFile);
        }
        if (! is_dir(dirname($runtimeTarget))
            && ! mkdir(dirname($runtimeTarget), 0755, true)
            && ! is_dir(dirname($runtimeTarget))
        ) {
            throw new RuntimeException('FRAMEWORK_CORE_RUNTIME_DIRECTORY_FAILED: ' . $runtimeFile);
        }
        file_put_contents($runtimeTarget, $runtimeBytes, LOCK_EX);
        chmod($runtimeTarget, 0644);
    }
}

$manifestPath = $runtimeBase . '/runtime-manifest.json';
$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
$manifest['files'] = array_filter(
    $manifest['files'],
    static fn (string $relativePath): bool => ! str_starts_with($relativePath, 'utility/'),
    ARRAY_FILTER_USE_KEY,
);
$manifest['files']['rule/rule.json'] = ['sha256' => hash('sha256', $bytes)];
foreach (new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($distribution . '/utility', FilesystemIterator::SKIP_DOTS),
) as $file) {
    if (! $file->isFile() || $file->isLink() || str_contains($file->getFilename(), '.min.')) {
        continue;
    }
    $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($distribution) + 1));
    if (! str_ends_with($relativePath, '.css')) {
        continue;
    }
    $manifest['files'][$relativePath] = ['sha256' => hash_file('sha256', $file->getPathname())];
}
foreach (new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($distribution . '/component', FilesystemIterator::SKIP_DOTS),
) as $file) {
    if (! $file->isFile() || $file->isLink() || str_contains($file->getFilename(), '.min.')) {
        continue;
    }
    $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($distribution) + 1));
    if (preg_match('#^component/([a-z0-9-]+)/(css|js)/\1\.(css|js)$#D', $relativePath) !== 1) {
        continue;
    }
    $manifest['files'][$relativePath] = ['sha256' => hash_file('sha256', $file->getPathname())];
}
ksort($manifest['files'], SORT_STRING);
$ledger = '';
foreach (array_keys($manifest['files']) as $relativePath) {
    $path = $distribution . '/' . $relativePath;
    if (! is_file($path) || is_link($path) || fileinode($path) === false || (int) fileperms($path) === 0) {
        throw new RuntimeException('FRAMEWORK_RUNTIME_ASSET_UNSAFE: ' . $relativePath);
    }
    $asset = file_get_contents($path);
    if (! is_string($asset) || $asset === '') {
        throw new RuntimeException('FRAMEWORK_RUNTIME_ASSET_MISSING: ' . $relativePath);
    }
    $sha256 = hash('sha256', $asset);
    $manifest['files'][$relativePath] = ['sha256' => $sha256];
    $ledger .= $sha256 . '  ' . $relativePath . "\n";
}
$packetSha256 = hash('sha256', $ledger);
$manifest['packet_sha256'] = $packetSha256;
$manifestBytes = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
file_put_contents($manifestPath, $manifestBytes, LOCK_EX);

$manifestSha256 = hash('sha256', $manifestBytes);
foreach ([$lockPath, $root . '/stubs/portable/simai-framework.lock.json'] as $projectLockPath) {
    $projectLock = json_decode((string) file_get_contents($projectLockPath), true, 512, JSON_THROW_ON_ERROR);
    if (($projectLock['runtime_projection']['source']['revision'] ?? null) !== $revision) {
        throw new RuntimeException('FRAMEWORK_PROJECT_LOCK_REVISION_MISMATCH: ' . $projectLockPath);
    }
    $projectLock['runtime_projection']['packet_sha256'] = $packetSha256;
    $projectLock['runtime_projection']['files'] = count($manifest['files']);
    $projectLock['runtime_projection']['manifest']['sha256'] = $manifestSha256;
    $lockBytes = json_encode($projectLock, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    file_put_contents($projectLockPath, $lockBytes, LOCK_EX);
}

fwrite(STDOUT, json_encode([
    'schema' => 'docara.framework_rule_projection.v1',
    'revision' => $revision,
    'rule_registry_sha256' => hash('sha256', $bytes),
    'rule_registry_source' => $useWorkingRegistry ? 'working_tree' : 'revision',
    'files' => count($manifest['files']),
    'packet_sha256' => $packetSha256,
], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
