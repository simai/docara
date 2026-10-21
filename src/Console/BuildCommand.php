<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\PageInspectionService;
use Simai\Docara\Authoring\AuthoringContract;
use Simai\Docara\PortableSite\BuildReceipts;
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
                // Every page with a profile carries the advisory editorial
                // checklist (PAGE_EDITORIAL_REVIEW_REQUIRED); `docara validate
                // page` shows it. The build reports only what needs a fix:
                // missing structure, links and assets.
                $profileIssues = count(array_filter(
                    (new PageInspectionService)->validateAll($this->base),
                    static fn (array $check): bool => ($check['status'] ?? 'pass') !== 'pass'
                        && ($check['severity'] ?? null) !== 'advisory',
                ));
                if ($profileIssues > 0) {
                    $this->console->comment("Page authoring reported $profileIssues item(s) for structural review or with a missing link or asset; the build remains valid in report mode.");
                }
            }
        } catch (Throwable $exception) {
            $this->console->comment('Page authoring contract warning: ' . $exception->getMessage() . ' The build remains valid in report mode.');
        }
        $translationReport = BuildReceipts::existing($destination) . '/translation-status.json';
        if (is_file($translationReport) && ! is_link($translationReport)) {
            $report = json_decode((string) file_get_contents($translationReport), true);
            if (is_array($report)) {
                $issues = array_sum(array_map(
                    static fn (string $status): int => (int) ($report['summary'][$status] ?? 0),
                    ['stale', 'missing', 'unverified', 'orphan', 'duplicate_key', 'structure_mismatch'],
                ));
                if ($issues > 0) {
                    $this->console->comment(($report['mode'] ?? 'report') === 'publish-current'
                        ? "Translation tracking reported $issues item(s) that need attention; publish-current mode published only current translations."
                        : "Translation tracking reported $issues item(s) that need attention; the build remains valid in report mode.");
                }
            }
        }
        $publicationReceipt = BuildReceipts::existing($destination) . '/translation-publication.json';
        if (is_file($publicationReceipt) && ! is_link($publicationReceipt)) {
            $receipt = json_decode((string) file_get_contents($publicationReceipt), true);
            if (is_array($receipt) && is_array($receipt['pages'] ?? null) && is_array($receipt['lang'] ?? null)) {
                $heldBack = count($receipt['pages']) + count($receipt['lang']);
                if ($heldBack === 0) {
                    $this->console->info('Translations: every target page and string is current; nothing was held back.');
                } else {
                    $this->console->comment(sprintf(
                        'Translations held back (mode publish-current): %d page(s) show the source text, %d page(s) withheld, %d string(s) show the source text.',
                        (int) ($receipt['summary']['source_fallback'] ?? 0),
                        (int) ($receipt['summary']['withheld'] ?? 0),
                        (int) ($receipt['summary']['source_strings'] ?? 0),
                    ));
                    foreach ($receipt['pages'] as $page) {
                        $this->console->write(sprintf(
                            '- %-16s %-12s %s [%s] %s',
                            (string) ($page['action'] ?? ''),
                            (string) ($page['status'] ?? ''),
                            (string) ($page['key'] ?? ''),
                            (string) ($page['locale'] ?? ''),
                            (string) ($page['target_path'] ?? ''),
                        ));
                    }
                    foreach ($receipt['lang'] as $string) {
                        $this->console->write(sprintf(
                            '- %-16s lang:%s [%s]',
                            (string) ($string['action'] ?? ''),
                            (string) ($string['key'] ?? ''),
                            (string) ($string['locale'] ?? ''),
                        ));
                    }
                }
            }
        }
        $documentationReport = BuildReceipts::existing($destination) . '/documentation-status.json';
        if (is_file($documentationReport) && ! is_link($documentationReport)) {
            $report = json_decode((string) file_get_contents($documentationReport), true);
            if (is_array($report)) {
                $issues = array_sum(array_map(
                    static fn (string $status): int => (int) ($report['summary'][$status] ?? 0),
                    ['new', 'changed', 'missing', 'missing_example', 'unverified', 'orphan'],
                ));
                if ($issues > 0) {
                    $this->console->comment("Documentation tracking reported $issues item(s) that need attention; the build remains valid in report mode.");
                }
            }
        }

        return self::SUCCESS;
    }
}
