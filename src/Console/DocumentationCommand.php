<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Documentation\DocumentationStatusService;
use Simai\Docara\Portable\CanonicalJson;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

final class DocumentationCommand extends Command
{
    private string $base;

    public function __construct(private readonly DocumentationStatusService $service)
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
        $this->setName('documentation')->setDescription('Report or accept source-backed documentation freshness without editing content during build.')
            ->addArgument('action', InputArgument::REQUIRED, 'status|accept')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Configured documentation source id.')
            ->addOption('kind', null, InputOption::VALUE_REQUIRED, 'Filter entity kind.')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Filter report status.')
            ->addOption('key', null, InputOption::VALUE_REQUIRED, 'Stable source entity key.')
            ->addOption('route', null, InputOption::VALUE_REQUIRED, 'Source-locale public page route.')
            ->addOption('example', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Required case mapping case=example/id.')
            ->addOption('review', null, InputOption::VALUE_REQUIRED, 'ai_verified|human_reviewed', 'ai_verified')
            ->addOption('exclude-reason', null, InputOption::VALUE_REQUIRED, 'Mark the source entity intentionally excluded.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Create a hash-bound acceptance plan.')
            ->addOption('apply', null, InputOption::VALUE_REQUIRED, 'Apply an exact acceptance plan SHA-256.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit stable JSON.');
    }

    protected function fire(): int
    {
        try {
            $action = (string) $this->input->getArgument('action');
            if ($action === 'status') {
                $report = $this->service->report(
                    $this->base,
                    $this->option('source'),
                    $this->option('kind'),
                    $this->option('status'),
                );
                if ((bool) $this->input->getOption('json')) {
                    $this->output->write(CanonicalJson::encodePretty($report));
                } else {
                    $this->renderReport($report);
                }

                return self::SUCCESS;
            }
            if ($action !== 'accept') {
                throw new \InvalidArgumentException('Documentation action must be status or accept.');
            }
            $apply = $this->option('apply');
            if ($apply !== null) {
                $result = $this->service->apply($this->base, $apply);
            } elseif ((bool) $this->input->getOption('dry-run')) {
                $result = $this->service->planAccept(
                    $this->base,
                    (string) $this->input->getOption('source'),
                    (string) $this->input->getOption('key'),
                    (string) $this->input->getOption('route'),
                    (string) $this->input->getOption('review'),
                    $this->examples((array) $this->input->getOption('example')),
                    $this->option('exclude-reason'),
                );
            } else {
                throw new \InvalidArgumentException('Documentation acceptance requires --dry-run or --apply=<plan-sha256>.');
            }
            $this->output->write(CanonicalJson::encodePretty($result));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if ((bool) $this->input->getOption('json')) {
                $this->output->write(CanonicalJson::encodePretty(['schema' => 'docara.documentation_error.v1', 'status' => 'error', 'message' => $exception->getMessage()]));
            } else {
                $this->console->error($exception->getMessage());
            }

            return self::FAILURE;
        }
    }

    /** @param list<mixed> $values @return array<string,string> */
    private function examples(array $values): array
    {
        $examples = [];
        foreach ($values as $value) {
            if (! is_string($value) || preg_match('/^([a-z][a-z0-9_.-]*)=([a-z0-9][a-z0-9._-]*(?:\/[a-z0-9][a-z0-9._-]*)*)$/D', $value, $match) !== 1) {
                throw new \InvalidArgumentException('Each --example must use case=lowercase/example-id.');
            }
            if (isset($examples[$match[1]])) {
                throw new \InvalidArgumentException("Example case [{$match[1]}] is duplicated.");
            }
            $examples[$match[1]] = $match[2];
        }
        ksort($examples, SORT_STRING);

        return $examples;
    }

    private function option(string $name): ?string
    {
        $value = $this->input->getOption($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<string,mixed> $report */
    private function renderReport(array $report): void
    {
        if (($report['enabled'] ?? false) !== true) {
            $this->console->comment('Documentation tracking is disabled.');

            return;
        }
        $this->console->info('Documentation status for source locale ' . $report['source_locale'] . '.');
        foreach ($report['summary'] as $status => $count) {
            if ($status !== 'total') {
                $this->console->write(sprintf('%-20s %d', $status, $count));
            }
        }
        foreach ($report['items'] as $item) {
            if ($item['status'] !== 'current') {
                $this->console->write(sprintf('- %-18s %s:%s', $item['status'], $item['source'], $item['key']));
            }
        }
    }
}
