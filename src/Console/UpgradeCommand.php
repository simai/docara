<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\ProjectUpgradeService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class UpgradeCommand extends ApplicationCommand
{
    public function __construct(private readonly ProjectUpgradeService $service)
    {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('upgrade')
            ->setDescription('Resolve, verify, apply or roll back a compatible project-local Docara upgrade.')
            ->addArgument('path', InputArgument::OPTIONAL, 'Target project directory.', '.')
            ->addOption('check', null, InputOption::VALUE_NONE, 'Resolve and verify whether a compatible update is available.')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Select one exact stable target version.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Prepare and retain an immutable verified upgrade plan.')
            ->addOption('apply', null, InputOption::VALUE_REQUIRED, 'Apply an exact unchanged upgrade plan SHA-256.')
            ->addOption('rollback', null, InputOption::VALUE_REQUIRED, 'Restore an applied upgrade by id or "latest" without network.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        $check = (bool) $this->input->getOption('check');
        $dryRun = (bool) $this->input->getOption('dry-run');
        $apply = $this->input->getOption('apply');
        $rollback = $this->input->getOption('rollback');
        $actions = (int) $check + (int) $dryRun + (int) is_string($apply) + (int) is_string($rollback);
        if ($actions > 1) {
            return $this->runOperation(static fn () => throw new \InvalidArgumentException('UPGRADE_ACTION_CONFLICT:Choose only one of --check, --dry-run, --apply or --rollback.'), 'upgrade');
        }
        $target = $this->input->getOption('to');
        if (($apply !== false || $rollback !== false) && is_string($target)) {
            return $this->runOperation(static fn () => throw new \InvalidArgumentException('UPGRADE_ACTION_CONFLICT:--to is allowed only for check, dry-run or automatic upgrade.'), 'upgrade');
        }
        $root = $this->targetDirectory();

        return match (true) {
            $check => $this->runOperation(fn () => $this->service->check($root, is_string($target) ? $target : null), 'upgrade.check'),
            $dryRun => $this->runOperation(fn () => $this->service->plan($root, is_string($target) ? $target : null), 'upgrade.plan'),
            is_string($apply) => $this->runOperation(fn () => $this->service->apply($root, $apply), 'upgrade.apply', ['plan_id' => $apply]),
            is_string($rollback) => $this->runOperation(fn () => $this->service->rollback($root, $rollback), 'upgrade.rollback', ['id' => $rollback]),
            default => $this->runOperation(fn () => $this->service->upgrade($root, is_string($target) ? $target : null), 'upgrade'),
        };
    }

    private function targetDirectory(): string
    {
        $path = trim((string) $this->input->getArgument('path'));
        if ($path === '' || $path === '.') {
            return $this->base;
        }
        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return rtrim($this->base, '/\\') . '/' . $path;
    }
}
