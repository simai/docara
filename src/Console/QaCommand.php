<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\OperationFailureMapper;
use Simai\Docara\Application\OperationResult;
use Simai\Docara\Application\QaService;
use Simai\Docara\Quality\QualityReportService;
use Simai\Docara\Quality\ReviewPacketService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

/**
 * Quality assurance. `qa report` scores the documentation of a built site
 * and `qa review-packet` prepares the semantic review for a person or an AI
 * assistant; both work offline and never change a page. The kinds smart,
 * region and layout plan and verify optional browser QA.
 */
final class QaCommand extends ApplicationCommand
{
    public function __construct(
        private readonly QaService $service,
        private readonly QualityReportService $quality = new QualityReportService,
        private readonly ReviewPacketService $review = new ReviewPacketService,
    ) {
        $this->setBase();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('qa')->setDescription('Score documentation quality (report), prepare a semantic review packet (review-packet), or plan and verify browser QA.')
            ->addArgument('kind', InputArgument::OPTIONAL, 'report|review-packet|smart|region|layout')
            ->addArgument('id', InputArgument::OPTIONAL, 'Page route for review-packet; artifact id for browser QA.')
            ->addOption('page', null, InputOption::VALUE_REQUIRED)
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Build environment the report reads: build_<env>/ and build_<env>.docara/.', 'local')
            ->addOption('min-score', null, InputOption::VALUE_REQUIRED, 'Fail when the site score is below this number (0-100); overrides quality.min_score.')
            ->addOption('top', null, InputOption::VALUE_REQUIRED, 'How many "fix first" items to list.', '10')
            ->addOption('html', null, InputOption::VALUE_NONE, 'Also write a standalone HTML report next to the build (never published).')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Publish preview and create a deterministic QA scenario plan.')
            ->addOption('finalize-reference', null, InputOption::VALUE_REQUIRED, 'Validate a recorded reference draft and bind its full manifest to a new immutable plan.')
            ->addOption('verify', null, InputOption::VALUE_REQUIRED, 'Verify an exact externally produced QA report by plan SHA-256.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the stable operation result as JSON.');
    }

    protected function fire(): int
    {
        $kind = (string) $this->input->getArgument('kind');
        if ($kind === 'report') {
            return $this->report();
        }
        if ($kind === 'review-packet') {
            $page = $this->input->getArgument('id') ?? $this->input->getOption('page');

            return $this->runOperation(fn () => $this->review->run(
                $this->base,
                is_string($page) && trim($page) !== '' ? $page : null,
                (string) $this->input->getOption('env'),
            ), 'qa.review_packet');
        }
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
            return $this->runOperation(static fn () => throw new \InvalidArgumentException('QA_DRY_RUN_REQUIRED:Use docara qa report, or --dry-run with an exact --page before browser execution.'));
        }

        return $this->runOperation(fn () => $this->service->plan(
            $this->base,
            $kind,
            (string) $this->input->getArgument('id'),
            $page,
        ));
    }

    private function report(): int
    {
        try {
            $minimum = $this->input->getOption('min-score');
            if ($minimum !== null && preg_match('/\A\d{1,3}\z/D', (string) $minimum) !== 1) {
                throw new \InvalidArgumentException('QUALITY_MIN_SCORE_INVALID:The minimum score is a whole number from 0 to 100.');
            }
            $top = (string) $this->input->getOption('top');
            if (preg_match('/\A\d{1,4}\z/D', $top) !== 1) {
                throw new \InvalidArgumentException('QUALITY_TOP_INVALID:--top is a whole number.');
            }
            $result = $this->quality->run(
                $this->base,
                (string) $this->input->getOption('env'),
                $minimum === null ? null : (int) $minimum,
                (int) $top,
                (bool) $this->input->getOption('html'),
            );
        } catch (Throwable $exception) {
            $result = (new OperationFailureMapper)->map($this->base, 'qa.report', ['kind' => 'report'], $exception);
        }
        if ((bool) $this->input->getOption('json') || $result->status === 'error' && ! isset($result->data['site'])) {
            $this->output->write((bool) $this->input->getOption('json') ? (new OperationResultFormatter)->json($result) : (new OperationResultFormatter)->human($result));

            return $result->exitCode;
        }
        $this->output->write(self::summary($result));

        return $result->exitCode;
    }

    /** The human summary of `docara qa report`. */
    public static function summary(OperationResult $result): string
    {
        $data = $result->data;
        $site = $data['site'];
        $lines = [sprintf(
            'Documentation quality: %d/100 — %d page(s) scored, %d need attention, %d skipped.',
            $site['score'],
            $site['pages'],
            $site['pages_needing_attention'],
            $site['skipped'],
        ), ''];
        foreach ($data['groups'] as $group => $value) {
            $lines[] = sprintf('  %-14s %s', $group, $value['score'] === null ? 'n/a (' . str_replace('_', ' ', (string) ($value['reason'] ?? 'not applicable')) . ')' : $value['score']);
        }
        if ($data['fix_first'] !== []) {
            $lines[] = '';
            $lines[] = 'Fix first:';
            foreach ($data['fix_first'] as $index => $item) {
                $lines[] = sprintf('%3d. +%.2f  %s  %s', $index + 1, $item['impact'], $item['url'], $item['code']);
                $lines[] = '       ' . $item['detail'] . ' ' . $item['hint'];
            }
        }
        if (($data['by_check'] ?? []) !== []) {
            $lines[] = '';
            $lines[] = 'Biggest gains by check (fixed on every page):';
            foreach ($data['by_check'] as $item) {
                $lines[] = sprintf('  +%.2f  %s on %d page(s)', $item['impact'], $item['code'], $item['pages']);
            }
        }
        $lines[] = '';
        foreach ($data['files'] as $kind => $file) {
            $lines[] = sprintf('Report (%s): %s', $kind, $file);
        }
        if (($data['gate']['min_score'] ?? null) !== null) {
            $lines[] = sprintf('Minimum score %d: %s.', $data['gate']['min_score'], $data['gate']['passed'] ? 'passed' : 'FAILED');
        }

        return implode("\n", $lines) . "\n";
    }
}
