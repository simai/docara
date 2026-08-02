<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\PortableSite\PortableProjectUpdater;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

final class UpdateCommand extends Command
{
    private string $base;

    public function __construct(private readonly PortableProjectUpdater $updater)
    {
        $this->setBase();
        parent::__construct();
    }

    public function setBase(?string $cwd = null): self
    {
        $this->base = $cwd ?: (getcwd() ?: '.');

        return $this;
    }

    protected function configure(): void
    {
        $this->setName('update')
            ->setDescription('Verify, preview, apply or roll back package-owned Docara project state.')
            ->addArgument('path', InputArgument::OPTIONAL, 'Target project directory.', '.')
            ->addOption('verify', null, InputOption::VALUE_NONE, 'Verify ownership and report whether an update is available.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Write a hash-bound update plan without applying it.')
            ->addOption('diff', null, InputOption::VALUE_NONE, 'Alias of --dry-run.')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply the previously generated unchanged plan atomically.')
            ->addOption('rollback', null, InputOption::VALUE_REQUIRED, 'Roll back an applied update by id or "latest".')
            ->addOption('adopt', null, InputOption::VALUE_NONE, 'Explicitly adopt an existing pre-manifest project during dry-run.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit machine-readable JSON.');
    }

    protected function fire(): int
    {
        $actions = array_filter([
            'verify' => (bool) $this->input->getOption('verify'),
            'dry-run' => (bool) $this->input->getOption('dry-run') || (bool) $this->input->getOption('diff'),
            'apply' => (bool) $this->input->getOption('apply'),
            'rollback' => is_string($this->input->getOption('rollback')),
        ]);
        if ($actions === []) {
            $actions = ['verify' => true];
        }
        if (count($actions) !== 1) {
            $this->console->error('Choose exactly one update action: --verify, --dry-run/--diff, --apply or --rollback.');

            return self::INVALID;
        }
        if ((bool) $this->input->getOption('adopt') && ! isset($actions['dry-run'])) {
            $this->console->error('--adopt is allowed only with --dry-run or --diff.');

            return self::INVALID;
        }

        try {
            $root = $this->targetDirectory();
            $action = array_key_first($actions);
            $result = match ($action) {
                'verify' => $this->updater->verify($root),
                'dry-run' => $this->updater->dryRun($root, (bool) $this->input->getOption('adopt')),
                'apply' => $this->updater->apply($root),
                'rollback' => $this->updater->rollback($root, (string) $this->input->getOption('rollback')),
            };
        } catch (Throwable $exception) {
            if ((bool) $this->input->getOption('json')) {
                $this->output->writeln(CanonicalJson::encode([
                    'schema' => 'docara.cli_error.v1',
                    'status' => 'error',
                    'exit_code' => self::FAILURE,
                    'message' => $exception->getMessage(),
                ]));
            } else {
                $this->console->error($exception->getMessage());
            }

            return self::FAILURE;
        }

        if ((bool) $this->input->getOption('json')) {
            $this->output->writeln(CanonicalJson::encode($result));
        } else {
            $this->renderHuman($result);
        }

        return self::SUCCESS;
    }

    protected function printBanner(): void
    {
        if ($this->input->hasOption('json') && (bool) $this->input->getOption('json')) {
            return;
        }

        parent::printBanner();
    }

    /** @param array<string, mixed> $result */
    private function renderHuman(array $result): void
    {
        $status = (string) ($result['status'] ?? 'ok');
        $this->console->info('Update status: ' . $status);
        if (isset($result['operations']) && is_array($result['operations'])) {
            $this->console->comment('Operations: ' . count($result['operations']));
            foreach ($result['operations'] as $operation) {
                if (is_array($operation)) {
                    $this->console->comment(sprintf('  %s %s', $operation['action'] ?? '?', $operation['path'] ?? '?'));
                }
            }
        }
        if (isset($result['rollback_id'])) {
            $this->console->comment('Rollback id: ' . $result['rollback_id']);
        }
        if ($status === 'update_available') {
            $this->console->comment('Run "docara update --dry-run" to inspect and bind the update plan.');
        } elseif (($result['schema'] ?? null) === 'docara.update_plan.v1') {
            $this->console->comment('Review the plan, then run "docara update --apply" without changing inputs.');
        }
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
