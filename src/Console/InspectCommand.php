<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\DiscoveryService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class InspectCommand extends ApplicationCommand
{
    public function __construct(private readonly DiscoveryService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('inspect')->setDescription('Inspect one registered SDK subject with ownership and provenance.')
            ->addArgument('kind', InputArgument::REQUIRED)
            ->addArgument('id', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        return $this->runOperation(fn () => $this->service->inspect(
            $this->base,
            (string) $this->input->getArgument('kind'),
            (string) $this->input->getArgument('id'),
        ));
    }
}
