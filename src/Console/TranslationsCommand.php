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
            ->addOption('key', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Translation key; repeat to accept a reviewed set.')
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Accept every unverified or stale key that starts with this prefix, such as guide/.')
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
                    // The page default belongs to accept; status reports every kind unless --kind is given.
                    $this->input->hasParameterOption('--kind', true) && is_string($this->input->getOption('kind')) ? $this->input->getOption('kind') : null,
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
                $keys = array_values(array_filter(
                    (array) $this->input->getOption('key'),
                    static fn (mixed $key): bool => is_string($key) && $key !== '',
                ));
                $prefix = is_string($this->input->getOption('prefix')) ? $this->input->getOption('prefix') : null;
                $status = is_string($this->input->getOption('status')) ? $this->input->getOption('status') : null;
                $excludeReason = is_string($this->input->getOption('exclude-reason')) ? $this->input->getOption('exclude-reason') : null;
                if (count($keys) <= 1 && $prefix === null && $status === null) {
                    $result = $this->service->planAccept(
                        $this->base,
                        (string) $this->input->getOption('locale'),
                        (string) ($keys[0] ?? ''),
                        (string) $this->input->getOption('review'),
                        $excludeReason,
                        (string) $this->input->getOption('kind'),
                    );
                } elseif ($excludeReason !== null) {
                    throw new \InvalidArgumentException('An exclusion names exactly one --key; it cannot apply to a set.');
                } else {
                    $result = $this->service->planAcceptSet(
                        $this->base,
                        (string) $this->input->getOption('locale'),
                        (string) $this->input->getOption('review'),
                        $keys,
                        $prefix,
                        $status,
                        (string) $this->input->getOption('kind'),
                    );
                }
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
