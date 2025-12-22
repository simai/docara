<?php

    namespace Simai\Docara\Console;

    use FilesystemIterator;
    use Exception;
    use Dotenv\Dotenv;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Process\Process;
    use Simai\Docara\File\Filesystem;
    use Simai\Docara\Scaffold\BasicScaffoldBuilder;
    use Simai\Docara\Scaffold\InstallerCommandException;
    use Simai\Docara\Scaffold\PresetScaffoldBuilder;

    class InitCommand extends Command
    {
        private $base;

        private bool $autoloadAdded = false;

        private bool $forceCoreConfigs = false;

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
                ->addOption('force-core-configs', null, InputOption::VALUE_NONE, 'Overwrite template configs even if modified locally.')
                ->addOption('force-core-files', null, InputOption::VALUE_NONE, 'Overwrite all files in source/_core from stubs (ignores user changes).');
        }

        protected function fire()
        {
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
                    ->comment("What would you like to do? (default: update in-place)")
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

            try {
                $this->confirmDocsDirExistsOrAsk();
                $scaffold->init($this->input->getArgument('preset'));
            } catch (Exception $e) {
                $this->console->error($e->getMessage())->line();

                return;
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

                $this->console->comment("Update mode: copying stubs and refreshing dependencies without deleting the project...");
                $this->clearSourceExceptCore();
            }

            try {
                $scaffold->setConsole($this->console)->build();
            
                $this->copyTemplateConfigsPreservingUserChanges();
                $this->ensureAppPsr4Autoload();
                $this->ensureDocsCreateComposerScript();
                $this->ensureValidPackageJson();
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
            }
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
            $core = $this->base . '/source/_core';
            if (! $this->files->isDirectory($core)) {
                return;
            }

            $rootFiles = [
                'webpack.mix.js',
                'bootstrap.php',
                'translate.config.php',
                'eslint.config.js',
                'package.json',
            ];

            $sourceFiles = [
                '404.blade.php',
                'favicon.ico',
            ];

            $stats = ['copied' => 0, 'updated' => 0, 'skipped' => 0, 'forced' => 0];

            foreach ($rootFiles as $file) {
                $this->copyIfUnchanged("{$core}/{$file}", "{$this->base}/{$file}", $stats);
            }

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

        private function ensureValidPackageJson(): void
        {
            $packagePath = $this->base . '/package.json';
            if (! file_exists($packagePath)) {
                return;
            }

            $contents = file_get_contents($packagePath);
            $data = json_decode($contents, true);
            if (! is_array($data)) {
                $this->console->comment('Could not parse package.json; skipped version/name normalization.');

                return;
            }

            $changed = false;

            if (empty($data['name']) || ! is_string($data['name'])) {
                $data['name'] = 'docara-site';
                $changed = true;
            }

            $version = $data['version'] ?? null;
            $semver = '/^\\d+\\.\\d+\\.\\d+(?:[-+][\\w.]+)?$/';
            if (empty($version) || ! is_string($version) || ! preg_match($semver, $version)) {
                $data['version'] = '1.0.0';
                $changed = true;
            }

            if ($changed) {
                $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
                file_put_contents($packagePath, $encoded);
                $this->console->comment('Normalized package.json (name/version). Run "npm install" or "yarn install" again.');
            }
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

            $useYarn = file_exists($path . '/yarn.lock');
            $cmd = $useYarn ? ['yarn', 'install'] : ['npm', 'install'];
            $tool = $useYarn ? 'yarn' : 'npm';

            $this->console->comment("Installing frontend dependencies in {$label} via {$tool}...");

            try {
                $process = new Process($cmd, $path, null, null, 300);
                $process->run();

                if (! $process->isSuccessful()) {
                    $this->console->error("{$tool} install failed: " . $process->getErrorOutput());
                } else {
                    $this->console->comment("{$tool} install complete for {$label}.");
                }
            } catch (\Throwable $e) {
                $this->console->error("Could not run {$tool} install in {$label}: {$e->getMessage()}");
            }
        }

        private function clearSourceExceptCore(): void
        {
            $sourcePath = $this->base . '/source';
            $docsDir = trim($_ENV['DOCS_DIR'] ?? getenv('DOCS_DIR') ?? 'docs', '/\\');
            if (! $this->files->isDirectory($sourcePath)) {
                return;
            }

            $items = array_diff(scandir($sourcePath) ?: [], ['.', '..']);
            foreach ($items as $item) {
                if ($item === '_core' || $item === $docsDir) {
                    continue;
                }
                $full = $sourcePath . DIRECTORY_SEPARATOR . $item;
                if ($this->files->isDirectory($full)) {
                    $this->files->deleteDirectory($full);
                } else {
                    $this->files->delete($full);
                }
            }
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
