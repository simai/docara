<?php

namespace Simai\Docara\Console;

use Exception;
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
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update existing site in-place (no delete/archive).');
    }

    protected function fire()
    {
        $envPath = $this->base . '/.env';
        if (! file_exists($envPath)) {
            $this->console->error('Missing .env in project root. Please create it (DOCS_DIR, AZURE_*, etc.) and rerun init.');

            return static::FAILURE;
        }

        $updateMode = $this->input->getOption('update');
        $scaffold = $this->getScaffold()->setBase($this->base);

        try {
            $scaffold->init($this->input->getArgument('preset'));
        } catch (Exception $e) {
            $this->console->error($e->getMessage())->line();

            return;
        }

        if ($this->initHasAlreadyBeenRun() && ! $updateMode) {
            $response = $this->askUserWhatToDoWithExistingSite();
            $this->console->line();

            switch ($response) {
                case 'a':
                    $this->console->comment('Archiving your existing site...');
                    $scaffold->archiveExistingSite();
                    break;

                case 'd':
                    if ($this->console->confirm(
                        '<fg=red>Are you sure you want to delete your existing site?</>',
                    )) {
                        $this->console->comment('Deleting your existing site...');
                        $scaffold->deleteExistingSite();
                        break;
                    }

                    // no break
                default:
                    return;
            }
        }

        if ($updateMode) {
            $this->console->comment("Update mode: copying stubs and refreshing dependencies without deleting the project...");
            $this->clearSourceExceptCore();
        }

        try {
            $scaffold->setConsole($this->console)->build();
            $this->ensureCoreSubmodule();
            $this->runCoreCopyScript();
            $this->ensureAppPsr4Autoload();
            $this->installFrontendDependencies($this->base, 'project root');

            $suffix = $scaffold instanceof $this->presetScaffold && $scaffold->package ?
                " using the '" . $scaffold->package->shortName . "' preset." :
                ' successfully.';

            $this->console
                ->line()
                ->info('Your Docara site was initialized' . $suffix)
                ->line();
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

    private function ensureCoreSubmodule(): void
    {
        $corePath = $this->base . '/source/_core';
        $coreRelative = 'source/_core';
        $coreRepo = 'https://github.com/simai/ui-doc-core.git';

        if (! is_dir($this->base . '/.git')) {
            $this->console->comment('Git repo not detected, skipping submodule setup for source/_core.');

            return;
        }

        if (file_exists($corePath . '/.git') || is_file($corePath . '/.git')) {
            return;
        }

        if ($this->files->exists($corePath)) {
            $this->files->deleteDirectory($corePath);
        }

        // Try submodule first.
        $cmd = 'cd ' . escapeshellarg($this->base)
            . ' && git submodule add ' . escapeshellarg($coreRepo) . ' ' . escapeshellarg($coreRelative)
            . ' && git submodule update --init --recursive ' . escapeshellarg($coreRelative);

        exec($cmd, $output, $status);

        if ($status === 0) {
            $this->console->comment('Submodule source/_core added (simai/ui-doc-core).');

            return;
        }

        // Fallback: clone without submodule metadata (for non-git consumers).
        $this->console->comment('Submodule setup failed, trying direct clone of source/_core...');
        $cloneCmd = 'git clone --depth=1 ' . escapeshellarg($coreRepo) . ' ' . escapeshellarg($corePath);
        exec($cloneCmd, $cOut, $cStatus);

        if ($cStatus === 0) {
            // Remove git metadata from the clone to keep it lightweight.
            if ($this->files->isDirectory($corePath . '/.git')) {
                $this->files->deleteDirectory($corePath . '/.git');
            }
            $this->console->comment('Cloned source/_core from simai/ui-doc-core.');

            return;
        }

        $this->console->comment('Could not fetch source/_core automatically. Please run:');
        $this->console->comment("git submodule add {$coreRepo} {$coreRelative}");
        $this->console->comment("git submodule update --init --recursive {$coreRelative}");
        $this->console->comment("or clone manually: git clone {$coreRepo} {$coreRelative}");
    }

    private function runCoreCopyScript(): void
    {
        $script = $this->base . '/source/_core/copy-template-configs.js';
        if (! file_exists($script)) {
            return;
        }

        $this->console->comment('Copying template configs from source/_core...');

        try {
            $process = new Process(['node', $script], $this->base, null, null, 60);
            $process->run();

            if (! $process->isSuccessful()) {
                $this->console->error('copy-template-configs failed: ' . $process->getErrorOutput());
            } else {
                $this->console->comment('Template configs copied.');
            }
        } catch (\Throwable $e) {
            $this->console->error("Could not run copy-template-configs.js: {$e->getMessage()}");
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

        $psr4['App\\'] = 'source/';
        $autoload['psr-4'] = $psr4;
        $data['autoload'] = $autoload;

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($composerPath, $encoded);
        $this->console->comment('Added App\\ => source/ to composer.json autoload. Run "composer dump-autoload" to apply.');
    }

    private function installFrontendDependencies(string $path, string $label): void
    {
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
}
