<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\DesignAtlasService;
use Symfony\Component\Console\Input\InputOption;

final class AtlasCommand extends ApplicationCommand
{
    public function __construct(private readonly DesignAtlasService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('atlas')->setDescription('Show the deterministic Design Atlas projected from admitted registries.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        return $this->runOperation(fn () => $this->service->atlas($this->base));
    }
}
