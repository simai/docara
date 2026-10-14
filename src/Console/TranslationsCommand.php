<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\I18n\TranslationStatusService;
use Simai\Docara\Portable\CanonicalJson;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

final class TranslationsCommand extends Command
{
    private string $base;

    public function __construct(private readonly TranslationStatusService $service)
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
        $this->setName('translations')->setDescription('Report or accept multilingual content freshness without translating during build.')
            ->addArgument('action', InputArgument::REQUIRED, 'status|accept')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Target locale.')
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Filter report by status.')
            ->addOption('kind', null, InputOption::VALUE_REQUIRED, 'page|lang', 'page')
            ->addOption('key', null, InputOption::VALUE_REQUIRED, 'Translation key.')
            ->addOption('review', null, InputOption::VALUE_REQUIRED, 'ai_verified|human_reviewed', 'ai_verified')
            ->addOption('exclude-reason', null, InputOption::VALUE_REQUIRED, 'Mark this locale/key intentionally excluded.')
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
                    is_string($this->input->getOption('locale')) ? $this->input->getOption('locale') : null,
                    is_string($this->input->getOption('status')) ? $this->input->getOption('status') : null,
                );
                if ((bool) $this->input->getOption('json')) {
                    $this->output->write(CanonicalJson::encodePretty($report));
                } else {
                    $this->renderReport($report);
                }

                return self::SUCCESS;
            }
            if ($action !== 'accept') {
                throw new \InvalidArgumentException('Translation action must be status or accept.');
            }
            $apply = $this->input->getOption('apply');
            if (is_string($apply) && $apply !== '') {
                $result = $this->service->apply($this->base, $apply);
            } elseif ((bool) $this->input->getOption('dry-run')) {
                $result = $this->service->planAccept(
                    $this->base,
                    (string) $this->input->getOption('locale'),
                    (string) $this->input->getOption('key'),
                    (string) $this->input->getOption('review'),
                    is_string($this->input->getOption('exclude-reason')) ? $this->input->getOption('exclude-reason') : null,
                    (string) $this->input->getOption('kind'),
                );
            } else {
                throw new \InvalidArgumentException('Translation acceptance requires --dry-run or --apply=<plan-sha256>.');
            }
            $this->output->write(CanonicalJson::encodePretty($result));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if ((bool) $this->input->getOption('json')) {
                $this->output->write(CanonicalJson::encodePretty(['schema' => 'docara.translation_error.v1', 'status' => 'error', 'message' => $exception->getMessage()]));
            } else {
                $this->console->error($exception->getMessage());
            }

            return self::FAILURE;
        }
    }

    /** @param array<string,mixed> $report */
    private function renderReport(array $report): void
    {
        if (($report['enabled'] ?? false) !== true) {
            $this->console->comment('Translation tracking is disabled.');

            return;
        }
        $this->console->info('Translation status from source locale ' . $report['source_locale'] . '.');
        foreach ($report['summary'] as $status => $count) {
            if ($status !== 'total') {
                $this->console->write(sprintf('%-20s %d', $status, $count));
            }
        }
        foreach ($report['items'] as $item) {
            if ($item['status'] !== 'current') {
                $this->console->write(sprintf('- %-18s %s:%s [%s]', $item['status'], $item['kind'], $item['key'], $item['locale']));
            }
        }
    }
}
