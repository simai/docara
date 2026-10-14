<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\DiscoveryService;
use Symfony\Component\Console\Input\InputOption;

final class DoctorCommand extends ApplicationCommand
{
    public function __construct(private readonly DiscoveryService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('doctor')->setDescription('Check the effective project SDK/runtime contracts.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        return $this->runOperation(fn () => $this->service->doctor($this->base));
    }
}
