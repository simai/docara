#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Project every conventional unminified Smart entrypoint from one immutable
 * simai/ui-smart revision. Shell files stay in asset_projection; the remaining
 * files are published for the dynamic Loader without being loaded eagerly.
 */
$root = dirname(__DIR__);
// The package's own autoloader in a checkout; the consumer's when Docara is
// installed as a dependency (vendor/simai/docara/scripts -> vendor/).
$autoload = null;
foreach ([$root . '/vendor/autoload.php', dirname(__DIR__, 3) . '/autoload.php'] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if ($autoload === null) {
    fwrite(STDERR, "FRAMEWORK_SYNC_AUTOLOAD_UNAVAILABLE: no Composer autoloader beside the package or in the consumer's vendor directory\n");
    exit(1);
}
require $autoload;

use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Framework\FrameworkRuntimeClosure;

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

// Each Smart manifest names its custom element and declares its inputs; the
// input keys are the element's attribute names. A tag with declared inputs
// also accepts the two base-element attributes every Smart element observes
// and that carry no URL or style: template and root-class. A tag without a
// manifest, or with no declared inputs, keeps an empty list (fail-closed).
$baseAttributes = ['root-class', 'template'];
$declaredAttributes = [];
foreach (preg_split('/\R/', trim($tree)) ?: [] as $relativePath) {
    if (preg_match('#^smart/[a-z][a-z0-9-]*/smart\.manifest\.json$#D', $relativePath) !== 1) {
        continue;
    }
    $manifest = json_decode($git(['show', $revision . ':' . $relativePath]), true, 512, JSON_THROW_ON_ERROR);
    $tag = is_array($manifest) ? ($manifest['custom_element'] ?? null) : null;
    $properties = is_array($manifest) ? ($manifest['inputs']['properties'] ?? null) : null;
    if (! is_string($tag) || preg_match('/^sf-[a-z][a-z0-9-]*$/D', $tag) !== 1 || ! is_array($properties)) {
        throw new RuntimeException('FRAMEWORK_SMART_MANIFEST_INVALID: ' . $relativePath);
    }
    if (isset($declaredAttributes[$tag])) {
        throw new RuntimeException('FRAMEWORK_SMART_MANIFEST_TAG_DUPLICATE: ' . $tag);
    }
    $attributes = [];
    foreach (array_keys($properties) as $attribute) {
        if (! is_string($attribute) || preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', $attribute) !== 1) {
            throw new RuntimeException('FRAMEWORK_SMART_MANIFEST_ATTRIBUTE_INVALID: ' . $relativePath);
        }
        $attributes[] = $attribute;
    }
    if ($attributes !== []) {
        $attributes = array_values(array_unique([...$attributes, ...$baseAttributes]));
    }
    sort($attributes, SORT_STRING);
    $declaredAttributes[$tag] = $attributes;
}
if ($declaredAttributes === []) {
    throw new RuntimeException('FRAMEWORK_SMART_MANIFESTS_MISSING');
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
// Every file the sync owns there comes from `git show <revision>:<path>`, so a
// file the revision does not have can only be left over from an earlier run;
// it is removed and reported rather than kept.
$expected = array_fill_keys(array_keys($files), true);
$pruned = [];
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
    if (! isset($expected[$relativePath])) {
        if (! $entry->isFile() || ! unlink($path)) {
            throw new RuntimeException('FRAMEWORK_SMART_PROJECTION_PRUNE_FAILED: ' . $relativePath);
        }
        $pruned[] = $relativePath;
    }
}
sort($pruned, SORT_STRING);

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
// What each component needs loaded with it is read from the Framework contract
// registry this repository bundles, not kept by hand here. The hand-written map
// named two components and was a patch over the Framework under-declaring its
// own dependencies: the admin menu rendered four Smart elements its rule never
// fetched, and the table five. That is fixed upstream, so the registry now
// carries the answer for every component, and this file stops holding a second
// copy that could disagree with it.
if (! is_array($registry['entries'] ?? null)) {
    throw new RuntimeException('FRAMEWORK_SMART_REGISTRY_INVALID');
}
$registryTags = [];
foreach ($registry['entries'] as $entry) {
    if (! is_array($entry) || ($entry['kind'] ?? null) !== 'smart-component') {
        continue;
    }
    $registryTags[$entry['id']] = $entry['runtime']['tags'] ?? [];
}
$runtimeDependencies = [];
foreach ($registry['entries'] as $entry) {
    if (! is_array($entry) || ($entry['kind'] ?? null) !== 'smart-component') {
        continue;
    }
    $required = [];
    foreach ($entry['requires'] ?? [] as $requiredId) {
        // Only another Smart component is a custom element the Loader fetches;
        // the component and utility layers arrive as assets of their own.
        if (! str_starts_with((string) $requiredId, 'smart.')) {
            continue;
        }
        foreach ($registryTags[$requiredId] ?? [] as $requiredTag) {
            $required[$requiredTag] = true;
        }
    }
    // A component does not fetch itself, and a component with several tags
    // answers the same list for each of them.
    foreach ($registryTags[$entry['id']] ?? [] as $tag) {
        unset($required[$tag]);
    }
    if ($required === []) {
        continue;
    }
    $tags = array_keys($required);
    sort($tags, SORT_STRING);
    foreach ($registryTags[$entry['id']] ?? [] as $tag) {
        $runtimeDependencies[$tag] = $tags;
    }
}
ksort($runtimeDependencies, SORT_STRING);

$synchronizedRuntime = null;
$staticPaths = null;
$staticProjection = [];
$dynamicProjection = [];
foreach ([$lockPath, $root . '/stubs/portable/simai-framework.lock.json'] as $projectLockPath) {
    $projectLock = json_decode((string) file_get_contents($projectLockPath), true, 512, JSON_THROW_ON_ERROR);
    if (($projectLock['runtime']['ui_smart']['commit'] ?? null) !== $revision
        || ($projectLock['asset_projection']['source']['revision'] ?? null) !== $revision
    ) {
        throw new RuntimeException('FRAMEWORK_SMART_PROJECT_LOCK_REVISION_MISMATCH: ' . $projectLockPath);
    }
    foreach ($smartEntries as $tag => $component) {
        if (! isset($projectLock['runtime']['components'][$tag])) {
            $projectLock['runtime']['components'][$tag] = $component;
        }
    }
    foreach ($runtimeDependencies as $tag => $requires) {
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
    foreach (array_keys($projectLock['runtime']['components']) as $tag) {
        $projectLock['runtime']['components'][$tag]['attributes'] = $declaredAttributes[$tag] ?? [];
    }
    ksort($projectLock['runtime']['components'], SORT_STRING);

    // The eager projection is exactly what the admission preflight's closure
    // reaches: the shell tags and each admitted manifest's tag, expanded
    // through `requires`. It is derived with the planner's own function, and a
    // list already present in the lock is ignored, so a hand-kept list can no
    // longer drift from the closure the planner checks.
    $manifestTags = [];
    foreach (array_keys($projectLock['manifests'] ?? []) as $manifestKey) {
        $manifestPath = $root . '/resources/framework/'
            . FrameworkManifestRepository::manifestRelativePath((string) $manifestKey);
        $componentManifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $manifestTag = $componentManifest['frontend']['tag'] ?? null;
        if (! is_string($manifestTag)) {
            throw new RuntimeException('FRAMEWORK_SMART_MANIFEST_TAG_MISSING: ' . $manifestKey);
        }
        $manifestTags[] = $manifestTag;
    }
    $eagerPaths = FrameworkRuntimeClosure::eagerSmartFiles($projectLock['runtime'], $manifestTags);
    foreach ($eagerPaths as $relativePath) {
        if (! isset($files[$relativePath])) {
            throw new RuntimeException('FRAMEWORK_STATIC_SMART_ASSET_MISSING: ' . $relativePath);
        }
    }
    if ($staticPaths !== null && $staticPaths !== $eagerPaths) {
        throw new RuntimeException('FRAMEWORK_SMART_EAGER_SET_DIVERGES: ' . $projectLockPath);
    }
    $staticPaths = $eagerPaths;
    $staticProjection = [];
    $dynamicProjection = [];
    foreach ($files as $relativePath => $bytes) {
        $record = ['sha256' => hash('sha256', $bytes)];
        if (in_array($relativePath, $eagerPaths, true)) {
            $staticProjection[$relativePath] = $record;
        } else {
            $dynamicProjection[$relativePath] = $record;
        }
    }
    $projectLock['asset_projection']['mount'] = '_docara/framework-runtime';
    $projectLock['asset_projection']['files'] = $staticProjection;
    $projectLock['dynamic_asset_projection'] = [
        'schema' => 'docara.framework_dynamic_asset_projection.v1',
        'mount' => '_docara/framework-runtime',
        'source' => $projectLock['asset_projection']['source'],
        'files' => $dynamicProjection,
    ];

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
    'pruned' => $pruned,
], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
