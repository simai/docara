<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\ArtifactTestService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class TestArtifactCommand extends ApplicationCommand
{
    public function __construct(private readonly ArtifactTestService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('test')->setDescription('Validate and render one declared Smart/layout fixture through PreviewKernel.')
            ->addArgument('kind', InputArgument::REQUIRED, 'smart|layout')
            ->addArgument('id', InputArgument::REQUIRED)
            ->addOption('page', null, InputOption::VALUE_REQUIRED, 'Public route supplying production context.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        $page = $this->input->getOption('page');
        if (! is_string($page) || trim($page) === '') {
            return $this->runOperation(static fn () => throw new \InvalidArgumentException('SDK_TEST_PAGE_REQUIRED:A public --page route is required.'));
        }

        return $this->runOperation(fn () => $this->service->test(
            $this->base,
            (string) $this->input->getArgument('kind'),
            (string) $this->input->getArgument('id'),
            $page,
        ));
    }
}
