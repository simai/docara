<?php

namespace Simai\Docara\Console;

use Dotenv\Dotenv;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Scaffold\BasicScaffoldBuilder;
use Simai\Docara\Scaffold\InstallerCommandException;
use Simai\Docara\Scaffold\PresetScaffoldBuilder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class InitCommand extends Command
{
    private $base;

    private bool $autoloadAdded = false;

    private bool $forceCoreConfigs = false;

    /** @var array<string, string> */
    private array $frontendEnvironmentBaseline = [];

    private $basicScaffold;

    private $files;

    private $presetScaffold;

    public function __construct(Filesystem $files, BasicScaffoldBuilder $basicScaffold, PresetScaffoldBuilder $presetScaffold)
    {
        $this->basicScaffold = $basicScaffold;
        $this->presetScaffold = $presetScaffold;
        $this->files = $files;
        $this->setBase();
        parent::__construct();
    }

    public function setBase($cwd = null)
    {
        $this->base = $cwd ?: getcwd();

        return $this;
    }

    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Scaffold a new Docara project.')
            ->addArgument(
                'preset',
                InputArgument::OPTIONAL,
                'Which preset should we use to initialize this project?',
            )
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update existing site in-place (no delete/archive).')
            ->addOption('portable', null, InputOption::VALUE_NONE, 'Initialize the portable JSON/Markdown site format.')
            ->addOption('force-core-configs', null, InputOption::VALUE_NONE, 'Overwrite template configs even if modified locally.')
            ->addOption('force-core-files', null, InputOption::VALUE_NONE, 'Overwrite all files in source/_core from stubs (ignores user changes).');
    }

    protected function fire()
    {
        if ((bool) $this->input->getOption('portable')) {
            return $this->firePortable();
        }

        $this->frontendEnvironmentBaseline = $this->captureProcessEnvironment();

        if (! $this->frontendLockPreflight()
            || ! $this->frontendConfigurationPreflight()
            || ! $this->frontendPackagePreflight()
            || ! $this->frontendToolPreflight()
            || ! $this->legacyMutationPreflight()
        ) {
            return static::FAILURE;
        }

        $envPath = $this->base . '/.env';
        if (file_exists($envPath)) {
            $this->loadEnv();
        }
        if (! file_exists($envPath)) {
            $examplePath = $this->base . '/stubs/site/.env.example';
            $created = false;

            if (file_exists($examplePath)) {
                $created = copy($examplePath, $envPath);
            }

            if (! $created) {
                $docsDirValue = 'docs';
                $defaultEnv = <<<ENV
DOCS_DIR={$docsDirValue}
AZURE_KEY=''
AZURE_REGION=''
AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
ENV;
                $created = (bool) file_put_contents($envPath, $defaultEnv . PHP_EOL);
            }

            if ($created) {
                $this->console->comment('Missing .env was created with default values. Update it if needed and rerun init if this was not intended.');
                $this->loadEnv();
            } else {
                $this->console->error('Missing .env in project root. Please create it (DOCS_DIR, AZURE_*, etc.) and rerun init.');

                return static::FAILURE;
            }
        }

        $updateMode = $this->input->getOption('update');
        $existing = $this->detectExistingSite();

        if (! empty($existing) && ! $updateMode) {
            $this->console
                ->line()
                ->comment('Detected existing site files: ' . implode(', ', $existing))
                ->comment('What would you like to do? (default: update in-place)')
                ->line();

            $action = $this->askExistingSiteAction();
            switch ($action) {
                case 'update':
                    $updateMode = true;
                    break;

                case 'archive':
                    $this->console->comment('Archiving your existing site...');
                    $this->getScaffold()->setBase($this->base)->archiveExistingSite();
                    break;

                case 'delete':
                    if ($this->console->confirm('<fg=red>Are you sure you want to delete your existing site?</>')) {
                        $this->console->comment('Deleting your existing site...');
                        $this->getScaffold()->setBase($this->base)->deleteExistingSite();
                        break;
                    }

                    return;

                case 'cancel':
                default:
                    return;
            }
        }

        $scaffold = $this->getScaffold()->setBase($this->base);
        $this->forceCoreConfigs = (bool) $this->input->getOption('force-core-configs');
        $forceCoreFiles = (bool) $this->input->getOption('force-core-files');
        if (method_exists($scaffold, 'setForceCoreFiles')) {
            $scaffold->setForceCoreFiles($forceCoreFiles);
        }
        if (method_exists($scaffold, 'setUpdateMode')) {
            $scaffold->setUpdateMode((bool) $updateMode);
        }

        try {
            $this->confirmDocsDirExistsOrAsk();
            $scaffold->init($this->input->getArgument('preset'));
        } catch (Exception $e) {
            $this->console->error($e->getMessage())->line();

            return static::FAILURE;
        }

        if ($updateMode) {
            $cacheDir = $this->base . '/.cache';
            $buildDirs = glob($this->base . '/build_*', GLOB_ONLYDIR) ?: [];
            if ($this->files->isDirectory($cacheDir)) {
                $this->console->comment("Update mode: removing cache directory {$cacheDir}");
                $this->files->deleteDirectory($cacheDir);
            }
            foreach ($buildDirs as $buildDir) {
                $this->console->comment("Update mode: removing build directory {$buildDir}");
                $this->files->deleteDirectory($buildDir);
            }

            $this->console->comment('Update mode: copying stubs and refreshing dependencies without deleting the project...');
        }

        try {
            $scaffold->setConsole($this->console)->build();

            $this->copyTemplateConfigsPreservingUserChanges();
            $this->ensureAppPsr4Autoload();
            $this->ensureDocsCreateComposerScript();
            $this->installFrontendDependencies($this->base, 'project root');

            $suffix = $scaffold instanceof $this->presetScaffold && $scaffold->package ?
                " using the '" . $scaffold->package->shortName . "' preset." :
                ' successfully.';

            $this->console
                ->line()
                ->info('Your Docara site was initialized' . $suffix)
                ->line();
            if ($this->autoloadAdded) {
                $this->console
                    ->line()
                    ->comment(str_repeat('=', 60))
                    ->comment('PSR-4 autoload updated. Please run: composer dump-autoload -o')
                    ->comment(str_repeat('=', 60))
                    ->line();
            }
        } catch (InstallerCommandException $e) {
            $this->console
                ->line()
                ->error("There was an error running the command '" . $e->getMessage() . "'")
                ->line();

            return static::FAILURE;
        }

        return static::SUCCESS;
    }

    /**
     * Initialize the opt-in portable site format without invoking the legacy
     * source/_core, Composer autoload or frontend dependency setup.
     */
    private function firePortable(): int
    {
        $preset = (string) $this->input->getArgument('preset');
        if ($preset !== '') {
            $this->console->error('Portable mode does not accept a legacy preset argument. Configure the preset in docara.json.');

            return static::FAILURE;
        }

        $updateMode = (bool) $this->input->getOption('update');
        $portableMarkers = $this->detectPortableSite();
        $legacyMarkers = $this->detectExistingSite();

        if ($legacyMarkers !== []) {
            $this->console
                ->error('Refusing to migrate an existing legacy site implicitly.')
                ->comment('Portable mode requires a clean directory. Migrate legacy sites with a dedicated migration workflow.');

            return static::FAILURE;
        }

        if ($portableMarkers !== [] && ! $updateMode) {
            $this->console
                ->error('Detected an existing portable site: ' . implode(', ', $portableMarkers))
                ->comment('Run "docara init --portable --update" to add missing portable scaffold files without overwriting JSON or Markdown.');

            return static::FAILURE;
        }

        try {
            $this->basicScaffold
                ->setBase($this->base)
                ->setPortableMode()
                ->setUpdateMode($updateMode)
                ->setConsole($this->console)
                ->build();
        } catch (InstallerCommandException $e) {
            $this->console->error($e->getMessage());

            return static::FAILURE;
        }

        $this->console
            ->line()
            ->info($updateMode
                ? 'Your portable Docara site was updated without overwriting existing JSON or Markdown.'
                : 'Your portable Docara site was initialized successfully.')
            ->line();

        return static::SUCCESS;
    }

    protected function getScaffold()
    {
        return $this->input->getArgument('preset') ?
            $this->presetScaffold :
            $this->basicScaffold;
    }

    /**
     * Detects markers of an initialized site.
     *
     * @return array<string>
     */
    private function detectExistingSite(): array
    {
        $existing = [];

        if ($this->files->exists($this->base . '/config.php')) {
            $existing[] = 'config.php';
        }

        if ($this->files->exists($this->base . '/source')) {
            $existing[] = 'source/';
        }

        return $existing;
    }

    /**
     * Detects markers of the opt-in portable format.
     *
     * @return array<string>
     */
    private function detectPortableSite(): array
    {
        $existing = [];

        foreach ([
            'docara.json',
            'simai-framework.lock.json',
            'content',
        ] as $path) {
            if ($this->files->exists($this->base . '/' . $path)) {
                $existing[] = is_dir($this->base . '/' . $path) ? $path . '/' : $path;
            }
        }

        return $existing;
    }

    private function askExistingSiteAction(): string
    {
        $choices = [
            'u' => '<info>update</info> (keep docs, overwrite stubs; same as --update)',
            'a' => '<info>archive</info> current site then reinitialize',
            'd' => '<info>delete</info> current site then reinitialize',
            'c' => '<info>cancel</info>',
        ];

        $response = $this->console->ask('Choose action', 'u', $choices);

        return match ($response) {
            'a' => 'archive',
            'd' => 'delete',
            'c' => 'cancel',
            default => 'update',
        };
    }

    /**
     * Copy core files from stubs but do not overwrite files the user changed.
     * Change detection uses a whitespace-insensitive hash (line endings normalized, whitespace removed).
     *
     * @return array{int,int} [copied, skipped]
     */
    private function copyCorePreservingUserChanges(string $source, string $target): array
    {
        $copied = 0;
        $skipped = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($source))), '/');
            if (str_starts_with($relative, '.git/')) {
                continue;
            }

            $dest = rtrim($target, '/\\') . '/' . $relative;
            $destDir = dirname($dest);
            if (! $this->files->isDirectory($destDir)) {
                $this->files->makeDirectory($destDir, 0755, true);
            }

            if (! $this->files->exists($dest)) {
                $this->files->copy($file->getPathname(), $dest);
                $copied++;

                continue;
            }

            $srcHash = $this->normalizedHash($file->getPathname());
            $dstHash = $this->normalizedHash($dest);

            if ($srcHash === $dstHash) {
                // Safe to update (same content modulo whitespace).
                $this->files->copy($file->getPathname(), $dest);
                $copied++;
            } else {
                $skipped++;
            }
        }

        return [$copied, $skipped];
    }

    /**
     * Normalize file content (line endings + whitespace) and hash it.
     * Binary files keep raw hash to avoid mangling.
     */
    private function normalizedHash(string $path): string
    {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            return '';
        }

        // Treat binary files (with null byte) as raw hash.
        if (str_contains($contents, "\0")) {
            return md5($contents);
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $contents);
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;

        return md5($normalized);
    }

    /**
     * Copy template config/build files from _core without clobbering user changes.
     */
    private function copyTemplateConfigsPreservingUserChanges(): void
    {
        $core = $this->canonicalCorePath();
        if (! $this->files->isDirectory($core)) {
            return;
        }

        $rootFiles = [
            'vite.config.js',
            'bootstrap.php',
            'translate.config.php',
            'eslint.config.js',
        ];

        $sourceFiles = [
            '404.blade.php',
            'favicon.ico',
        ];

        $stats = ['copied' => 0, 'updated' => 0, 'skipped' => 0, 'forced' => 0];

        foreach ($rootFiles as $file) {
            $this->copyIfUnchanged("{$core}/{$file}", "{$this->base}/{$file}", $stats);
        }

        $this->mergeCanonicalPackageJson("{$core}/package.json", "{$this->base}/package.json", $stats);
        $this->copyCanonicalLockfile("{$core}/yarn.lock", "{$this->base}/yarn.lock", $stats);

        foreach ($sourceFiles as $file) {
            $this->copyIfUnchanged("{$core}/{$file}", "{$this->base}/source/{$file}", $stats);
        }

        $this->console->comment(sprintf(
            'Template configs: copied=%d, updated=%d, forced=%d, skipped=%d',
            $stats['copied'],
            $stats['updated'],
            $stats['forced'],
            $stats['skipped'],
        ));
    }

    /**
     * Keep project identity and additional scripts while Docara remains the
     * owner of the frontend toolchain, dependency graph and standard scripts.
     */
    private function mergeCanonicalPackageJson(string $source, string $destination, array &$stats): void
    {
        if (! $this->files->exists($source)) {
            return;
        }

        if (! $this->files->exists($destination)) {
            $contents = @file_get_contents($source);
            if (! is_string($contents)) {
                throw new InstallerCommandException('Could not read the canonical package.json.');
            }
            $this->writeFileAtomically($destination, $contents, 'package.json');
            $stats['copied']++;

            return;
        }

        $canonical = json_decode((string) file_get_contents($source), true);
        $project = json_decode((string) file_get_contents($destination), true);
        if (! is_array($canonical) || ! is_array($project)) {
            $stats['skipped']++;
            $this->console->comment('Skipped package.json because it is not valid JSON.');

            return;
        }

        $ownedKeys = ['private', 'packageManager', 'engines', 'scripts', 'dependencies', 'devDependencies'];
        $merged = array_diff_key($project, array_fill_keys($ownedKeys, true));
        foreach (['private', 'packageManager', 'engines', 'dependencies', 'devDependencies'] as $key) {
            if (array_key_exists($key, $canonical)) {
                $merged[$key] = $canonical[$key];
            }
        }

        $canonicalScripts = is_array($canonical['scripts'] ?? null) ? $canonical['scripts'] : [];
        $projectScripts = is_array($project['scripts'] ?? null) ? $project['scripts'] : [];
        $merged['scripts'] = array_replace($projectScripts, $canonicalScripts);

        $encoded = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if ((string) file_get_contents($destination) === $encoded) {
            $stats['updated']++;

            return;
        }

        $this->writeFileAtomically($destination, $encoded, 'package.json');
        $stats['updated']++;
        $this->console->comment('Updated package.json from the canonical Docara contract; project identity and additional scripts were preserved.');
    }

    private function copyCanonicalLockfile(string $source, string $destination, array &$stats): void
    {
        if (! $this->files->exists($source)) {
            return;
        }

        $directory = dirname($destination);
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $existed = $this->files->exists($destination);
        $contents = @file_get_contents($source);
        if (! is_string($contents)) {
            throw new InstallerCommandException('Could not read the canonical yarn.lock.');
        }
        $this->writeFileAtomically($destination, $contents, 'yarn.lock');
        $stats[$existed ? 'updated' : 'copied']++;
    }

    private function writeFileAtomically(string $destination, string $contents, string $label): void
    {
        $temporary = $destination . '.docara-' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new InstallerCommandException("Could not write temporary {$label}.");
        }
        if (! @rename($temporary, $destination)) {
            @unlink($temporary);
            throw new InstallerCommandException("Could not replace {$label} atomically.");
        }
    }

    /**
     * Copy $src to $dest if dest missing or has the same normalized hash (whitespace/line endings ignored).
     */
    private function copyIfUnchanged(string $src, string $dest, array &$stats): void
    {
        if (! $this->files->exists($src)) {
            return;
        }

        $destDir = dirname($dest);
        if (! $this->files->isDirectory($destDir)) {
            $this->files->makeDirectory($destDir, 0755, true);
        }

        if (! $this->files->exists($dest)) {
            $this->files->copy($src, $dest);
            $stats['copied']++;
            $this->console->comment("Copied {$src} -> {$dest}");

            return;
        }

        if ($this->forceCoreConfigs) {
            $this->files->copy($src, $dest);
            $stats['forced']++;
            $this->console->comment("Forced overwrite of {$dest} (--force-core-configs).");

            return;
        }

        $srcHash = $this->normalizedHash($src);
        $dstHash = $this->normalizedHash($dest);

        if ($srcHash === $dstHash) {
            $this->files->copy($src, $dest);
            $stats['updated']++;
            $this->console->comment("Updated {$dest} (no user changes detected).");
        } else {
            $stats['skipped']++;
            $this->console->comment("Skipped {$dest} (user changes detected).");
        }
    }

    private function ensureAppPsr4Autoload(): void
    {
        $composerPath = $this->base . '/composer.json';
        if (! file_exists($composerPath)) {
            $this->console->comment('composer.json not found in project root; skipped autoload check for App\\ namespace.');

            return;
        }

        $contents = file_get_contents($composerPath);
        $data = json_decode($contents, true);
        if (! is_array($data)) {
            $this->console->comment('Could not parse composer.json; skipped autoload update.');

            return;
        }

        $autoload = $data['autoload'] ?? [];
        $psr4 = $autoload['psr-4'] ?? [];

        $current = $psr4['App\\'] ?? null;
        if ($current === 'source/') {
            return;
        }

        if ($current !== null && $current !== 'source/') {
            $this->console->comment("composer.json already defines App\\ autoload ({$current}); leaving it unchanged.");

            return;
        }

        $addedFromScratch = empty($psr4);
        $psr4['App\\'] = 'source/';
        $autoload['psr-4'] = $psr4;
        $data['autoload'] = $autoload;

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($composerPath, $encoded);
        $this->console->comment('Added App\\ => source/ to composer.json autoload. Run "composer dump-autoload" to apply.');
        $this->autoloadAdded = true;
        if ($addedFromScratch) {
            $this->console->comment('Warning: composer.json had no PSR-4 autoload; default mapping was added automatically.');
        }
    }

    private function ensureDocsCreateComposerScript(): void
    {
        $composerPath = $this->base . '/composer.json';
        if (! file_exists($composerPath)) {
            $this->console->comment('composer.json not found in project root; skipped docs:create script setup.');

            return;
        }

        $contents = file_get_contents($composerPath);
        $data = json_decode($contents, true);
        if (! is_array($data)) {
            $this->console->comment('Could not parse composer.json; skipped docs:create script setup.');

            return;
        }

        $scripts = $data['scripts'] ?? [];
        $desired = 'php bin/docs-create.php';
        $current = is_array($scripts) ? ($scripts['docs:create'] ?? null) : null;

        if ($current === $desired) {
            return;
        }

        if ($current !== null && $current !== $desired) {
            $this->console->comment('composer.json already defines docs:create script; leaving it unchanged.');

            return;
        }

        if (! is_array($scripts)) {
            $scripts = [];
        }

        $scripts['docs:create'] = $desired;
        $data['scripts'] = $scripts;

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($composerPath, $encoded);
        $this->console->comment('Added docs:create script to composer.json.');
    }

    private function installFrontendDependencies(string $path, string $label): void
    {
        $skip = filter_var(getenv('DOCARA_SKIP_FRONTEND_INSTALL') ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($skip) {
            $this->console->comment("Skipping frontend dependency install for {$label} (DOCARA_SKIP_FRONTEND_INSTALL=true).");

            return;
        }

        $packageJson = $path . '/package.json';
        if (! file_exists($packageJson)) {
            return;
        }

        $hasYarnLock = file_exists($path . '/yarn.lock');
        $hasNpmLock = file_exists($path . '/package-lock.json');
        if ($hasYarnLock && $hasNpmLock) {
            throw new InstallerCommandException(
                "Frontend dependency contract is ambiguous in {$label}: keep exactly one lockfile.",
            );
        }
        if (! $hasYarnLock && ! $hasNpmLock) {
            throw new InstallerCommandException(
                "Frontend dependency lockfile is missing in {$label}; refusing a non-reproducible install.",
            );
        }

        $cmd = $hasYarnLock
            ? [
                'yarn', '--no-default-rc', 'install', '--frozen-lockfile',
                '--production=false', '--non-interactive',
            ]
            : ['npm', 'ci'];
        $tool = $hasYarnLock ? 'yarn' : 'npm';

        $this->console->comment("Installing frontend dependencies in {$label} via {$tool}...");

        try {
            $environment = $hasYarnLock ? $this->frontendProcessEnvironment() : null;
            $process = new Process($cmd, $path, $environment, null, 300);
            $process->run();
        } catch (\Throwable $e) {
            throw new InstallerCommandException(
                "Could not run {$tool} install in {$label}: {$e->getMessage()}",
                previous: $e,
            );
        }

        if (! $process->isSuccessful()) {
            throw new InstallerCommandException(
                "{$tool} install failed in {$label}: " . trim($process->getErrorOutput()),
            );
        }

        $this->console->comment("{$tool} frozen install complete for {$label}.");
    }

    /**
     * The canonical maintainer scaffold owns one frozen Yarn graph. Reject a
     * legacy npm graph before creating .env, clearing builds, or copying any
     * scaffold file so migration can never leave a partially changed tree.
     */
    private function frontendLockPreflight(): bool
    {
        $yarnPath = $this->base . '/yarn.lock';
        $npmPath = $this->base . '/package-lock.json';
        $hasYarnLock = file_exists($yarnPath) || is_link($yarnPath);
        $hasNpmLock = file_exists($npmPath) || is_link($npmPath);

        foreach ([$yarnPath, $npmPath] as $path) {
            if ((file_exists($path) || is_link($path)) && $this->unsafeWritableFile($path)) {
                $this->console->error('Frontend lockfile must be one regular project file; init did not change the project.');

                return false;
            }
        }

        if ($hasYarnLock && $hasNpmLock) {
            $this->console->error(
                'Frontend dependency contract is ambiguous: keep exactly one lockfile before running Docara init.',
            );

            return false;
        }
        if ($hasNpmLock) {
            $this->console
                ->error('Legacy npm-only frontend contract detected; Docara init did not change the project.')
                ->comment('The canonical maintainer scaffold uses Yarn. Migrate and review the frozen lockfile explicitly, remove package-lock.json, then rerun init.');

            return false;
        }

        return true;
    }

    /**
     * Project Yarn configuration can replace the Yarn executable or redirect
     * writes outside the project before a frozen install begins. Docara owns
     * the frontend contract, so these extension surfaces are fail-closed.
     */
    private function frontendConfigurationPreflight(): bool
    {
        foreach (['.yarnrc', '.yarnrc.yml', '.yarn', '.yarnclean', '.npmrc'] as $entry) {
            $path = $this->base . '/' . $entry;
            if (! file_exists($path) && ! is_link($path)) {
                continue;
            }

            $this->console
                ->error("Unsupported frontend configuration [{$entry}]; init did not change the project.")
                ->comment('Docara owns the Yarn configuration. Remove the project override and rerun init.');

            return false;
        }

        return true;
    }

    /**
     * Validate the complete frontend ownership contract before .env, cache,
     * scaffold, build output, package.json, or lockfile can be changed.
     */
    private function frontendPackagePreflight(): bool
    {
        $canonicalPath = $this->canonicalCorePath() . '/package.json';
        $canonicalContents = @file_get_contents($canonicalPath);
        $canonicalObject = is_string($canonicalContents) ? json_decode($canonicalContents) : null;
        $canonical = is_string($canonicalContents) ? json_decode($canonicalContents, true) : null;
        $canonicalLockPath = dirname($canonicalPath) . '/yarn.lock';
        if ($this->unsafeWritableFile($canonicalPath)
            || $this->unsafeWritableFile($canonicalLockPath)
            || ! $canonicalObject instanceof \stdClass
            || ! is_array($canonical)
            || ! is_readable($canonicalLockPath)
        ) {
            $this->console->error('Canonical Docara package.json is missing or invalid; init did not change the project.');

            return false;
        }

        $canonicalPackageManager = $canonical['packageManager'] ?? null;
        if (! is_string($canonicalPackageManager) || $canonicalPackageManager !== 'yarn@1.22.22') {
            $this->console->error('Canonical Docara package manager contract is invalid; init did not change the project.');

            return false;
        }
        $canonicalNodeRange = $canonical['engines']['node'] ?? null;
        if ($canonicalNodeRange !== '^20.19.0 || >=22.12.0') {
            $this->console->error('Canonical Docara Node.js engine contract is invalid; init did not change the project.');

            return false;
        }

        $projectLockPath = $this->base . '/yarn.lock';
        if (! is_writable($this->base)
            || is_link($projectLockPath)
            || (file_exists($projectLockPath) && ($this->unsafeWritableFile($projectLockPath) || ! is_writable($projectLockPath)))
            || (! file_exists($projectLockPath) && ! is_writable($this->base))
        ) {
            $this->console->error('Project yarn.lock cannot be refreshed; init did not change the project.');

            return false;
        }

        $projectPath = $this->base . '/package.json';
        if (is_link($projectPath)) {
            $this->console->error('Project package.json must be a regular project file; init did not change the project.');

            return false;
        }
        if (! file_exists($projectPath)) {
            return true;
        }
        if ($this->unsafeWritableFile($projectPath)
            || ! is_readable($projectPath)
            || ! is_writable($projectPath)
        ) {
            $this->console->error('Project package.json must be readable and writable; init did not change the project.');

            return false;
        }

        $projectContents = @file_get_contents($projectPath);
        $projectObject = is_string($projectContents) ? json_decode($projectContents) : null;
        $project = is_string($projectContents) ? json_decode($projectContents, true) : null;
        if (! $projectObject instanceof \stdClass || ! is_array($project)) {
            $this->console->error('Project package.json must be a valid JSON object; init did not change the project.');

            return false;
        }

        if (array_key_exists('packageManager', $project)
            && $project['packageManager'] !== $canonicalPackageManager
        ) {
            $this->console
                ->error('Project packageManager does not match the canonical Docara Yarn revision; init did not change the project.')
                ->comment("Required: {$canonicalPackageManager}");

            return false;
        }
        if (array_key_exists('engines', $project)
            && $project['engines'] !== $canonical['engines']
        ) {
            $this->console
                ->error('Project Node.js engine does not match the canonical Docara runtime; init did not change the project.')
                ->comment("Required: {$canonicalNodeRange}");

            return false;
        }

        foreach (['scripts', 'dependencies', 'devDependencies'] as $key) {
            if (array_key_exists($key, $project)
                && (! is_array($project[$key]) || ! (($projectObject->{$key} ?? null) instanceof \stdClass))
            ) {
                $this->console->error("Project package.json key [{$key}] must be an object; init did not change the project.");

                return false;
            }
        }

        $projectScripts = $project['scripts'] ?? [];
        foreach ($projectScripts as $name => $command) {
            if (! is_string($name) || ! is_string($command)) {
                $this->console->error('Project package.json scripts must map names to command strings; init did not change the project.');

                return false;
            }
        }
        $automaticScripts = [
            'preinstall', 'install', 'postinstall', 'prepublish', 'prepublishOnly',
            'prepare', 'publish', 'postpublish', 'prepack', 'postpack', 'dependencies',
        ];
        foreach (array_keys($canonical['scripts'] ?? []) as $canonicalScript) {
            $automaticScripts[] = 'pre' . $canonicalScript;
            $automaticScripts[] = 'post' . $canonicalScript;
        }
        $unsafeScripts = array_intersect(array_keys($projectScripts), array_unique($automaticScripts));
        if ($unsafeScripts !== []) {
            $this->console
                ->error('Project package.json contains automatically executed lifecycle scripts; init did not change the project.')
                ->comment('Unsupported scripts: ' . implode(', ', $unsafeScripts));

            return false;
        }

        foreach ([
            'workspaces', 'resolutions', 'overrides', 'peerDependencies',
            'optionalDependencies', 'bundledDependencies', 'bundleDependencies',
            'installConfig', 'flat', 'devEngines', 'os', 'cpu', 'libc',
        ] as $key) {
            if (! empty($project[$key])) {
                $this->console
                    ->error("Project package.json defines unsupported frontend graph key [{$key}]; init did not change the project.")
                    ->comment('Move the custom frontend graph behind an explicit Docara extension contract before retrying.');

                return false;
            }
        }

        foreach (['dependencies', 'devDependencies'] as $key) {
            $projectDependencies = is_array($project[$key] ?? null) ? $project[$key] : [];
            $canonicalDependencies = is_array($canonical[$key] ?? null) ? $canonical[$key] : [];
            $additional = array_diff_key($projectDependencies, $canonicalDependencies);
            if ($additional !== []) {
                $this->console
                    ->error("Project package.json extends canonical {$key}; init did not change the project.")
                    ->comment('Unsupported packages: ' . implode(', ', array_keys($additional)));

                return false;
            }
        }

        return true;
    }

    private function frontendToolPreflight(): bool
    {
        $skip = filter_var(getenv('DOCARA_SKIP_FRONTEND_INSTALL') ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($skip) {
            return true;
        }

        try {
            $node = new Process(
                ['node', '--version'],
                $this->base,
                $this->frontendProcessEnvironment(),
                null,
                15,
            );
            $node->run();
            $nodeVersion = ltrim(trim($node->getOutput()), 'vV');
            $nodeSupported = $node->isSuccessful()
                && preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+(?:[-+][0-9A-Za-z.-]+)?\z/', $nodeVersion) === 1
                && (
                    (version_compare($nodeVersion, '20.19.0', '>=') && version_compare($nodeVersion, '21.0.0', '<'))
                    || version_compare($nodeVersion, '22.12.0', '>=')
                );
            if (! $nodeSupported) {
                $actual = trim($node->getOutput() . ' ' . $node->getErrorOutput());
                $this->console
                    ->error('Node.js 20.19+ or 22.12+ is required; init did not change the project.')
                    ->comment('Detected: ' . ($actual !== '' ? $actual : 'unavailable'));

                return false;
            }

            $process = new Process(
                ['yarn', '--no-default-rc', '--version'],
                $this->base,
                $this->frontendProcessEnvironment(),
                null,
                15,
            );
            $process->run();
        } catch (\Throwable $exception) {
            $this->console->error('Yarn 1.22.22 is required; init did not change the project.');

            return false;
        }

        if (! $process->isSuccessful() || trim($process->getOutput()) !== '1.22.22') {
            $actual = trim($process->getOutput() . ' ' . $process->getErrorOutput());
            $this->console
                ->error('Yarn 1.22.22 is required; init did not change the project.')
                ->comment('Detected: ' . ($actual !== '' ? $actual : 'unavailable'));

            return false;
        }

        return true;
    }

    /** @return array<string, string|false> */
    private function frontendProcessEnvironment(): array
    {
        $current = $this->captureProcessEnvironment();
        $environment = array_fill_keys(
            array_unique(array_merge(array_keys($current), array_keys($this->frontendEnvironmentBaseline))),
            false,
        );
        $allowed = [
            'PATH', 'HOME', 'USER', 'LOGNAME', 'SHELL', 'TERM',
            'TMPDIR', 'TMP', 'TEMP', 'SystemRoot', 'COMSPEC', 'PATHEXT',
            'USERPROFILE', 'HOMEDRIVE', 'HOMEPATH', 'APPDATA', 'LOCALAPPDATA',
            'LANG', 'LC_ALL', 'LC_CTYPE',
            'HTTP_PROXY', 'HTTPS_PROXY', 'NO_PROXY',
            'http_proxy', 'https_proxy', 'no_proxy',
            'SSL_CERT_FILE', 'SSL_CERT_DIR', 'NODE_EXTRA_CA_CERTS',
        ];
        $allowedLookup = array_fill_keys(array_map('strtoupper', $allowed), true);
        foreach ($this->frontendEnvironmentBaseline as $key => $value) {
            if (isset($allowedLookup[strtoupper($key)])) {
                $environment[$key] = $value;
            }
        }
        foreach (array_keys($environment) as $key) {
            if (preg_match('/\A(?:NODE_OPTIONS|NODE_ENV|NODE_PATH|YARN_.+|NPM_CONFIG_.+|COREPACK_.+)\z/i', $key) === 1) {
                $environment[$key] = false;
            }
        }
        $environment['YARN_IGNORE_PATH'] = '1';

        return $environment;
    }

    /** @return array<string, string> */
    private function captureProcessEnvironment(): array
    {
        $environment = [];
        $sources = [getenv() ?: [], $_SERVER, $_ENV];
        foreach ($sources as $source) {
            foreach ($source as $key => $value) {
                if (! is_string($key)
                    || $key === ''
                    || str_contains($key, '=')
                    || str_contains($key, "\0")
                    || (! is_scalar($value) && ! $value instanceof \Stringable)
                ) {
                    continue;
                }
                $environment[$key] = (string) $value;
            }
        }

        return $environment;
    }

    /**
     * Legacy init refreshes and removes several paths. Refuse links, hardlinks
     * and special files before .env is loaded or any project file is changed.
     */
    private function legacyMutationPreflight(): bool
    {
        if (is_link($this->base) || ! is_dir($this->base) || ! is_writable($this->base)) {
            $this->console->error('Unsafe or unwritable project root; init did not change the project.');

            return false;
        }

        $nodeModules = $this->base . '/node_modules';
        if ((file_exists($nodeModules) || is_link($nodeModules))
            && $this->unsafeNodeModulesBoundary($nodeModules)
        ) {
            $this->console->error('Unsafe init boundary [node_modules]; init did not change the project.');

            return false;
        }

        $boundaries = [
            $this->base . '/.env',
            $this->base . '/.env.example',
            $this->base . '/.gitignore',
            $this->base . '/config.php',
            $this->base . '/composer.json',
            $this->base . '/package.json',
            $this->base . '/yarn.lock',
            $this->base . '/vite.config.js',
            $this->base . '/bootstrap.php',
            $this->base . '/translate.config.php',
            $this->base . '/eslint.config.js',
            $this->base . '/source',
            $this->base . '/.cache',
            $this->base . '/yarn-error.log',
            $this->base . '/archived',
        ];
        array_push($boundaries, ...(glob($this->base . '/build_*') ?: []));

        foreach (array_unique($boundaries) as $path) {
            if (! file_exists($path) && ! is_link($path)) {
                continue;
            }
            if ($this->unsafeMutationBoundary($path)) {
                $relative = ltrim(str_replace($this->base, '', $path), '/\\');
                $this->console->error("Unsafe init boundary [{$relative}]; init did not change the project.");

                return false;
            }
        }

        return true;
    }

    private function unsafeNodeModulesBoundary(string $path): bool
    {
        if (is_link($path) || ! is_dir($path)) {
            return true;
        }
        $resolvedRoot = realpath($path);
        if ($resolvedRoot === false || ! is_writable($resolvedRoot)) {
            return true;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolvedRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            if ($item->isLink()) {
                $target = realpath($itemPath);
                if ($target === false
                    || ($target !== $resolvedRoot && ! str_starts_with($target, $resolvedRoot . DIRECTORY_SEPARATOR))
                ) {
                    return true;
                }

                continue;
            }
            if ($item->isFile()) {
                $stat = @lstat($itemPath);
                if (! is_array($stat)
                    || (($stat['mode'] ?? 0) & 0170000) !== 0100000
                    || ($stat['nlink'] ?? 1) > 1
                ) {
                    return true;
                }

                continue;
            }
            if (! $item->isDir()) {
                return true;
            }
        }

        return false;
    }

    private function unsafeMutationBoundary(string $path): bool
    {
        if (is_link($path)) {
            return true;
        }
        if (is_file($path)) {
            return $this->unsafeWritableFile($path);
        }
        if (! is_dir($path)) {
            return true;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            if ($item->isLink()) {
                return true;
            }
            if ($item->isFile()) {
                $stat = @lstat($itemPath);
                if (is_array($stat) && ($stat['nlink'] ?? 1) > 1) {
                    return true;
                }

                continue;
            }
            if (! $item->isDir()) {
                return true;
            }
        }

        return false;
    }

    private function canonicalCorePath(): string
    {
        return dirname(__DIR__, 2) . '/stubs/site/source/_core';
    }

    private function unsafeWritableFile(string $path): bool
    {
        if (is_link($path)) {
            return true;
        }
        $stat = @lstat($path);

        return ! is_array($stat)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) > 1;
    }

    protected function initHasAlreadyBeenRun()
    {
        return $this->files->exists($this->base . '/config.php')
            || $this->files->exists($this->base . '/source');
    }

    protected function askUserWhatToDoWithExistingSite()
    {
        $this->console
            ->line()
            ->comment("It looks like you've already run 'docara init' on this project.")
            ->comment('Running it again will overwrite important files.')
            ->line();

        $choices = [
            'a' => '<info>archive</info> your existing site, then initialize a new one',
            'd' => '<info>delete</info> your existing site, then initialize a new one',
            'c' => '<info>cancel</info>',
        ];

        return $this->console->ask('What would you like to do?', 'a', $choices);
    }

    /**
     * Warn user if configured DOCS_DIR does not exist; allow them to continue or abort.
     */
    private function confirmDocsDirExistsOrAsk(): void
    {
        $docsDir = trim($_ENV['DOCS_DIR'] ?? getenv('DOCS_DIR') ?? 'docs', '/\\');
        $target = $this->base . '/source/' . $docsDir;

        if (is_dir($target)) {
            return;
        }

        $this->console
            ->line()
            ->comment("<fg=yellow>Warning:</> DOCS_DIR={$docsDir} does not exist at {$target}. Docs stubs will be copied there.")
            ->line();

        if (! $this->console->confirm('Continue anyway?', true)) {
            throw new InstallerCommandException('Aborted by user because DOCS_DIR does not exist.');
        }
    }

    private function loadEnv(): void
    {
        if (! class_exists(Dotenv::class)) {
            return;
        }

        try {
            $dotenv = Dotenv::createImmutable($this->base);
            $dotenv->safeLoad();
        } catch (\Throwable) {
            // Non-fatal: continue with whatever env is available.
        }
    }
}
