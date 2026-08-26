<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\PageInspectionService;
use Simai\Docara\Authoring\AuthoringContract;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

final class BuildCommand extends Command
{
    private string $base;

    public function __construct(private readonly PortableSiteBuilder $builder)
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
        $this->setName('build')
            ->setDescription('Build the portable Docara site atomically.')
            ->addArgument('environment', InputArgument::OPTIONAL, 'Build output suffix.', 'local')
            ->addOption(
                'page',
                null,
                InputOption::VALUE_REQUIRED,
                'Rebuild one existing page by its public URL.',
            );
    }

    protected function fire(): int
    {
        $environment = (string) $this->input->getArgument('environment');
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/D', $environment) !== 1) {
            $this->console->error('The build environment may contain only letters, digits, underscores and hyphens.');

            return self::FAILURE;
        }

        $destination = $this->base . '/build_' . $environment;
        $startedAt = microtime(true);
        try {
            $page = $this->input->getOption('page');
            $pages = $this->builder->build(
                $this->base,
                $destination,
                is_string($page) && trim($page) !== '' ? $page : null,
            );
        } catch (Throwable $exception) {
            $this->console->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->console->info(sprintf(
            'Built %d page(s) into %s in %.2fs.',
            $pages->count(),
            $destination,
            microtime(true) - $startedAt,
        ));
        try {
            $contract = AuthoringContract::load($this->base);
            if ($contract->present) {
                $profileIssues = count(array_filter(
                    (new PageInspectionService)->validateAll($this->base),
                    static fn (array $check): bool => ($check['status'] ?? 'pass') !== 'pass',
                ));
                if ($profileIssues > 0) {
                    $this->console->comment("Page authoring reported $profileIssues item(s) for structural or editorial review; the build remains valid in report mode.");
                }
            }
        } catch (Throwable $exception) {
            $this->console->comment('Page authoring contract warning: ' . $exception->getMessage() . ' The build remains valid in report mode.');
        }
        $translationReport = $destination . '/.docara/translation-status.json';
        if (is_file($translationReport) && ! is_link($translationReport)) {
            $report = json_decode((string) file_get_contents($translationReport), true);
            if (is_array($report)) {
                $issues = array_sum(array_map(
                    static fn (string $status): int => (int) ($report['summary'][$status] ?? 0),
                    ['stale', 'missing', 'unverified', 'orphan', 'duplicate_key', 'structure_mismatch'],
                ));
                if ($issues > 0) {
                    $this->console->comment("Translation tracking reported $issues item(s) that need attention; the build remains valid in report mode.");
                }
            }
        }

        return self::SUCCESS;
    }
}
