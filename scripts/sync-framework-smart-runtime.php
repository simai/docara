#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Project every conventional unminified Smart entrypoint from one immutable
 * simai/ui-smart revision. Shell files stay in asset_projection; the remaining
 * files are published for the dynamic Loader without being loaded eagerly.
 */
$root = dirname(__DIR__);
$smartRoot = $argv[1] ?? null;
if (! is_string($smartRoot)
    || $smartRoot === ''
    || (! is_dir($smartRoot . '/.git') && ! is_file($smartRoot . '/.git'))
) {
    fwrite(STDERR, "Usage: php scripts/sync-framework-smart-runtime.php /absolute/path/to/ui-smart\n");
    exit(2);
}
$smartRoot = (string) realpath($smartRoot);

$lockPath = $root . '/docs/site/simai-framework.lock.json';
$lock = json_decode((string) file_get_contents($lockPath), true, 512, JSON_THROW_ON_ERROR);
$revision = $lock['runtime']['ui_smart']['commit'] ?? null;
if (! is_string($revision) || preg_match('/^[a-f0-9]{40}$/D', $revision) !== 1) {
    throw new RuntimeException('FRAMEWORK_SMART_REVISION_INVALID');
}

$git = static function (array $arguments) use ($smartRoot): string {
    $pipes = [];
    $process = proc_open(['git', '-C', $smartRoot, ...$arguments], [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('FRAMEWORK_SMART_SOURCE_UNAVAILABLE');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0 || ! is_string($stdout)) {
        throw new RuntimeException('FRAMEWORK_SMART_SOURCE_UNAVAILABLE: ' . trim((string) $stderr));
    }

    return $stdout;
};

$tree = $git(['ls-tree', '-r', '--name-only', $revision, 'smart']);
$files = [];
foreach (preg_split('/\R/', trim($tree)) ?: [] as $relativePath) {
    if (preg_match('#^smart/([a-z][a-z0-9-]*)/(css|js)/\1\.(css|js)$#D', $relativePath) !== 1) {
        continue;
    }
    $bytes = $git(['show', $revision . ':' . $relativePath]);
    if ($bytes === '') {
        throw new RuntimeException('FRAMEWORK_SMART_ASSET_EMPTY: ' . $relativePath);
    }
    $files[$relativePath] = $bytes;
}
ksort($files, SORT_STRING);
if ($files === []) {
    throw new RuntimeException('FRAMEWORK_SMART_ENTRYPOINTS_MISSING');
}

$targetRoot = $root . '/resources/framework/runtime-smart/' . $revision;
foreach ($files as $relativePath => $bytes) {
    $target = $targetRoot . '/' . $relativePath;
    if (! is_dir(dirname($target))
        && ! mkdir(dirname($target), 0755, true)
        && ! is_dir(dirname($target))
    ) {
        throw new RuntimeException('FRAMEWORK_SMART_DIRECTORY_FAILED: ' . $relativePath);
    }
    file_put_contents($target, $bytes, LOCK_EX);
    chmod($target, 0644);
}

// Prune only obsolete regular files under this explicit revision directory.
$expected = array_fill_keys(array_keys($files), true);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($targetRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST,
);
foreach ($iterator as $entry) {
    $path = $entry->getPathname();
    if ($entry->isLink()) {
        throw new RuntimeException('FRAMEWORK_SMART_PROJECTION_SYMLINK_FORBIDDEN: ' . $path);
    }
    if ($entry->isDir()) {
        @rmdir($path);

        continue;
    }
    $relativePath = str_replace('\\', '/', substr($path, strlen($targetRoot) + 1));
    if (! isset($expected[$relativePath]) && (! $entry->isFile() || ! unlink($path))) {
        throw new RuntimeException('FRAMEWORK_SMART_PROJECTION_PRUNE_FAILED: ' . $relativePath);
    }
}

$staticPaths = array_keys($lock['asset_projection']['files'] ?? []);
sort($staticPaths, SORT_STRING);
foreach ($staticPaths as $relativePath) {
    if (! isset($files[$relativePath])) {
        throw new RuntimeException('FRAMEWORK_STATIC_SMART_ASSET_MISSING: ' . $relativePath);
    }
}

$staticProjection = [];
$dynamicProjection = [];
foreach ($files as $relativePath => $bytes) {
    $record = ['sha256' => hash('sha256', $bytes)];
    if (in_array($relativePath, $staticPaths, true)) {
        $staticProjection[$relativePath] = $record;
    } else {
        $dynamicProjection[$relativePath] = $record;
    }
}

$registry = json_decode(
    (string) file_get_contents($root . '/docs/site/contracts/generated/framework-contract-registry.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
$smartEntries = [];
foreach (($registry['entries'] ?? []) as $entry) {
    if (! is_array($entry) || ($entry['kind'] ?? null) !== 'smart-component') {
        continue;
    }
    $name = $entry['name'] ?? null;
    $tags = $entry['runtime']['tags'] ?? null;
    if (! is_string($name) || ! is_array($tags) || ! array_is_list($tags) || $tags === []) {
        continue;
    }
    $javascript = 'smart/' . $name . '/js/' . $name . '.js';
    $css = 'smart/' . $name . '/css/' . $name . '.css';
    if (! isset($files[$javascript])) {
        throw new RuntimeException('FRAMEWORK_SMART_RUNTIME_JAVASCRIPT_MISSING: ' . $name);
    }
    foreach ($tags as $tag) {
        if (! is_string($tag) || preg_match('/^sf-[a-z][a-z0-9-]*$/D', $tag) !== 1) {
            throw new RuntimeException('FRAMEWORK_SMART_RUNTIME_TAG_INVALID: ' . $name);
        }
        $smartEntries[$tag] = [
            'source' => 'smart/' . $name,
            'javascript' => 'smart/' . $javascript,
            'css' => isset($files[$css]) ? 'smart/' . $css : null,
            'attributes' => [],
        ];
    }
}
ksort($smartEntries, SORT_STRING);
$runtimeDependencyOverrides = [
    'sf-admin-menu' => [
        'sf-badge',
        'sf-button',
        'sf-context-menu',
        'sf-icon',
        'sf-icon-button',
        'sf-input',
        'sf-modal',
    ],
    'sf-table' => [
        'sf-avatar',
        'sf-button',
        'sf-checkbox',
        'sf-context-menu',
        'sf-datepicker',
        'sf-dropdown',
        'sf-icon-button',
        'sf-input',
        'sf-list-item',
        'sf-modal',
        'sf-pagination',
        'sf-range-slider',
        'sf-tabs',
        'sf-tag',
    ],
];

$synchronizedRuntime = null;
foreach ([$lockPath, $root . '/stubs/portable/simai-framework.lock.json'] as $projectLockPath) {
    $projectLock = json_decode((string) file_get_contents($projectLockPath), true, 512, JSON_THROW_ON_ERROR);
    if (($projectLock['runtime']['ui_smart']['commit'] ?? null) !== $revision
        || ($projectLock['asset_projection']['source']['revision'] ?? null) !== $revision
    ) {
        throw new RuntimeException('FRAMEWORK_SMART_PROJECT_LOCK_REVISION_MISMATCH: ' . $projectLockPath);
    }
    $projectLock['asset_projection']['mount'] = '_docara/framework-runtime';
    $projectLock['asset_projection']['files'] = $staticProjection;
    $projectLock['dynamic_asset_projection'] = [
        'schema' => 'docara.framework_dynamic_asset_projection.v1',
        'mount' => '_docara/framework-runtime',
        'source' => $projectLock['asset_projection']['source'],
        'files' => $dynamicProjection,
    ];
    foreach ($smartEntries as $tag => $component) {
        if (! isset($projectLock['runtime']['components'][$tag])) {
            $projectLock['runtime']['components'][$tag] = $component;
        }
    }
    foreach ($runtimeDependencyOverrides as $tag => $requires) {
        if (! isset($projectLock['runtime']['components'][$tag])) {
            throw new RuntimeException('FRAMEWORK_SMART_RUNTIME_DEPENDENCY_OWNER_MISSING: ' . $tag);
        }
        foreach ($requires as $requiredTag) {
            if (! isset($projectLock['runtime']['components'][$requiredTag])) {
                throw new RuntimeException('FRAMEWORK_SMART_RUNTIME_DEPENDENCY_MISSING: ' . $tag . ' -> ' . $requiredTag);
            }
        }
        sort($requires, SORT_STRING);
        $projectLock['runtime']['components'][$tag]['requires'] = $requires;
    }
    ksort($projectLock['runtime']['components'], SORT_STRING);
    $synchronizedRuntime = $projectLock['runtime'];
    file_put_contents(
        $projectLockPath,
        json_encode($projectLock, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        LOCK_EX,
    );
}

$runtimeLockPath = $root . '/resources/framework/runtime-lock.json';
$runtimeLock = json_decode((string) file_get_contents($runtimeLockPath), true, 512, JSON_THROW_ON_ERROR);
$assetPlanner = $runtimeLock['asset_planner'] ?? null;
if (! is_array($synchronizedRuntime)) {
    throw new RuntimeException('FRAMEWORK_SMART_RUNTIME_SYNC_FAILED');
}
if (is_array($assetPlanner)) {
    $synchronizedRuntime = [
        'schema' => $synchronizedRuntime['schema'],
        'asset_planner' => $assetPlanner,
        ...array_diff_key($synchronizedRuntime, ['schema' => true]),
    ];
}
file_put_contents(
    $runtimeLockPath,
    json_encode($synchronizedRuntime, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    LOCK_EX,
);

fwrite(STDOUT, json_encode([
    'schema' => 'docara.framework_smart_runtime_projection.v1',
    'revision' => $revision,
    'static_files' => count($staticProjection),
    'dynamic_files' => count($dynamicProjection),
    'total_files' => count($files),
    'runtime_components' => count($smartEntries),
], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
