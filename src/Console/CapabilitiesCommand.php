<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\CapabilitiesService;
use Symfony\Component\Console\Input\InputOption;

final class CapabilitiesCommand extends ApplicationCommand
{
    public function __construct(private readonly CapabilitiesService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('capabilities')
            ->setDescription('Describe the exact installed Docara AI and application contract.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        return $this->runOperation(fn () => $this->service->capabilities($this->base, $this->getApplication()));
    }
}
