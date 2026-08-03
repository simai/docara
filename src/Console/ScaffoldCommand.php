<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\ScaffoldService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class ScaffoldCommand extends ApplicationCommand
{
    public function __construct(private readonly ScaffoldService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('scaffold')->setDescription('Create a hash-bound Smart or design scaffold plan, then explicitly apply it.')
            ->addArgument('kind', InputArgument::OPTIONAL, 'smart|design')
            ->addArgument('id', InputArgument::OPTIONAL, 'Project-owned namespaced artifact id')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Create and return a deterministic review plan.')
            ->addOption('apply', null, InputOption::VALUE_REQUIRED, 'Apply the exact plan SHA-256 after rechecking every input.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        $apply = $this->input->getOption('apply');
        if (is_string($apply) && $apply !== '') {
            return $this->runOperation(fn () => $this->service->apply($this->base, $apply));
        }
        if (! (bool) $this->input->getOption('dry-run')) {
            return $this->runOperation(static fn () => throw new \InvalidArgumentException('SCAFFOLD_DRY_RUN_REQUIRED:Review a dry-run before apply.'));
        }

        return $this->runOperation(fn () => $this->service->plan(
            $this->base,
            (string) $this->input->getArgument('kind'),
            (string) $this->input->getArgument('id'),
        ));
    }
}
