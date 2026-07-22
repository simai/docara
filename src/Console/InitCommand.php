<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\File\Filesystem;
use Simai\Docara\PortableSite\PortableProjectInitializer;
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
            ->addOption(
                'update',
                'u',
                InputOption::VALUE_NONE,
                'Add missing starter files without overwriting project-owned files.',
            );
    }

    protected function fire(): int
    {
        $legacyMarkers = $this->markers(['config.php', 'source']);
        if ($legacyMarkers !== []) {
            $this->console
                ->error('Refusing to migrate an existing legacy site implicitly.')
                ->comment('Use the documented migration workflow in a separate directory.');

            return self::FAILURE;
        }

        $portableMarkers = $this->markers(['docara.json', 'simai-framework.lock.json', 'content']);
        $update = (bool) $this->input->getOption('update');
        if ($portableMarkers !== [] && ! $update) {
            $this->console
                ->error('Detected an existing Docara project: ' . implode(', ', $portableMarkers))
                ->comment('Run "docara init --update" to add only missing starter files.');

            return self::FAILURE;
        }

        try {
            $result = $this->initializer->initialize($this->base);
        } catch (Throwable $exception) {
            $this->console->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->console
            ->comment("Starter files: copied={$result['copied']}, preserved={$result['preserved']}")
            ->info($update
                ? 'Your Docara project was updated without overwriting existing files.'
                : 'Your Docara project was initialized successfully.');

        return self::SUCCESS;
    }

    /** @param list<string> $paths @return list<string> */
    private function markers(array $paths): array
    {
        $markers = [];
        foreach ($paths as $path) {
            $absolute = $this->base . '/' . $path;
            if ($this->files->exists($absolute)) {
                $markers[] = $this->files->isDirectory($absolute) ? $path . '/' : $path;
            }
        }

        return $markers;
    }
}
