<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

final class BuildCommand extends Command
{
    private string $base;

    public function __construct(private readonly PortableSiteBuilder $builder)
    {
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
        $this->setName('build')
            ->setDescription('Build the portable Docara site atomically.')
            ->addArgument('environment', InputArgument::OPTIONAL, 'Build output suffix.', 'local');
    }

    protected function fire(): int
    {
        $environment = (string) $this->input->getArgument('environment');
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/D', $environment) !== 1) {
            $this->console->error('The build environment may contain only letters, digits, underscores and hyphens.');

            return self::FAILURE;
        }

        $destination = $this->base . '/build_' . $environment;
        $startedAt = microtime(true);
        try {
            $pages = $this->builder->build($this->base, $destination);
        } catch (Throwable $exception) {
            $this->console->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->console->info(sprintf(
            'Built %d page(s) into %s in %.2fs.',
            $pages->count(),
            $destination,
            microtime(true) - $startedAt,
        ));

        return self::SUCCESS;
    }
}
