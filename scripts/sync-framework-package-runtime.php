#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Project one product component package beside a Framework release lock v2,
 * taken by its package receipt.
 *
 *   php scripts/sync-framework-package-runtime.php \
 *     --receipt /absolute/out/package-receipt.json [--from /absolute/out | /absolute/out/package-<name>.tar] \
 *     --lock /absolute/site/simai-framework.lock.json [--lock ...]
 *
 * A product component package (a commercial SIMAI package, for example) is built by
 * ui-builder (`sf-build package`) from a folder of the product's own UI
 * repository over a pinned ui-source revision. The build writes a receipt,
 * `simai.framework.product-package-receipt` version 1, beside the output: the
 * package name, the package source revision, the free release the package must
 * be served beside (`framework.release`, `ui-<12>`), the hash of a
 * deterministic archive of the output and the hash of every file.
 *
 * The package name comes from the receipt. The receipt's `framework.release`
 * must equal the `runtime.pair_id` of each given Docara lock. The files are
 * read from the archive the receipt names (its SHA-256 must match) or from an
 * output directory holding `smart/`; with `--from` omitted, the archive beside
 * the receipt is used when present, otherwise the receipt's directory. Every
 * file laid is checked against the receipt's hash and size, and every
 * `required_files` entry must be laid. The rule file and each component's own
 * script and stylesheet are laid into
 * `resources/framework/runtime-packages/<name>/<package revision>/smart/`, and
 * each given lock gains `package_projections.<name>` with the receipt's
 * identity and the files' hashes. Several packages are projected by running
 * the script once per receipt.
 *
 * Docara does not ship package bytes: `resources/framework/runtime-packages/`
 * is ignored in this repository. A site lays them into its installed copy of
 * Docara at build time; a build publishes them under
 * `_docara/framework-packages/<name>/<package revision>/smart/` and declares
 * `SF_BOOT_CONFIG.packages.<name> = {base}` before Core.
 */
$root = dirname(__DIR__);
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

use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkPackageReceipt;

$usage = "Usage: php scripts/sync-framework-package-runtime.php --receipt /absolute/package-receipt.json [--from /absolute/output-or-archive] --lock /absolute/simai-framework.lock.json [--lock ...]\n";
$options = ['receipt' => null, 'from' => null, 'lock' => []];
for ($index = 1; $index < count($argv); $index++) {
    $name = $argv[$index];
    $value = $argv[$index + 1] ?? null;
    if (! is_string($value) || ! str_starts_with($name, '--') || ! array_key_exists(substr($name, 2), $options)) {
        fwrite(STDERR, $usage);
        exit(2);
    }
    $key = substr($name, 2);
    if ($key === 'lock') {
        $options['lock'][] = $value;
    } else {
        $options[$key] = $value;
    }
    $index++;
}
$receiptPath = is_string($options['receipt']) ? realpath($options['receipt']) : false;
if ($receiptPath === false || ! is_file($receiptPath) || $options['lock'] === []) {
    fwrite(STDERR, "FRAMEWORK_PACKAGE_SYNC_ARGUMENTS_INVALID\n" . $usage);
    exit(2);
}

$receipt = FrameworkPackageReceipt::fromFile($receiptPath);
$package = $receipt->package();
$revision = $receipt->packageRevision();

$from = $options['from'];
if ($from === null) {
    $besideArchive = dirname($receiptPath) . '/' . $receipt->archiveName();
    $from = is_file($besideArchive) ? $besideArchive : dirname($receiptPath);
}
$fromPath = realpath($from);
if ($fromPath === false) {
    throw new RuntimeException('FRAMEWORK_PACKAGE_SOURCE_MISSING: ' . $from);
}
$all = is_file($fromPath)
    ? $receipt->readArchive($fromPath)
    : $receipt->readDirectory($fromPath);

// The rule file and each component's own script and stylesheet, as the free
// Smart projection takes them; minified and compressed copies, templates and
// manifests stay behind.
$files = [];
foreach ($all as $relativePath => $bytes) {
    if ($relativePath === 'smart/rule.js'
        || preg_match('#^smart/([a-z][a-z0-9-]*)/(css|js)/\1\.(css|js)$#D', $relativePath) === 1
    ) {
        $files[$relativePath] = $bytes;
    }
}
ksort($files, SORT_STRING);
foreach ($receipt->requiredFiles() as $required) {
    if (! isset($files[$required])) {
        throw new RuntimeException('FRAMEWORK_PACKAGE_REQUIRED_FILE_MISSING: ' . $required);
    }
}

$targetRoot = $root . '/resources/framework/runtime-packages/' . $package . '/' . $revision;
foreach ($files as $relativePath => $bytes) {
    $target = $targetRoot . '/' . $relativePath;
    if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
        throw new RuntimeException('FRAMEWORK_PACKAGE_DIRECTORY_FAILED: ' . $relativePath);
    }
    if (file_put_contents($target, $bytes, LOCK_EX) === false) {
        throw new RuntimeException('FRAMEWORK_PACKAGE_WRITE_FAILED: ' . $relativePath);
    }
    chmod($target, 0644);
}
$pruned = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($targetRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST,
);
foreach ($iterator as $entry) {
    $path = $entry->getPathname();
    if ($entry->isLink()) {
        throw new RuntimeException('FRAMEWORK_PACKAGE_PROJECTION_SYMLINK_FORBIDDEN: ' . $path);
    }
    if ($entry->isDir()) {
        @rmdir($path);

        continue;
    }
    $relativePath = str_replace('\\', '/', substr($path, strlen($targetRoot) + 1));
    if (! isset($files[$relativePath])) {
        if (! unlink($path)) {
            throw new RuntimeException('FRAMEWORK_PACKAGE_PROJECTION_PRUNE_FAILED: ' . $relativePath);
        }
        $pruned[] = $relativePath;
    }
}

$projection = $receipt->projection(array_map(
    static fn (string $bytes): array => ['sha256' => hash('sha256', $bytes)],
    $files,
));

$written = [];
foreach ($options['lock'] as $lockPath) {
    $lock = json_decode((string) file_get_contents($lockPath), true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($lock) || ($lock['runtime']['pair_id'] ?? null) !== $projection['requires']) {
        throw new RuntimeException(
            'FRAMEWORK_PACKAGE_FRAMEWORK_MISMATCH: package ' . $package . ' requires ' . $projection['requires']
            . ', ' . $lockPath . ' is pinned to ' . (string) ($lock['runtime']['pair_id'] ?? 'nothing'),
        );
    }
    $lock['package_projections'][$package] = $projection;
    ksort($lock['package_projections'], SORT_STRING);
    FrameworkLock::fromArray($lock);
    file_put_contents(
        $lockPath,
        json_encode($lock, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        LOCK_EX,
    );
    $written[] = $lockPath;
}

fwrite(STDOUT, json_encode([
    'schema' => 'docara.framework_package_runtime_projection',
    'schema_version' => 2,
    'package' => $package,
    'package_source' => $projection['receipt']['package_source'],
    'requires' => $projection['requires'],
    'read_from' => is_file($fromPath) ? 'archive' : 'directory',
    'files' => count($files),
    'locks' => $written,
    'pruned' => $pruned,
], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
