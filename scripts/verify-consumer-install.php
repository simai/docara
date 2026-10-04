#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Install Docara into a throwaway Composer project the way consumers get it,
 * then run the Framework syncs, the production build and the static verifier
 * there.
 *
 * Package source. Consumers install simai/docara from the GitHub zipball of a
 * tag (Packagist and VCS repositories both resolve to it). That zipball is
 * `git archive` of the commit: tracked files minus `export-ignore` paths, so
 * no tests/, no .github/ and no vendor/. This script builds exactly that with
 * `git archive` (tracked changes in the working tree are included through
 * `git stash create`, untracked files are not), extracts it, and installs it
 * through a Composer `path` repository with `"symlink": false`, which copies
 * the tree into vendor/simai/docara. The release-package ZIP from
 * build-release-package.php is not used: its surface is narrower than what
 * Composer downloads (it leaves the sync scripts out), so it cannot reproduce
 * a consumer that runs them.
 *
 * Framework syncs. The consumer mirrors the reference materializer of a
 * consumer site: it writes its own Framework lock into the package-owned locks
 * (docs/site and stubs/portable inside vendor/simai/docara), runs
 * sync-framework-rule-registry.php against simai/ui and
 * sync-framework-smart-runtime.php against simai/ui-smart from the package
 * directory, and copies the synchronized lock back into the project. Both
 * syncs write package-owned paths inside vendor/simai/docara; that is how the
 * reference consumer works, and the script reports how many files changed.
 * The project lock starts from the old four-file eager Smart list (alert,
 * buttons, icons, modal) that broke 2.13.0; the sync has to derive the eager
 * set itself for the build to pass.
 *
 * Revisions. ui and ui-smart are read only at the commits pinned in the
 * package's own resources/framework/runtime-lock.json.
 *
 * Usage:
 *   php scripts/verify-consumer-install.php [--ui=DIR] [--ui-smart=DIR]
 *       [--source=DIR] [--work-dir=DIR] [--keep]
 *
 * --ui and --ui-smart default to SIMAI_UI_ROOT and SIMAI_UI_SMART_ROOT. Each
 * must be a Git checkout that contains the pinned commit; ui must also be
 * checked out at it, because the build's Composition Recipe runtime reads its
 * working tree (passed on as SIMAI_UI_ROOT, as a consumer sets it). --source defaults
 * to this repository. COMPOSER_BINARY overrides the composer executable.
 */
const OLD_EAGER_SMART_FILES = [
    'smart/alert/js/alert.js',
    'smart/buttons/js/buttons.js',
    'smart/icons/js/icons.js',
    'smart/modal/js/modal.js',
];

final class ConsumerInstallFailure extends RuntimeException
{
    public function __construct(public readonly string $step, string $message)
    {
        parent::__construct($message);
    }
}

$options = getopt('', ['ui:', 'ui-smart:', 'source:', 'work-dir:', 'keep', 'help']);
if (! is_array($options) || isset($options['help'])) {
    fwrite(STDERR, "Usage: php scripts/verify-consumer-install.php [--ui=DIR] [--ui-smart=DIR] [--source=DIR] [--work-dir=DIR] [--keep]\n");
    exit(2);
}
$option = static function (string $name, ?string $environment = null) use ($options): ?string {
    $value = $options[$name] ?? null;
    if (! is_string($value) || $value === '') {
        $value = $environment === null ? null : getenv($environment);
    }

    return is_string($value) && $value !== '' ? $value : null;
};

$sourceRoot = realpath($option('source') ?? dirname(__DIR__));
$uiRoot = $option('ui', 'SIMAI_UI_ROOT');
$smartRoot = $option('ui-smart', 'SIMAI_UI_SMART_ROOT');
if ($sourceRoot === false || $uiRoot === null || $smartRoot === null) {
    fwrite(STDERR, "CONSUMER_INSTALL_INPUT_MISSING: pass --ui and --ui-smart (or set SIMAI_UI_ROOT and SIMAI_UI_SMART_ROOT)\n");
    exit(2);
}
$uiRoot = realpath($uiRoot);
$smartRoot = realpath($smartRoot);
if ($uiRoot === false || $smartRoot === false) {
    fwrite(STDERR, "CONSUMER_INSTALL_INPUT_MISSING: the ui or ui-smart directory does not exist\n");
    exit(2);
}
$composer = getenv('COMPOSER_BINARY');
$composer = is_string($composer) && $composer !== '' ? $composer : 'composer';
$keep = isset($options['keep']);

$workParent = realpath($option('work-dir') ?? sys_get_temp_dir());
if ($workParent === false) {
    fwrite(STDERR, "CONSUMER_INSTALL_INPUT_MISSING: the work directory does not exist\n");
    exit(2);
}
$work = $workParent . '/docara-consumer-' . bin2hex(random_bytes(6));
$packageDir = $work . '/package';
$consumerDir = $work . '/consumer';
$logDir = $work . '/logs';
$installedPackage = $consumerDir . '/vendor/simai/docara';

// A consumer build needs the exact Framework distribution for its Composition
// Recipe runtime and finds it through SIMAI_UI_ROOT; nothing else from the
// maintainer's environment is passed on.
$environment = getenv();
foreach (['SIMAI_UI_SMART_ROOT', 'DOCARA_SIMAI_UI_ROOT', 'COMPOSER'] as $name) {
    unset($environment[$name]);
}
$environment['SIMAI_UI_ROOT'] = $uiRoot;
$environment['COMPOSER_NO_INTERACTION'] = '1';

$say = static function (string $line): void {
    fwrite(STDOUT, $line . "\n");
};

/**
 * Run one command; on failure raise ConsumerInstallFailure naming the step
 * and carrying the tail of its output.
 *
 * @param  list<string>  $command
 */
$run = static function (string $step, array $command, string $cwd) use ($environment, $logDir): string {
    $log = $logDir . '/' . preg_replace('/[^a-z0-9-]+/', '-', $step) . '.log';
    $error = $log . '.stderr';
    $process = proc_open($command, [
        0 => ['file', '/dev/null', 'r'],
        1 => ['file', $log, 'w'],
        2 => ['file', $error, 'w'],
    ], $pipes, $cwd, $environment);
    if (! is_resource($process)) {
        throw new ConsumerInstallFailure($step, 'could not start: ' . implode(' ', $command));
    }
    $status = proc_close($process);
    $stdout = (string) file_get_contents($log);
    $stderr = (string) file_get_contents($error);
    if ($status !== 0) {
        $tail = implode("\n", array_slice(preg_split('/\R/', trim($stderr . "\n" . $stdout)) ?: [], -40));
        throw new ConsumerInstallFailure(
            $step,
            'exit ' . $status . ' from ' . implode(' ', $command) . "\n" . $tail,
        );
    }

    return $stdout;
};

$git = static function (string $step, string $root, array $arguments) use ($run): string {
    return $run($step, ['git', '-C', $root, ...$arguments], $root);
};

$readJson = static function (string $step, string $path): array {
    $value = is_file($path)
        ? json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)
        : null;
    if (! is_array($value)) {
        throw new ConsumerInstallFailure($step, 'missing or invalid JSON: ' . $path);
    }

    return $value;
};

$writeJson = static function (string $path, array $value): void {
    file_put_contents(
        $path,
        json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        LOCK_EX,
    );
};

/** @return array<string, string> path => sha1 for every regular file */
$fingerprint = static function (string $root): array {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $entry) {
        if ($entry->isFile()) {
            $files[substr($entry->getPathname(), strlen($root) + 1)] = (string) sha1_file($entry->getPathname());
        }
    }

    return $files;
};

$remove = static function (string $path) use (&$remove): void {
    if (is_link($path) || is_file($path)) {
        unlink($path);

        return;
    }
    if (! is_dir($path)) {
        return;
    }
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            $remove($path . '/' . $entry);
        }
    }
    rmdir($path);
};

$step = 'setup';
$exitCode = 0;
try {
    foreach ([$work, $packageDir, $consumerDir, $logDir] as $directory) {
        if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new ConsumerInstallFailure($step, 'could not create ' . $directory);
        }
    }

    // 1. Pins: the package's own runtime lock names the ui and ui-smart commits.
    $step = 'pins';
    $runtimeLock = $readJson($step, $sourceRoot . '/resources/framework/runtime-lock.json');
    $uiCommit = $runtimeLock['ui']['commit'] ?? null;
    $smartCommit = $runtimeLock['ui_smart']['commit'] ?? null;
    foreach (['ui' => $uiCommit, 'ui-smart' => $smartCommit] as $name => $commit) {
        if (! is_string($commit) || preg_match('/^[a-f0-9]{40}$/D', $commit) !== 1) {
            throw new ConsumerInstallFailure($step, 'runtime-lock.json has no exact ' . $name . ' commit');
        }
    }
    foreach ([[$uiRoot, $uiCommit, 'ui'], [$smartRoot, $smartCommit, 'ui-smart']] as [$root, $commit, $name]) {
        $resolved = trim($git($step, $root, ['rev-parse', '--verify', '--quiet', $commit . '^{commit}']));
        if ($resolved !== $commit) {
            throw new ConsumerInstallFailure($step, $name . ' checkout ' . $root . ' does not contain ' . $commit);
        }
    }
    // The build reads the ui working tree, so that one must be checked out at the pin.
    if (trim($git($step, $uiRoot, ['rev-parse', 'HEAD'])) !== $uiCommit) {
        throw new ConsumerInstallFailure($step, 'ui checkout ' . $uiRoot . ' is not checked out at ' . $uiCommit);
    }
    $say("[pins] ui {$uiCommit}, ui-smart {$smartCommit}");

    // 2. Package: the tree Composer downloads for a tag (git archive of the commit).
    $step = 'package';
    $treeish = trim($git($step, $sourceRoot, ['stash', 'create']));
    $label = $treeish === '' ? 'HEAD' : 'HEAD + tracked working-tree changes';
    if ($treeish === '') {
        $treeish = 'HEAD';
    }
    $archive = $work . '/docara.tar';
    $git($step, $sourceRoot, ['archive', '--format=tar', '--output=' . $archive, $treeish]);
    $run($step, ['tar', '-xf', $archive, '-C', $packageDir], $work);
    unlink($archive);
    $version = trim((string) @file_get_contents($packageDir . '/VERSION'));
    if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:-[0-9A-Za-z.-]+)?$/D', $version) !== 1) {
        throw new ConsumerInstallFailure($step, 'the archived VERSION is not a SemVer version');
    }
    foreach (['vendor', 'tests', '.github'] as $excluded) {
        if (file_exists($packageDir . '/' . $excluded)) {
            throw new ConsumerInstallFailure($step, 'the archive unexpectedly contains ' . $excluded . '/');
        }
    }
    $say("[package] git archive of {$label}, version {$version}");

    // 3. Composer: a fresh project requires simai/docara from that tree, copied.
    $step = 'composer-require';
    $writeJson($consumerDir . '/composer.json', [
        'name' => 'simai/docara-consumer-check',
        'type' => 'project',
        'license' => 'MIT',
        'repositories' => [[
            'type' => 'path',
            'url' => $packageDir,
            'options' => ['symlink' => false, 'versions' => ['simai/docara' => $version]],
        ]],
        'require' => new stdClass,
        'config' => [
            'allow-plugins' => false,
            'optimize-autoloader' => true,
            'platform' => ['php' => '8.2.0'],
            'sort-packages' => true,
        ],
    ]);
    $run($step, [$composer, 'require', 'simai/docara:' . $version, '--no-interaction', '--no-progress'], $consumerDir);
    if (is_link($installedPackage) || ! is_file($installedPackage . '/composer.json')) {
        throw new ConsumerInstallFailure($step, 'vendor/simai/docara is not a copied package');
    }
    if (file_exists($installedPackage . '/vendor')) {
        throw new ConsumerInstallFailure($step, 'vendor/simai/docara has a vendor/ of its own; a consumer install does not');
    }
    $say('[composer-require] simai/docara ' . $version . ' copied into vendor/simai/docara (no vendor/ of its own)');

    // 4. Init: the project root holds only composer.json, composer.lock and vendor/.
    $step = 'init';
    $run($step, [PHP_BINARY, 'vendor/bin/docara', 'init'], $consumerDir);
    $say('[init] vendor/bin/docara init');

    // 5. Project lock: the project's own Framework lock, pinned to the package's
    //    pair and carrying the old four-file eager list, written into the
    //    package-owned locks the syncs read, as the reference materializer does.
    $step = 'project-lock';
    $installed = $fingerprint($installedPackage);
    $projectLockPath = $consumerDir . '/simai-framework.lock.json';
    $packageLockPath = $installedPackage . '/docs/site/simai-framework.lock.json';
    $stubLockPath = $installedPackage . '/stubs/portable/simai-framework.lock.json';
    $lock = $readJson($step, $projectLockPath);
    $pins = [
        'runtime.ui.commit' => [$lock['runtime']['ui']['commit'] ?? null, $uiCommit],
        'runtime.ui_smart.commit' => [$lock['runtime']['ui_smart']['commit'] ?? null, $smartCommit],
        'runtime_projection.source.revision' => [$lock['runtime_projection']['source']['revision'] ?? null, $uiCommit],
        'asset_projection.source.revision' => [$lock['asset_projection']['source']['revision'] ?? null, $smartCommit],
    ];
    foreach ($pins as $field => [$actual, $expected]) {
        if ($actual !== $expected) {
            throw new ConsumerInstallFailure($step, 'the initialized lock pins ' . $field . ' to ' . var_export($actual, true) . ', not ' . $expected);
        }
    }
    $oldEager = [];
    foreach (OLD_EAGER_SMART_FILES as $path) {
        $oldEager[$path] = ['sha256' => hash('sha256', $git($step, $smartRoot, ['show', $smartCommit . ':' . $path]))];
    }
    $lock['asset_projection']['files'] = $oldEager;
    foreach ([$projectLockPath, $packageLockPath, $stubLockPath] as $path) {
        $writeJson($path, $lock);
    }
    $say('[project-lock] asset_projection.files set to the old hand list: ' . implode(', ', array_map('basename', OLD_EAGER_SMART_FILES)));

    // 6. Syncs, run from the installed package as the reference materializer runs them.
    $step = 'sync-rule-registry';
    $receipt = json_decode($run($step, [PHP_BINARY, $installedPackage . '/scripts/sync-framework-rule-registry.php', $uiRoot], $installedPackage), true);
    if (! is_array($receipt) || ($receipt['revision'] ?? null) !== $uiCommit) {
        throw new ConsumerInstallFailure($step, 'no receipt for ' . $uiCommit);
    }
    $say('[sync-rule-registry] ' . $receipt['files'] . ' runtime files, packet ' . substr((string) $receipt['packet_sha256'], 0, 12));

    $step = 'sync-smart-runtime';
    $receipt = json_decode($run($step, [PHP_BINARY, $installedPackage . '/scripts/sync-framework-smart-runtime.php', $smartRoot], $installedPackage), true);
    if (! is_array($receipt) || ($receipt['revision'] ?? null) !== $smartCommit) {
        throw new ConsumerInstallFailure($step, 'no receipt for ' . $smartCommit);
    }
    copy($packageLockPath, $projectLockPath);
    $synchronized = $readJson($step, $projectLockPath);
    $eager = array_keys($synchronized['asset_projection']['files'] ?? []);
    $after = $fingerprint($installedPackage);
    $changed = array_keys(array_diff_assoc($after, $installed) + array_diff_key($installed, $after));
    sort($changed, SORT_STRING);
    $say('[sync-smart-runtime] eager ' . $receipt['static_files'] . ' / dynamic ' . $receipt['dynamic_files'] . ' files; eager set: ' . implode(', ', array_map('basename', $eager)));
    $say('[syncs] package-owned files in vendor/simai/docara that differ from the installed copy: '
        . match (true) {
            $changed === [] => 'none (the syncs reproduced the shipped files byte for byte)',
            count($changed) <= 10 => implode(', ', $changed),
            default => count($changed) . ' file(s)',
        });

    // 7. Build and verify, from the consumer project.
    $step = 'build';
    $run($step, [PHP_BINARY, 'vendor/bin/docara', 'build', 'production'], $consumerDir);
    $say('[build] vendor/bin/docara build production');

    $step = 'verify-static';
    $run($step, [PHP_BINARY, 'vendor/bin/docara', 'verify-static', 'build_production'], $consumerDir);
    $say('[verify-static] vendor/bin/docara verify-static build_production');

    $say('CONSUMER_INSTALL_PASSED');
} catch (Throwable $exception) {
    $failedStep = $exception instanceof ConsumerInstallFailure ? $exception->step : $step;
    fwrite(STDERR, 'CONSUMER_INSTALL_FAILED at step [' . $failedStep . ']: ' . $exception->getMessage() . "\n");
    $exitCode = 1;
} finally {
    if ($keep) {
        fwrite(STDERR, 'Kept work directory: ' . $work . "\n");
    } else {
        $remove($work);
    }
}

exit($exitCode);
