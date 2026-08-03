<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\ValidationService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class ValidateCommand extends ApplicationCommand
{
    public function __construct(private readonly ValidationService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('validate')->setDescription('Validate effective project, Smart or design artifacts through production registries.')
            ->addArgument('kind', InputArgument::REQUIRED, 'project|smart|layout|view|section|block')
            ->addArgument('id', InputArgument::OPTIONAL)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        $id = $this->input->getArgument('id');

        return $this->runOperation(fn () => $this->service->validate($this->base, (string) $this->input->getArgument('kind'), is_string($id) ? $id : null));
    }
}
