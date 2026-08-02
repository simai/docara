<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

final class InitCommand extends Command
{
    private string $base;

    public function __construct(
        private readonly Filesystem $files,
        private readonly PortableProjectInitializer $initializer,
    ) {
        $this->setBase();
        parent::__construct();
    }

    public function setBase(?string $cwd = null): self
    {
        $this->base = $cwd ?: (getcwd() ?: '.');

        return $this;
    }

    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Initialize a portable JSON and Markdown Docara project.')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Target project directory. Relative paths are resolved from the current directory.',
                '.',
            )
            ->addOption(
                'update',
                'u',
                InputOption::VALUE_NONE,
                'Deprecated safety guard. Use the explicit "docara update" workflow.',
            );
    }

    protected function fire(): int
    {
        $target = $this->targetDirectory();
        if ($this->files->exists($target) && ! $this->files->isDirectory($target)) {
            $this->console->error("Target path is not a directory: {$target}");

            return self::FAILURE;
        }

        $legacyMarkers = $this->markers($target, ['config.php', 'source']);
        if ($legacyMarkers !== []) {
            $this->console
                ->error('Refusing to migrate an existing legacy site implicitly.')
                ->comment('Use the documented migration workflow in a separate directory.');

            return self::FAILURE;
        }

        $update = (bool) $this->input->getOption('update');
        if ($update) {
            $this->console
                ->error('The implicit "init --update" workflow is disabled.')
                ->comment('Run "docara update --verify", then "--dry-run", then explicit "--apply".');

            return self::FAILURE;
        }

        $portableMarkers = $this->markers($target, ['docara.json', 'simai-framework.lock.json', 'content']);
        if ($portableMarkers !== []) {
            $this->console
                ->error('Detected an existing Docara project: ' . implode(', ', $portableMarkers))
                ->comment('Use the explicit "docara update" workflow for an initialized project.');

            return self::FAILURE;
        }
        if ($this->files->isDirectory($target) && (array_diff(scandir($target) ?: [], ['.', '..']) !== [])) {
            $this->console->error('Initialization requires an empty target directory. No files were changed.');

            return self::FAILURE;
        }

        try {
            $result = $this->initializer->initialize($target);
        } catch (Throwable $exception) {
            $this->console->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->console
            ->comment("Project directory: {$target}")
            ->comment("Starter files: copied={$result['copied']}, preserved={$result['preserved']}")
            ->comment("Package-owned state: files={$result['engine_files']}, sha256={$result['engine_sha256']}")
            ->info('Your Docara project was initialized successfully.');

        return self::SUCCESS;
    }

    private function targetDirectory(): string
    {
        $path = trim((string) $this->input->getArgument('path'));
        if ($path === '' || $path === '.') {
            return rtrim($this->base, '/\\') ?: DIRECTORY_SEPARATOR;
        }

        if ($this->isAbsolutePath($path)) {
            return rtrim($path, '/\\') ?: DIRECTORY_SEPARATOR;
        }

        return (rtrim($this->base, '/\\') ?: DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    /** @param list<string> $paths @return list<string> */
    private function markers(string $target, array $paths): array
    {
        $markers = [];
        foreach ($paths as $path) {
            $absolute = $target . '/' . $path;
            if ($this->files->exists($absolute)) {
                $markers[] = $this->files->isDirectory($absolute) ? $path . '/' : $path;
            }
        }

        return $markers;
    }
}
