<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\QaService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class QaCommand extends ApplicationCommand
{
    public function __construct(private readonly QaService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('qa')->setDescription('Plan or verify optional browser/a11y/visual QA against isolated production-path preview.')
            ->addArgument('kind', InputArgument::OPTIONAL, 'smart|region|layout')
            ->addArgument('id', InputArgument::OPTIONAL)
            ->addOption('page', null, InputOption::VALUE_REQUIRED)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Publish preview and create a deterministic QA scenario plan.')
            ->addOption('finalize-reference', null, InputOption::VALUE_REQUIRED, 'Validate a recorded reference draft and bind its full manifest to a new immutable plan.')
            ->addOption('verify', null, InputOption::VALUE_REQUIRED, 'Verify an exact externally produced QA report by plan SHA-256.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        $verify = $this->input->getOption('verify');
        if (is_string($verify) && $verify !== '') {
            return $this->runOperation(fn () => $this->service->verify($this->base, $verify));
        }
        $finalize = $this->input->getOption('finalize-reference');
        if (is_string($finalize) && $finalize !== '') {
            return $this->runOperation(fn () => $this->service->finalizeReference($this->base, $finalize));
        }
        $page = $this->input->getOption('page');
        if (! (bool) $this->input->getOption('dry-run') || ! is_string($page) || trim($page) === '') {
            return $this->runOperation(static fn () => throw new \InvalidArgumentException('QA_DRY_RUN_REQUIRED:Use --dry-run with an exact --page before browser execution.'));
        }

        return $this->runOperation(fn () => $this->service->plan(
            $this->base,
            (string) $this->input->getArgument('kind'),
            (string) $this->input->getArgument('id'),
            $page,
        ));
    }
}
