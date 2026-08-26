<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\DiscoveryService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class ListCommand extends ApplicationCommand
{
    public function __construct(private readonly DiscoveryService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('list')->setDescription('List registered SDK subjects, including physical Markdown pages.')
            ->addArgument('kind', InputArgument::REQUIRED, 'page|smart|binding|layout|view|section|block|provider|fixture|state|schema')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        return $this->runOperation(fn () => $this->service->list($this->base, (string) $this->input->getArgument('kind')));
    }
}
