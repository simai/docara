<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

use Simai\Docara\Application\OperationResult;
use Simai\Docara\Application\PageInspectionService;
use Simai\Docara\Application\ProjectRuntime;
use Simai\Docara\Authoring\AuthoringContract;
use Simai\Docara\Authoring\AuthoringProfileRegistry;
use Simai\Docara\Documentation\DocumentationStatusService;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\BuildReceipts;

/**
 * The semantic half of documentation quality, prepared but not judged.
 *
 * A review packet lists the questions only a reader can answer (is the page
 * true to its sources, clear for its audience, does it solve the task, do the
 * examples work) together with everything needed to answer them: the page
 * text, its profile and editorial checklist, its tracked sources and the
 * findings of the last quality report. Docara never calls an AI: a person or
 * an AI assistant reads the packet and answers in the format it describes.
 */
final readonly class ReviewPacketService
{
    public const SCHEMA = 'docara.review_packet.v1';

    public const DIRECTORY = 'review-packets';

    /** Questions asked of every page. */
    public const QUESTIONS = [
        'accuracy' => 'Check every statement, command, option, default value and code sample against the sources listed for the page. List each one that is wrong or out of date, with the correct value.',
        'clarity' => 'Read the page as its audience. List terms that are not explained, sentences that need a second reading and steps that assume knowledge the page does not give.',
        'task' => 'Name the task a reader comes to this page with. Can they finish it with this page alone? If not, say what is missing.',
        'examples' => 'Would every example and command run as written with the current version? Name each one that would fail and why.',
    ];

    /** The editorial checklist items of the page profiles, as questions. */
    public const CHECKLIST = [
        'audience_fit' => 'Does the page speak to the audience it names, in their words?',
        'value_proposition' => 'Does the reader learn in the first screen what they gain here?',
        'action_clarity' => 'Is the next action obvious and one click away?',
        'focus' => 'Does the page keep to one subject?',
        'clarity' => 'Can the reader follow every paragraph on the first reading?',
        'completeness' => 'Does the page cover what a reader of this subject expects?',
        'expected_outcome' => 'Does the lesson say at the start what the reader will have at the end?',
        'prerequisites' => 'Are all prerequisites named before the first step?',
        'verification_quality' => 'Can the reader check that the result works?',
        'task_boundary' => 'Is it clear where the task starts and ends?',
        'step_safety' => 'Is every step safe to run as written, with risky steps marked?',
        'result_confirmation' => 'Does the page say what the reader sees when the task is done?',
        'terminology' => 'Are names of options, fields and commands exact and consistent?',
        'parameter_accuracy' => 'Are types, defaults and allowed values exact?',
        'coverage' => 'Is every option, field or code of the subject listed?',
        'rationale' => 'Does the page explain why, not only what?',
        'limitations' => 'Are the limits and trade-offs named?',
        'related_materials' => 'Does the page link the guides and references a reader needs next?',
    ];

    public function run(string $root, ?string $route = null, string $environment = 'local'): OperationResult
    {
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/D', $environment) !== 1) {
            throw new PortableConfigurationException('QUALITY_ENVIRONMENT_INVALID', 'The build environment may contain only letters, digits, underscores and hyphens.');
        }
        $root = rtrim($root, '/\\');
        $packet = $this->packet($root, $route, $environment);
        $name = $route === null ? 'site' : (trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($route, '/'))), '-') ?: 'home');
        $receipts = BuildReceipts::directoryFor($root . '/build_' . $environment) . '/' . self::DIRECTORY;
        $json = QualityReportService::writeReceipt($receipts, $name . '.json', CanonicalJson::encodePretty($packet));
        $markdown = QualityReportService::writeReceipt($receipts, $name . '.md', $this->markdown($packet));
        $files = ['json' => ltrim(substr($json, strlen($root)), '/'), 'markdown' => ltrim(substr($markdown, strlen($root)), '/')];

        $data = ['schema' => self::SCHEMA, 'scope' => $packet['scope'], 'pages' => count($packet['pages']), 'files' => $files];
        if ($route !== null) {
            $data['packet'] = $packet;
        } else {
            $data['routes'] = array_column($packet['pages'], 'url');
        }

        return OperationResult::success('qa.review_packet', $route ?? 'site', $data, ProjectRuntime::load($root)->provenance());
    }

    /** @return array<string, mixed> */
    public function packet(string $root, ?string $route, string $environment = 'local'): array
    {
        $runtime = ProjectRuntime::load($root);
        $contract = AuthoringContract::load($runtime->root);
        $profiles = (new AuthoringProfileRegistry)->all();
        $findings = $this->findings($runtime->root . '/build_' . $environment);
        $tracking = ($runtime->site['documentation_tracking']['enabled'] ?? false) === true
            ? (new DocumentationStatusService)->report($runtime->root)['items']
            : [];

        $pages = [];
        foreach ((new PageInspectionService)->profileReport($runtime->root) as $source) {
            if ($source['draft'] && $route === null) {
                continue;
            }
            if ($route !== null && trim($route, '/') !== trim($source['route'], '/')) {
                continue;
            }
            $profile = $source['profile'] === null ? null : $profiles[$source['profile']];
            $checklist = [];
            foreach ($profile['editorial_checklist'] ?? [] as $item) {
                $checklist[] = ['id' => $item, 'question' => self::CHECKLIST[$item] ?? $item];
            }
            $relations = array_values(array_map(
                static fn (array $item): array => array_intersect_key($item, array_flip(['source', 'key', 'kind', 'status', 'reviewed_revision', 'revision'])),
                array_filter($tracking, static fn (array $item): bool => isset($item['route']) && trim((string) $item['route'], '/') === trim($source['route'], '/')),
            ));
            $pages[] = [
                'url' => $source['route'],
                'source' => $source['source'],
                'locale' => $source['locale'],
                'title' => $this->title($runtime->root, $source),
                'description' => QualityReportService::authored($runtime->root, $source, 'description'),
                'profile' => [
                    'id' => $source['profile'],
                    'source' => $source['profile_source'],
                    'purpose' => $profile['purpose'] ?? null,
                    'audience' => $contract->audiences !== [] ? $contract->audiences : ($profile['expected_audience'] ?? []),
                ],
                'checklist' => $checklist,
                'sources' => ['page' => $source['source'], 'tracked' => $relations],
                'quality_findings' => $findings[trim($source['route'], '/')] ?? [],
                'text' => trim($source['markdown'], "\n") . "\n",
            ];
        }
        if ($route !== null && $pages === []) {
            throw new PortableConfigurationException('PAGE_ROUTE_UNKNOWN', "No Markdown page owns route [$route].");
        }

        return [
            'schema' => self::SCHEMA,
            'scope' => $route === null ? 'site' : 'page',
            'instructions' => 'Answer every question of every page from the page text and its sources. Do not rewrite the page in the answer; '
                . 'name the exact sentence, value or example and the fix. Answer "unsure" when the sources do not settle a question.',
            'questions' => array_map(static fn (string $id, string $question): array => ['id' => $id, 'question' => $question], array_keys(self::QUESTIONS), self::QUESTIONS),
            'answer_format' => [
                'per_page' => ['url' => 'the page URL', 'answers' => [['question' => 'question or checklist id', 'verdict' => 'pass|fail|unsure', 'evidence' => 'quote from the page', 'fix' => 'what to change']]],
            ],
            'pages' => $pages,
        ];
    }

    /** @param array<string, mixed> $packet */
    public function markdown(array $packet): string
    {
        $lines = ['# Documentation review packet', '', $packet['instructions'], '', '## Questions for every page', ''];
        foreach ($packet['questions'] as $question) {
            $lines[] = '- `' . $question['id'] . '`: ' . $question['question'];
        }
        $lines[] = '';
        $lines[] = 'Answer per page with: question id, verdict (pass, fail or unsure), evidence quoted from the page, and the fix.';
        foreach ($packet['pages'] as $page) {
            $lines[] = '';
            $lines[] = '## ' . ($page['title'] ?? $page['url']) . ' (`' . $page['url'] . '`)';
            $lines[] = '';
            $lines[] = '- Source: `' . $page['source'] . '`';
            $lines[] = '- Profile: ' . ($page['profile']['id'] === null ? 'none' : '`' . $page['profile']['id'] . '` (' . $page['profile']['source'] . ') — ' . $page['profile']['purpose']);
            $lines[] = '- Audience: ' . ($page['profile']['audience'] === [] ? 'not set' : implode(', ', $page['profile']['audience']));
            if ($page['description'] !== null) {
                $lines[] = '- Description: ' . $page['description'];
            }
            foreach ($page['sources']['tracked'] as $tracked) {
                $lines[] = '- Tracked source: `' . ($tracked['source'] ?? '') . ':' . ($tracked['key'] ?? '') . '` (' . ($tracked['status'] ?? '') . ')';
            }
            if ($page['checklist'] !== []) {
                $lines[] = '';
                $lines[] = 'Editorial checklist:';
                $lines[] = '';
                foreach ($page['checklist'] as $item) {
                    $lines[] = '- `' . $item['id'] . '`: ' . $item['question'];
                }
            }
            if ($page['quality_findings'] !== []) {
                $lines[] = '';
                $lines[] = 'Findings of the quality report (already measured; confirm or explain):';
                $lines[] = '';
                foreach ($page['quality_findings'] as $finding) {
                    $lines[] = '- `' . $finding['code'] . '` (' . $finding['severity'] . '): ' . $finding['detail'];
                }
            }
            $fence = str_contains($page['text'], '````') ? '`````' : '````';
            $lines[] = '';
            $lines[] = 'Page text:';
            $lines[] = '';
            $lines[] = $fence . 'markdown';
            $lines[] = rtrim($page['text']);
            $lines[] = $fence;
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * The page title as the build shows it: front matter, then the page file,
     * then the first `#` heading.
     *
     * @param  array<string, mixed>  $source
     */
    private function title(string $root, array $source): ?string
    {
        $title = QualityReportService::authored($root, $source, 'title');
        if ($title !== null) {
            return $title;
        }

        return preg_match('/^#\s+(.+?)\s*$/m', (string) $source['markdown'], $match) === 1 ? $match[1] : null;
    }

    /** @return array<string, list<array<string, mixed>>> route without slashes → findings of the last report */
    private function findings(string $destination): array
    {
        $path = BuildReceipts::directoryFor($destination) . '/' . QualityReportService::REPORT_FILE;
        if (! is_file($path) || is_link($path)) {
            return [];
        }
        $report = json_decode((string) file_get_contents($path), true);
        $findings = [];
        foreach (is_array($report) ? ($report['pages'] ?? []) : [] as $page) {
            $findings[trim((string) ($page['url'] ?? ''), '/')] = $page['findings'] ?? [];
        }

        return $findings;
    }
}
