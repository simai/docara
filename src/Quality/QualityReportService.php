<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

use Simai\Docara\Application\Diagnostic;
use Simai\Docara\Application\OperationResult;
use Simai\Docara\Application\PageInspectionService;
use Simai\Docara\Application\ProjectRuntime;
use Simai\Docara\Authoring\AuthoringContract;
use Simai\Docara\Authoring\DocumentationStandard;
use Simai\Docara\Documentation\DocumentationStatusService;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\BuildReceipts;

/**
 * The documentation quality report of a built site. It reads the build
 * (`build_<env>/`: pages, menus, search index; `build_<env>.docara/`:
 * documentation tracking status) and the page sources (front matter,
 * profiles), runs the checks of QualityChecks on every page and scores them
 * with QualityScore. It never changes a source file and never uses the
 * network.
 */
final readonly class QualityReportService
{
    public const SCHEMA = 'docara.quality_report.v1';

    public const REPORT_FILE = 'quality-report.json';

    public const HTML_FILE = 'quality-report.html';

    public function __construct(
        private QualityScore $score = new QualityScore,
        private QualityPageAnalyzer $analyzer = new QualityPageAnalyzer,
    ) {}

    /**
     * `docara qa report`: analyze, write the JSON report (and the HTML page
     * when asked) next to the build, and compare with the minimum score.
     */
    public function run(string $root, string $environment = 'local', ?int $minScore = null, int $top = 10, bool $html = false): OperationResult
    {
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/D', $environment) !== 1) {
            throw new PortableConfigurationException('QUALITY_ENVIRONMENT_INVALID', 'The build environment may contain only letters, digits, underscores and hyphens.');
        }
        if ($minScore !== null && ($minScore < 0 || $minScore > 100)) {
            throw new PortableConfigurationException('QUALITY_MIN_SCORE_INVALID', 'The minimum score is a whole number from 0 to 100.');
        }
        $root = rtrim($root, '/\\');
        $report = $this->analyze($root, $root . '/build_' . $environment, $top);
        $minimum = $minScore ?? (AuthoringContract::load($root)->quality['min_score'] ?? null);
        $report['gate'] = ['min_score' => $minimum, 'passed' => $minimum === null || $report['site']['score'] >= $minimum];

        $receipts = BuildReceipts::directoryFor($root . '/build_' . $environment);
        $files = ['json' => $this->relative($root, self::writeReceipt($receipts, self::REPORT_FILE, CanonicalJson::encodePretty($report)))];
        if ($html) {
            $files['html'] = $this->relative($root, self::writeReceipt($receipts, self::HTML_FILE, (new QualityReportHtml)->render($report)));
        }
        $data = [
            'schema' => self::SCHEMA,
            'site' => $report['site'],
            'groups' => $report['groups'],
            'fix_first' => $report['fix_first'],
            'by_check' => array_slice($report['by_check'], 0, 5),
            'gate' => $report['gate'],
            'files' => $files,
        ];
        if (! $report['gate']['passed']) {
            return new OperationResult('qa.report', 'error', 1, $environment, $data, [new Diagnostic(
                'QUALITY_SCORE_BELOW_MINIMUM',
                'error',
                sprintf('Documentation quality %d/100 is below the minimum %d.', $report['site']['score'], $minimum),
                $files['json'],
                owner: 'docara.quality',
                suggestion: 'Fix the "fix first" items of the report, or lower --min-score / quality.min_score.',
            )], OperationResult::success('qa.report', $environment)->provenance);
        }

        return OperationResult::success('qa.report', $environment, $data);
    }

    /**
     * The whole report of one build directory, without writing anything.
     *
     * @return array<string, mixed>
     */
    public function analyze(string $root, string $destination, int $top = 10): array
    {
        $root = rtrim($root, '/\\');
        $destination = rtrim($destination, '/\\');
        $metadataPath = $destination . '/_docara/page-metadata.json';
        if (! is_file($metadataPath) || is_link($metadataPath)) {
            throw new PortableConfigurationException('QUALITY_BUILD_MISSING', 'No full build in [' . basename($destination) . ']; run docara build first.');
        }
        $metadata = json_decode((string) file_get_contents($metadataPath), true, 512, JSON_THROW_ON_ERROR);
        $runtime = ProjectRuntime::load($root);
        $contract = AuthoringContract::load($root);
        $resolver = new StaticLinkResolver((string) ($runtime->site['base_url'] ?? '/'));
        $reader = new HtmlPageReader;

        $sources = [];
        $drafts = [];
        foreach ((new PageInspectionService)->profileReport($root) as $source) {
            $sources[$source['source']] = $source;
            if ($source['draft']) {
                $drafts[rtrim($source['route'], '/') . '/'] = true;
            }
        }

        // Read every built page once.
        $pages = [];
        $navigation = [];
        foreach ($metadata['pages'] ?? [] as $entry) {
            $url = (string) ($entry['url'] ?? '');
            $file = $this->fileOf($resolver, $url);
            $path = $destination . '/' . $file;
            if ($url === '' || ! is_file($path) || is_link($path)) {
                continue;
            }
            $facts = $reader->read((string) file_get_contents($path));
            foreach ($facts['navigation'] as $href) {
                $navigation[$this->urlPath($href)] = true;
            }
            $pages[] = ['entry' => $entry, 'url' => $url, 'file' => $file, 'facts' => $facts, 'source' => $sources[(string) ($entry['source'] ?? '')] ?? null];
        }
        unset($navigation['']);

        $search = null;
        $searchPath = $destination . '/_docara/search-index.json';
        if (is_file($searchPath) && ! is_link($searchPath)) {
            $search = [];
            foreach (json_decode((string) file_get_contents($searchPath), true, 512, JSON_THROW_ON_ERROR)['documents'] ?? [] as $document) {
                $search[$this->urlPath((string) ($document['url'] ?? ''))] = true;
            }
        }

        $site = new QualitySite(
            $resolver,
            static fn (string $file): bool => $file !== '' && is_file($destination . '/' . $file) && ! is_link($destination . '/' . $file),
            static fn (string $file): array => $reader->read((string) file_get_contents($destination . '/' . $file))['ids'],
            $navigation,
            $search,
            $drafts,
        );
        $inbound = [];
        foreach ($pages as $page) {
            foreach ($this->analyzer->linkedFiles($page['file'], $page['facts']['links'], $site) as $target) {
                $url = $resolver->url($target);
                $inbound[$url] = ($inbound[$url] ?? 0) + 1;
            }
        }
        $glossaries = $this->glossaries($pages, $contract);
        $site = new QualitySite(
            $resolver,
            static fn (string $file): bool => $file !== '' && is_file($destination . '/' . $file) && ! is_link($destination . '/' . $file),
            static fn (string $file): array => $reader->read((string) file_get_contents($destination . '/' . $file))['ids'],
            $navigation,
            $search,
            $drafts,
            $inbound,
            $glossaries,
            (new DocumentationStandard)->placeholderLines(),
        );

        $freshness = $this->freshness($root, $destination, $runtime->site);
        $groups = $freshness === null ? array_values(array_diff(QualityChecks::GROUPS, ['freshness'])) : QualityChecks::GROUPS;

        $scored = [];
        $skipped = [];
        foreach ($pages as $page) {
            $source = $page['source'];
            $relative = $source['relative'] ?? null;
            if ($page['facts']['noindex']) {
                $skipped[] = ['url' => $page['url'], 'reason' => 'noindex'];

                continue;
            }
            if (is_string($relative) && $contract->excludedFromQuality($relative)) {
                $skipped[] = ['url' => $page['url'], 'reason' => 'quality.exclude'];

                continue;
            }
            $front = $source['front_matter'] ?? [];
            $findings = $this->analyzer->analyze([
                'url' => $page['url'],
                'file' => $page['file'],
                'locale' => (string) ($page['entry']['locale'] ?? $source['locale'] ?? ''),
                'home' => $relative === 'index.md',
                'description' => self::authored($root, $source, 'description'),
                'title' => (string) ($page['entry']['title'] ?? ''),
                'tags' => is_array($front['tags'] ?? null) ? array_values($front['tags']) : [],
                'markdown' => (string) ($source['markdown'] ?? ''),
                'profile_missing' => $source['profile_missing'] ?? [],
                'profile_inferred' => ($source['profile_source'] ?? null) === 'inferred',
                'freshness' => $freshness === null ? null : ($freshness[$this->urlPath($page['url'])] ?? []),
                'facts' => $page['facts'],
            ], $site);
            $groupScores = $this->score->groupScores($findings, $groups);
            $scored[] = [
                'url' => $page['url'],
                'source' => $source['source'] ?? ($page['entry']['source'] ?? null),
                'title' => (string) ($page['entry']['title'] ?? ''),
                'profile' => $source['profile'] ?? null,
                'metrics' => $this->analyzer->metrics(),
                'score' => $this->score->pageScore($groupScores),
                'groups' => $groupScores,
                'findings' => $findings,
            ];
        }

        return $this->assemble($scored, $skipped, $groups, $top, $freshness !== null);
    }

    /**
     * @param  list<array<string, mixed>>  $scored
     * @param  list<array{url:string,reason:string}>  $skipped
     * @param  list<string>  $groups
     * @return array<string, mixed>
     */
    private function assemble(array $scored, array $skipped, array $groups, int $top, bool $freshness): array
    {
        $count = count($scored);
        $impacts = [];
        foreach ($scored as $page) {
            foreach ($page['findings'] as $finding) {
                $impacts[] = [
                    // Rounded so that float noise never decides the order.
                    'impact' => round($this->score->impact($page['findings'], $groups, $finding['code'], $count), 6),
                    'inbound' => (int) $page['metrics']['inbound_links'],
                    'url' => $page['url'],
                    'source' => $page['source'],
                    'code' => $finding['code'],
                    'group' => $finding['group'],
                    'severity' => $finding['severity'],
                    'detail' => $finding['detail'],
                    'hint' => QualityChecks::get($finding['code'])['hint'],
                ];
            }
        }
        // Equal impact: warnings first, then pages more other pages link to
        // (more readers reach them), then by URL and code.
        usort($impacts, static fn (array $a, array $b): int => [$b['impact'], $a['severity'] === 'warning' ? 0 : 1, $b['inbound'], $a['url'], $a['code']]
            <=> [$a['impact'], $b['severity'] === 'warning' ? 0 : 1, $a['inbound'], $b['url'], $b['code']]);
        $fixFirst = array_map(
            static fn (array $item): array => ['impact' => QualityScore::impactDisplay($item['impact'])] + array_diff_key($item, ['inbound' => true]),
            array_slice($impacts, 0, max(0, $top)),
        );

        // The same check fixed on every page that fails it.
        $byCheck = [];
        foreach ($impacts as $item) {
            $byCheck[$item['code']] ??= ['code' => $item['code'], 'group' => $item['group'], 'severity' => $item['severity'], 'pages' => 0, 'impact' => 0.0, 'hint' => $item['hint']];
            $byCheck[$item['code']]['pages']++;
            $byCheck[$item['code']]['impact'] += $item['impact'];
        }
        usort($byCheck, static fn (array $a, array $b): int => [$b['impact'], $a['code']] <=> [$a['impact'], $b['code']]);
        $byCheck = array_map(static fn (array $item): array => ['impact' => QualityScore::impactDisplay($item['impact'])] + $item, $byCheck);

        $siteGroups = [];
        foreach (QualityChecks::GROUPS as $group) {
            $siteGroups[$group] = in_array($group, $groups, true)
                ? ['score' => QualityScore::display($this->score->mean(array_map(static fn (array $page): float => $page['groups'][$group], $scored))), 'weight' => QualityScore::WEIGHTS[$group]]
                : ['score' => null, 'weight' => QualityScore::WEIGHTS[$group], 'reason' => $group === 'freshness' && ! $freshness ? 'documentation_tracking_off' : 'not_applicable'];
        }
        $codes = [];
        foreach ($scored as $page) {
            foreach ($page['findings'] as $finding) {
                $codes[$finding['code']] = ($codes[$finding['code']] ?? 0) + 1;
            }
        }
        ksort($codes);
        $attention = count(array_filter($scored, static fn (array $page): bool => in_array('warning', array_column($page['findings'], 'severity'), true)));
        usort($scored, static fn (array $a, array $b): int => [$a['score'], $a['url']] <=> [$b['score'], $b['url']]);
        $metric = static fn (string $name): array => array_values(array_filter(array_map(static fn (array $page): mixed => $page['metrics'][$name], $scored), static fn (mixed $value): bool => $value !== null));
        $words = $metric('words');
        sort($words);
        $latin = $metric('latin_share');
        $sentences = $metric('average_sentence_words');

        return [
            'schema' => self::SCHEMA,
            'site' => [
                'score' => QualityScore::display($this->score->mean(array_column($scored, 'score'))),
                'pages' => $count,
                'pages_needing_attention' => $attention,
                'skipped' => count($skipped),
            ],
            'statistics' => [
                'median_words' => $words === [] ? 0 : $words[intdiv(count($words), 2)],
                'average_sentence_words' => $sentences === [] ? null : round(array_sum($sentences) / count($sentences), 1),
                'average_latin_share' => $latin === [] ? null : round(array_sum($latin) / count($latin), 3),
                'pages_without_links_to_pages' => count(array_filter($scored, static fn (array $page): bool => $page['metrics']['links_to_pages'] === 0)),
                'pages_without_inbound_links' => count(array_filter($scored, static fn (array $page): bool => $page['metrics']['inbound_links'] === 0)),
            ],
            'groups' => $siteGroups,
            'formula' => [
                'penalties' => QualityScore::PENALTIES,
                'weights' => QualityScore::WEIGHTS,
                'page' => 'weighted mean of the group scores that apply',
                'site' => 'mean of the page scores',
            ],
            'checks' => array_map(static fn (array $check): array => ['group' => $check['group'], 'severity' => $check['severity'], 'hint' => $check['hint']], QualityChecks::ALL),
            'counts' => $codes,
            'fix_first' => $fixFirst,
            'by_check' => $byCheck,
            'pages' => array_map(static fn (array $page): array => [
                'url' => $page['url'],
                'source' => $page['source'],
                'title' => $page['title'],
                'profile' => $page['profile'],
                'metrics' => $page['metrics'],
                'score' => QualityScore::display($page['score']),
                'groups' => array_map(QualityScore::display(...), $page['groups']),
                'findings' => $page['findings'],
            ], $scored),
            'skipped' => $skipped,
        ];
    }

    /**
     * Documentation tracking items that need a review, by page URL; null when
     * tracking is off. The build receipt is read when it exists, so the report
     * shows the same status the build saw.
     *
     * @param  array<string, mixed>  $site
     * @return array<string, list<array{source:string,key:string,status:string}>>|null
     */
    private function freshness(string $root, string $destination, array $site): ?array
    {
        if (($site['documentation_tracking']['enabled'] ?? false) !== true) {
            return null;
        }
        $receipt = BuildReceipts::existing($destination) . '/documentation-status.json';
        $report = is_file($receipt) && ! is_link($receipt)
            ? json_decode((string) file_get_contents($receipt), true, 512, JSON_THROW_ON_ERROR)
            : (new DocumentationStatusService)->report($root);
        $items = [];
        foreach ($report['items'] ?? [] as $item) {
            $status = (string) ($item['status'] ?? '');
            if (! isset($item['route']) || in_array($status, ['current', 'excluded'], true)) {
                continue;
            }
            $items[$this->urlPath((string) $item['route'])][] = ['source' => (string) $item['source'], 'key' => (string) $item['key'], 'status' => $status];
        }

        return $items;
    }

    /**
     * Glossary vocabulary per locale: the page set in quality.glossary, or a
     * page named glossary, глоссарий or словарь.
     *
     * @param  list<array<string, mixed>>  $pages
     * @return array<string, array<string, true>>
     */
    private function glossaries(array $pages, AuthoringContract $contract): array
    {
        $configured = $contract->quality['glossary'] ?? null;
        $glossaries = [];
        foreach ($pages as $page) {
            $relative = (string) ($page['source']['relative'] ?? '');
            $named = preg_match('#(?:^|/)(?:glossary|глоссарий|словарь)(?:\.md|/index\.md)$#u', $relative) === 1;
            if ($configured !== null ? $this->urlPath($page['url']) !== $this->urlPath($configured) : ! $named) {
                continue;
            }
            $locale = (string) ($page['entry']['locale'] ?? '');
            $glossaries[$locale] = ($glossaries[$locale] ?? []) + $this->analyzer->glossaryWords($page['facts']['terms']);
        }

        return $glossaries;
    }

    /**
     * Writes one file into the receipt directory of a build, next to the
     * published folder and never inside it. The directory and the file must
     * not be symbolic links; the file is replaced atomically.
     */
    public static function writeReceipt(string $directory, string $name, string $contents): string
    {
        if (is_link($directory) || (file_exists($directory) && ! is_dir($directory))) {
            throw new PortableConfigurationException('QUALITY_OUTPUT_UNSAFE', 'The receipt directory [' . basename($directory) . '] must be a real directory.');
        }
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new PortableConfigurationException('QUALITY_OUTPUT_UNSAFE', 'The receipt directory [' . basename($directory) . '] could not be created.');
        }
        $path = $directory . '/' . $name;
        if (is_link($path) || (file_exists($path) && ! is_file($path))) {
            throw new PortableConfigurationException('QUALITY_OUTPUT_UNSAFE', 'The report file [' . $name . '] must be a regular file.');
        }
        $temporary = $directory . '/.' . $name . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($temporary, $contents) !== strlen($contents) || ! rename($temporary, $path)) {
            @unlink($temporary);
            throw new PortableConfigurationException('QUALITY_OUTPUT_UNSAFE', 'The report file [' . $name . '] could not be written.');
        }

        return $path;
    }

    /**
     * A page value an author wrote (title, description): front matter first,
     * then the page file `<page>.page.json`. A value the build derives, such
     * as a description from the first paragraph, does not count.
     *
     * @param  array<string, mixed>|null  $source  an item of PageInspectionService::profileReport()
     */
    public static function authored(string $root, ?array $source, string $key): ?string
    {
        if ($source === null) {
            return null;
        }
        if (is_string($source['front_matter'][$key] ?? null)) {
            return $source['front_matter'][$key];
        }
        // The page file sits next to the Markdown with the same name, as
        // PortableConfigurationLoader reads it: index.md → index.page.json.
        $path = (string) $source['source'];
        $sidecar = rtrim($root, '/\\') . '/' . (dirname($path) === '.' ? '' : dirname($path) . '/') . pathinfo($path, PATHINFO_FILENAME) . '.page.json';
        if (! is_file($sidecar) || is_link($sidecar)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($sidecar), true);

        return is_array($data) && is_string($data[$key] ?? null) ? $data[$key] : null;
    }

    private function fileOf(StaticLinkResolver $resolver, string $url): string
    {
        $resolved = $resolver->resolve('index.html', $url);

        return $resolved['kind'] === 'local' ? (string) $resolved['target'] : '';
    }

    private function urlPath(string $href): string
    {
        $path = preg_split('/[?#]/', $href, 2)[0] ?? '';
        if ($path === '' || ! str_starts_with($path, '/')) {
            return '';
        }
        $path = rawurldecode($path);

        return str_ends_with($path, '.html') ? $path : rtrim($path, '/') . '/';
    }

    private function relative(string $root, string $path): string
    {
        return ltrim(substr($path, strlen($root)), '/');
    }
}
