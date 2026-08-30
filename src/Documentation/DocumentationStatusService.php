<?php

declare(strict_types=1);

namespace Simai\Docara\Documentation;

use JsonException;
use Simai\Docara\Application\PageInspectionService;
use Simai\Docara\Application\ProjectRuntime;
use Simai\Docara\File\ProjectFilesystemGuard;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\PortableSite\ProjectExampleRepository;

final class DocumentationStatusService
{
    /** @var array<string,list<array<string,mixed>>> */
    private array $pageCache = [];

    public function __construct(
        private readonly DocumentationSourceRepository $sources = new DocumentationSourceRepository,
        private readonly ProjectFilesystemGuard $writes = new ProjectFilesystemGuard,
    ) {}

    /** @return array<string,mixed> */
    public function report(string $root, ?string $sourceFilter = null, ?string $kindFilter = null, ?string $statusFilter = null): array
    {
        [$runtime, $tracking] = $this->configuration($root);
        if (($tracking['enabled'] ?? false) !== true) {
            return $this->finalize(['schema' => 'docara.documentation_status.v1', 'enabled' => false, 'source_locale' => null, 'mode' => 'report', 'sources' => [], 'diagnostics' => [], 'items' => []]);
        }
        $lock = $this->lock($runtime->root, (string) $tracking['lock_file'], (string) $tracking['source_locale']);
        $pages = [];
        if ($lock['entries'] !== []) {
            foreach ($this->pages($runtime->root) as $page) {
                if ($page['locale'] === $tracking['source_locale']) {
                    $pages[$page['route']] = $page;
                }
            }
        }
        $accepted = [];
        foreach ($lock['entries'] as $entry) {
            $accepted[$entry['source'] . "\0" . $entry['key']] = $entry;
        }
        $excluded = [];
        foreach ($lock['exclusions'] as $entry) {
            $excluded[$entry['source'] . "\0" . $entry['key']] = $entry;
        }
        $items = [];
        $sourceEntities = [];
        $sources = $this->sources->all($runtime->root);
        $sourceSummaries = [];
        $diagnostics = [];
        foreach ($sources as $source) {
            $sourceSummaries[] = [
                'id' => $source['id'], 'provider' => $source['provider'], 'revision' => $source['revision'],
                'contract_sha256' => $source['contract_sha256'], 'compatibility_adapter' => $source['compatibility_adapter'],
            ];
            if ($source['compatibility_adapter']) {
                $diagnostics[] = [
                    'code' => 'DOCUMENTATION_SOURCE_COMPATIBILITY_ADAPTER',
                    'severity' => 'warning',
                    'source' => $source['id'],
                    'message' => 'The pinned source predates a neutral documentation contract; status is derived read-only with limited public-surface precision.',
                ];
            }
            if ($sourceFilter !== null && $source['id'] !== $sourceFilter) {
                continue;
            }
            foreach ($source['entities'] as $entity) {
                if ($kindFilter !== null && $entity['kind'] !== $kindFilter) {
                    continue;
                }
                $identity = $source['id'] . "\0" . $entity['key'];
                $sourceEntities[$identity] = true;
                if (isset($excluded[$identity])) {
                    $items[] = $this->item($source, $entity, 'excluded', null, $excluded[$identity]['reason']);

                    continue;
                }
                $entry = $accepted[$identity] ?? null;
                if ($entry === null) {
                    $items[] = $this->item($source, $entity, 'new');

                    continue;
                }
                $page = $pages[$entry['route']] ?? null;
                if ($page === null || ! is_file($runtime->root . '/' . $entry['page_path'])) {
                    $items[] = $this->item($source, $entity, 'missing', $entry);

                    continue;
                }
                $missingCases = array_values(array_diff($entity['example_cases'], array_keys($entry['examples'])));
                $exampleHash = null;
                if ($missingCases === []) {
                    try {
                        $exampleHash = $this->examplesHash($runtime->root, $entry['examples']);
                    } catch (PortableConfigurationException) {
                        $missingCases = array_values($entity['example_cases']);
                    }
                }
                if ($missingCases !== []) {
                    $items[] = $this->item($source, $entity, 'missing_example', $entry, null, $missingCases);

                    continue;
                }
                $pageHash = $this->normalizedFileHash($runtime->root, (string) $entry['page_path']);
                if (! hash_equals((string) $entry['page_sha256'], $pageHash)
                    || ! hash_equals((string) $entry['examples_sha256'], (string) $exampleHash)
                ) {
                    $items[] = $this->item($source, $entity, 'unverified', $entry);

                    continue;
                }
                if (! hash_equals((string) $entry['source_sha256'], (string) $entity['source_sha256'])) {
                    $items[] = $this->item($source, $entity, 'changed', $entry);

                    continue;
                }
                $items[] = $this->item($source, $entity, 'current', $entry);
            }
        }
        foreach ($lock['entries'] as $entry) {
            $identity = $entry['source'] . "\0" . $entry['key'];
            if (isset($sourceEntities[$identity]) || ($sourceFilter !== null && $entry['source'] !== $sourceFilter) || ($kindFilter !== null && $entry['kind'] !== $kindFilter)) {
                continue;
            }
            $items[] = [
                'source' => $entry['source'], 'key' => $entry['key'], 'kind' => $entry['kind'], 'title' => null,
                'status' => 'orphan', 'route' => $entry['route'], 'page_path' => $entry['page_path'], 'source_revision' => null,
                'source_sha256' => null, 'accepted_source_sha256' => $entry['source_sha256'], 'page_sha256' => null,
                'examples_sha256' => null, 'review' => $entry['review'], 'reason' => null, 'missing_example_cases' => [], 'provenance' => [],
            ];
        }
        $allowed = ['current', 'new', 'changed', 'missing', 'missing_example', 'unverified', 'orphan', 'excluded'];
        if ($statusFilter !== null && ! in_array($statusFilter, $allowed, true)) {
            throw new PortableConfigurationException('DOCUMENTATION_STATUS_FILTER_INVALID', "Unknown documentation status [$statusFilter].");
        }
        if ($statusFilter !== null) {
            $items = array_values(array_filter($items, static fn (array $item): bool => $item['status'] === $statusFilter));
        }
        usort($items, static fn (array $a, array $b): int => [$a['source'], $a['kind'], $a['key']] <=> [$b['source'], $b['kind'], $b['key']]);

        return $this->finalize([
            'schema' => 'docara.documentation_status.v1', 'enabled' => true, 'source_locale' => $tracking['source_locale'],
            'mode' => $tracking['mode'], 'sources' => $sourceSummaries, 'diagnostics' => $diagnostics, 'items' => $items,
        ]);
    }

    /** @param array<string,string> $examples @return array<string,mixed> */
    public function planAccept(string $root, string $sourceId, string $key, string $route, string $review, array $examples = [], ?string $excludeReason = null): array
    {
        [$runtime, $tracking] = $this->configuration($root);
        if (($tracking['enabled'] ?? false) !== true) {
            throw new PortableConfigurationException('DOCUMENTATION_TRACKING_DISABLED', 'Enable documentation_tracking before accepting documentation state.');
        }
        if (! in_array($review, ['ai_verified', 'human_reviewed'], true)) {
            throw new PortableConfigurationException('DOCUMENTATION_REVIEW_INVALID', 'Review must be ai_verified or human_reviewed.');
        }
        $entity = $this->sources->entity($runtime->root, $sourceId, $key);
        if ($excludeReason !== null && trim($excludeReason) === '') {
            throw new PortableConfigurationException('DOCUMENTATION_EXCLUSION_REASON_REQUIRED', 'An exclusion requires a visible reason.');
        }
        $lockPath = (string) $tracking['lock_file'];
        $lock = $this->lock($runtime->root, $lockPath, (string) $tracking['source_locale']);
        $lock['entries'] = array_values(array_filter($lock['entries'], static fn (array $entry): bool => ! ($entry['source'] === $sourceId && $entry['key'] === $key)));
        $lock['exclusions'] = array_values(array_filter($lock['exclusions'], static fn (array $entry): bool => ! ($entry['source'] === $sourceId && $entry['key'] === $key)));
        $inputHashes = [$lockPath => $this->fileHashOrAbsent($runtime->root, $lockPath), 'docara.json' => $this->fileHashOrAbsent($runtime->root, 'docara.json')];
        if ($excludeReason !== null) {
            $lock['exclusions'][] = ['source' => $sourceId, 'key' => $key, 'kind' => $entity['kind'], 'reason' => trim($excludeReason)];
        } else {
            $matches = array_values(array_filter(
                $this->pages($runtime->root),
                static fn (array $page): bool => trim((string) $page['route'], '/') === trim($route, '/'),
            ));
            if (count($matches) !== 1) {
                throw new PortableConfigurationException('DOCUMENTATION_ROUTE_INVALID', "Documentation route [$route] is missing or ambiguous.");
            }
            $page = $matches[0];
            if ($page['locale'] !== $tracking['source_locale']) {
                throw new PortableConfigurationException('DOCUMENTATION_ROUTE_LOCALE_INVALID', 'Documentation acceptance requires a page in source_locale.');
            }
            $missing = array_values(array_diff($entity['example_cases'], array_keys($examples)));
            if ($missing !== []) {
                throw new PortableConfigurationException('DOCUMENTATION_EXAMPLE_CASES_MISSING', 'Required example cases are missing: ' . implode(', ', $missing));
            }
            $pagePath = (string) $page['source'];
            $inputHashes[$pagePath] = $this->fileHashOrAbsent($runtime->root, $pagePath);
            foreach ($examples as $exampleId) {
                $inputHashes['examples/' . $exampleId] = $this->exampleHash($runtime->root, $exampleId);
            }
            $lock['entries'][] = [
                'source' => $sourceId, 'key' => $key, 'kind' => $entity['kind'], 'route' => $page['route'], 'page_path' => $pagePath,
                'examples' => $examples, 'source_sha256' => $entity['source_sha256'],
                'page_sha256' => $this->normalizedFileHash($runtime->root, $pagePath), 'examples_sha256' => $this->examplesHash($runtime->root, $examples),
                'review' => $review,
            ];
        }
        usort($lock['entries'], static fn (array $a, array $b): int => [$a['source'], $a['kind'], $a['key']] <=> [$b['source'], $b['kind'], $b['key']]);
        usort($lock['exclusions'], static fn (array $a, array $b): int => [$a['source'], $a['kind'], $a['key']] <=> [$b['source'], $b['kind'], $b['key']]);
        ksort($inputHashes, SORT_STRING);
        (new SchemaRepository)->assertValid($lock, 'documentation-lock.schema.json');
        $core = [
            'schema' => 'docara.documentation_accept_plan.v1',
            'source' => $sourceId,
            'key' => $key,
            'source_sha256' => $entity['source_sha256'],
            'lock_file' => $lockPath,
            'input_hashes' => $inputHashes,
            'lock' => $lock,
        ];
        $planId = hash('sha256', CanonicalJson::encode($core));
        $plan = ['plan_id' => $planId] + $core;
        $this->writes->putNewOrIdentical(
            $runtime->root,
            '.docara/documentation-plans/' . $planId . '.json',
            CanonicalJson::encodePretty($plan),
            'DOCUMENTATION_PLAN_COLLISION',
        );

        return $plan;
    }

    /** @return array<string,mixed> */
    public function apply(string $root, string $planId): array
    {
        [$runtime] = $this->configuration($root);
        if (preg_match('/^[a-f0-9]{64}$/D', $planId) !== 1) {
            throw new PortableConfigurationException('DOCUMENTATION_PLAN_ID_INVALID', 'Apply requires the exact SHA-256 plan id.');
        }
        $path = $this->writes->regularFile($runtime->root, '.docara/documentation-plans/' . $planId . '.json');
        $plan = json_decode($this->safeAbsoluteFile($runtime->root, $path, 'DOCUMENTATION_PLAN_INVALID'), true, 512, JSON_THROW_ON_ERROR);
        $core = $plan;
        unset($core['plan_id']);
        if (($plan['plan_id'] ?? null) !== $planId || ! hash_equals($planId, hash('sha256', CanonicalJson::encode($core)))) {
            throw new PortableConfigurationException('DOCUMENTATION_PLAN_HASH_MISMATCH', 'Documentation plan does not match its id.');
        }
        foreach ($plan['input_hashes'] as $relative => $expected) {
            $actual = str_starts_with($relative, 'examples/')
                ? $this->exampleHash($runtime->root, substr($relative, 9))
                : $this->fileHashOrAbsent($runtime->root, $relative);
            if (! hash_equals((string) $expected, $actual)) {
                throw new PortableConfigurationException('DOCUMENTATION_PLAN_STALE', "Input [$relative] changed after dry-run.");
            }
        }
        $currentSource = $this->sources->entity($runtime->root, (string) $plan['source'], (string) $plan['key']);
        if (! hash_equals((string) $plan['source_sha256'], (string) $currentSource['source_sha256'])) {
            throw new PortableConfigurationException('DOCUMENTATION_PLAN_STALE', 'Documentation source contract changed after dry-run.');
        }
        (new SchemaRepository)->assertValid($plan['lock'], 'documentation-lock.schema.json');
        $lockPath = $runtime->root . '/' . $plan['lock_file'];
        $this->writeAtomic($lockPath, CanonicalJson::encodePretty($plan['lock']), true);

        return ['schema' => 'docara.documentation_accept_result.v1', 'status' => 'applied', 'plan_id' => $planId, 'lock_file' => $plan['lock_file']];
    }

    /** @return array{0:ProjectRuntime,1:array<string,mixed>} */
    private function configuration(string $root): array
    {
        $runtime = ProjectRuntime::load($root);
        $tracking = is_array($runtime->site['documentation_tracking'] ?? null) ? $runtime->site['documentation_tracking'] : [
            'enabled' => false, 'source_locale' => $runtime->site['default_locale'] ?? '', 'mode' => 'report', 'lock_file' => 'documentation.lock.json', 'sources' => [],
        ];

        return [$runtime, $tracking];
    }

    /** @return list<array<string,mixed>> */
    private function pages(string $root): array
    {
        $key = FilesystemPath::normalize((string) realpath($root));

        return $this->pageCache[$key] ??= (new PageInspectionService)->list($key);
    }

    /** @return array<string,mixed> */
    private function lock(string $root, string $relative, string $sourceLocale): array
    {
        if (! file_exists($root . '/' . $relative) && ! is_link($root . '/' . $relative)) {
            return ['schema' => 'docara.documentation_lock.v1', 'source_locale' => $sourceLocale, 'entries' => [], 'exclusions' => []];
        }
        try {
            $lock = json_decode($this->safeAbsoluteFile($root, $root . '/' . $relative, 'DOCUMENTATION_LOCK_INVALID'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException('DOCUMENTATION_LOCK_INVALID', 'Documentation lock is not valid JSON.', $exception);
        }
        (new SchemaRepository)->assertValid($lock, 'documentation-lock.schema.json');
        if ($lock['source_locale'] !== $sourceLocale) {
            throw new PortableConfigurationException('DOCUMENTATION_LOCK_SOURCE_LOCALE_MISMATCH', 'Documentation lock source locale differs from config.');
        }
        $identities = [];
        foreach (['entries', 'exclusions'] as $collection) {
            foreach ($lock[$collection] as $entry) {
                $identity = $entry['source'] . "\0" . $entry['key'];
                if (isset($identities[$identity])) {
                    throw new PortableConfigurationException('DOCUMENTATION_LOCK_DUPLICATE', "Documentation entity [{$entry['source']}:{$entry['key']}] is bound more than once.");
                }
                $identities[$identity] = true;
            }
        }

        return $lock;
    }

    /** @param array<string,mixed> $source @param array<string,mixed> $entity @param array<string,mixed>|null $entry @param list<string> $missing */
    private function item(array $source, array $entity, string $status, ?array $entry = null, ?string $reason = null, array $missing = []): array
    {
        return [
            'source' => $source['id'], 'key' => $entity['key'], 'kind' => $entity['kind'], 'title' => $entity['title'], 'status' => $status,
            'route' => $entry['route'] ?? null, 'page_path' => $entry['page_path'] ?? null, 'source_revision' => $source['revision'],
            'source_sha256' => $entity['source_sha256'], 'accepted_source_sha256' => $entry['source_sha256'] ?? null,
            'page_sha256' => $entry['page_sha256'] ?? null, 'examples_sha256' => $entry['examples_sha256'] ?? null,
            'review' => $entry['review'] ?? null, 'reason' => $reason, 'missing_example_cases' => $missing, 'provenance' => $entity['provenance'],
        ];
    }

    /** @param array<string,mixed> $report @return array<string,mixed> */
    private function finalize(array $report): array
    {
        $summary = ['total' => count($report['items'])];
        foreach (['current', 'new', 'changed', 'missing', 'missing_example', 'unverified', 'orphan', 'excluded'] as $status) {
            $summary[$status] = count(array_filter($report['items'], static fn (array $item): bool => $item['status'] === $status));
        }
        $report['summary'] = $summary;
        $core = $report;
        $report['content_sha256'] = hash('sha256', CanonicalJson::encode($core));
        (new SchemaRepository)->assertValid($report, 'documentation-status.schema.json');

        return $report;
    }

    /** @param array<string,string> $examples */
    private function examplesHash(string $root, array $examples): string
    {
        ksort($examples, SORT_STRING);
        $hashes = [];
        foreach ($examples as $case => $id) {
            $hashes[$case] = ['id' => $id, 'sha256' => $this->exampleHash($root, $id)];
        }

        return hash('sha256', CanonicalJson::encode($hashes));
    }

    private function exampleHash(string $root, string $id): string
    {
        $repository = new ProjectExampleRepository($root);
        $repository->load($id, 'documentation-tracking');
        $receipt = $repository->receipt();

        return (string) $receipt['examples'][0]['content_sha256'];
    }

    private function normalizedFileHash(string $root, string $relative): string
    {
        $contents = $this->safeAbsoluteFile($root, $root . '/' . $relative, 'DOCUMENTATION_PAGE_INVALID');

        return hash('sha256', str_replace(["\r\n", "\r"], "\n", preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents));
    }

    private function fileHashOrAbsent(string $root, string $relative): string
    {
        $path = $root . '/' . $relative;

        if (file_exists($path) || is_link($path)) {
            $stat = @lstat($path);
            if (! is_array($stat) || is_link($path) || ! is_file($path) || ($stat['nlink'] ?? 1) !== 1) {
                throw new PortableConfigurationException('DOCUMENTATION_INPUT_UNSAFE', "Input [$relative] is unsafe.");
            }
        }

        return is_file($path) ? (hash_file('sha256', $path) ?: 'absent') : 'absent';
    }

    private function safeAbsoluteFile(string $root, string $path, string $code): string
    {
        $root = FilesystemPath::normalize((string) realpath($root));
        $stat = @lstat($path);
        $real = realpath($path);
        if (! is_array($stat) || $real === false || is_link($path) || ! is_file($real) || ($stat['nlink'] ?? 1) !== 1
            || ! FilesystemPath::isWithin(FilesystemPath::normalize($real), $root)
        ) {
            throw new PortableConfigurationException($code, "File [$path] is missing or unsafe.");
        }
        $contents = file_get_contents($real);
        if (! is_string($contents) || preg_match('//u', $contents) !== 1) {
            throw new PortableConfigurationException($code, "File [$path] must be valid UTF-8.");
        }

        return $contents;
    }

    private function writeAtomic(string $path, string $contents, bool $overwrite): void
    {
        $directory = dirname($path);
        $name = basename($path);
        $entries = is_dir($directory) && ! is_link($directory) ? scandir($directory) : false;
        if (is_array($entries)) {
            foreach ($entries as $entry) {
                if ($entry !== $name && strcasecmp($entry, $name) === 0) {
                    throw new PortableConfigurationException('DOCUMENTATION_WRITE_CASE_COLLISION', "Path [$path] conflicts by case with [$entry].");
                }
            }
        }
        $stat = @lstat($path);
        if (is_array($stat) && (($stat['nlink'] ?? 1) !== 1 || is_link($path) || ! is_file($path))) {
            throw new PortableConfigurationException('DOCUMENTATION_WRITE_PATH_UNSAFE', "Cannot safely replace [$path].");
        }
        if ((! $overwrite && (file_exists($path) || is_link($path))) || is_link(dirname($path)) || is_link($path)) {
            throw new PortableConfigurationException('DOCUMENTATION_WRITE_PATH_UNSAFE', "Cannot safely write [$path].");
        }
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new PortableConfigurationException('DOCUMENTATION_WRITE_FAILED', "Cannot create [$directory].");
        }
        $temporary = tempnam($directory, '.docara-documentation-');
        if (! is_string($temporary) || file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents) || ! rename($temporary, $path)) {
            if (is_string($temporary)) {
                @unlink($temporary);
            }
            throw new PortableConfigurationException('DOCUMENTATION_WRITE_FAILED', "Cannot write [$path].");
        }
    }
}
