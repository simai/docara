<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use JsonException;
use Simai\Docara\Content\FrontMatterParser;
use Simai\Docara\Content\PageSourceLocator;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class TranslationStatusService
{
    /** @return array<string,mixed> */
    public function report(string $root, ?string $locale = null, ?string $status = null): array
    {
        [$root, $site, $tracking] = $this->configuration($root);
        if (($tracking['enabled'] ?? false) !== true) {
            return $this->finalize([
                'schema' => 'docara.translation_status.v1',
                'enabled' => false,
                'source_locale' => null,
                'mode' => 'report',
                'items' => [],
            ]);
        }
        $sourceLocale = (string) $tracking['source_locale'];
        $registry = LocaleRegistry::fromSite($site);
        $registry->get($sourceLocale);
        if ($locale !== null) {
            $registry->get($locale);
            if ($locale === $sourceLocale) {
                throw new PortableConfigurationException('TRANSLATION_TARGET_LOCALE_INVALID', 'Target locale must differ from source_locale.');
            }
        }
        $lock = $this->lock($root, (string) $tracking['lock_file'], $sourceLocale);
        $accepted = [];
        foreach ($lock['entries'] as $entry) {
            $accepted[$entry['kind'] . "\0" . $entry['key'] . "\0" . $entry['locale']] = $entry;
        }
        $excluded = [];
        foreach ($lock['exclusions'] as $entry) {
            $excluded[$entry['kind'] . "\0" . $entry['key'] . "\0" . $entry['locale']] = $entry;
        }

        $pages = [];
        $parser = new FrontMatterParser;
        $locator = new PageSourceLocator($root, $registry);
        foreach ($registry->all() as $localeCode => $definition) {
            foreach ($locator->forLocale($localeCode) as $source) {
                $contents = $this->regularUtf8File($root, $source->path, 'TRANSLATION_PAGE_SOURCE_INVALID');
                $document = $parser->parse($contents, $source->path);
                if (($document->metadata['draft'] ?? false) === true) {
                    continue;
                }
                $relative = substr($source->path, strlen(rtrim($definition->contentRoot, '/') . '/'));
                $key = (string) ($document->metadata['translation_key'] ?? $relative);
                $pages[$localeCode][$key][] = $this->sourceRecord($source->path, $contents);
            }
        }

        $items = [];
        foreach ($registry->all() as $targetLocale => $definition) {
            if ($targetLocale === $sourceLocale || ($locale !== null && $targetLocale !== $locale)) {
                continue;
            }
            $keys = array_values(array_unique([
                ...array_keys($pages[$sourceLocale] ?? []),
                ...array_keys($pages[$targetLocale] ?? []),
            ]));
            sort($keys, SORT_STRING);
            foreach ($keys as $key) {
                $sourceRecords = $pages[$sourceLocale][$key] ?? [];
                $targetRecords = $pages[$targetLocale][$key] ?? [];
                if (count($sourceRecords) > 1 || count($targetRecords) > 1) {
                    $items[] = $this->item('page', $key, $targetLocale, 'duplicate_key', $sourceRecords[0] ?? null, $targetRecords[0] ?? null);

                    continue;
                }
                if ($sourceRecords === []) {
                    $items[] = $this->item('page', $key, $targetLocale, 'orphan', null, $targetRecords[0] ?? null);

                    continue;
                }
                $identity = 'page' . "\0" . $key . "\0" . $targetLocale;
                if (isset($excluded[$identity])) {
                    $items[] = $this->item('page', $key, $targetLocale, 'excluded', $sourceRecords[0], $targetRecords[0] ?? null, null, $excluded[$identity]['reason']);

                    continue;
                }
                if ($targetRecords === []) {
                    $items[] = $this->item(
                        'page',
                        $key,
                        $targetLocale,
                        'missing',
                        $sourceRecords[0],
                        null,
                        fallbackLocale: $this->pageFallbackLocale($definition->fallbacks, $pages, $key),
                    );

                    continue;
                }
                $items[] = $this->acceptedItem('page', $key, $targetLocale, $sourceRecords[0], $targetRecords[0], $accepted[$identity] ?? null);
            }
        }
        array_push($items, ...$this->languageItems($root, $registry, $sourceLocale, $locale, $accepted, $excluded));
        if ($status !== null) {
            $allowed = ['current', 'stale', 'missing', 'unverified', 'orphan', 'duplicate_key', 'structure_mismatch', 'excluded'];
            if (! in_array($status, $allowed, true)) {
                throw new PortableConfigurationException('TRANSLATION_STATUS_FILTER_INVALID', "Unknown translation status [$status].");
            }
            $items = array_values(array_filter($items, static fn (array $item): bool => $item['status'] === $status));
        }
        usort($items, static fn (array $left, array $right): int => [$left['locale'], $left['kind'], $left['key']] <=> [$right['locale'], $right['kind'], $right['key']]);

        return $this->finalize([
            'schema' => 'docara.translation_status.v1',
            'enabled' => true,
            'source_locale' => $sourceLocale,
            'mode' => (string) $tracking['mode'],
            'items' => $items,
        ]);
    }

    /** @return array<string,mixed> */
    public function planAccept(
        string $root,
        string $locale,
        string $key,
        string $review,
        ?string $excludeReason = null,
        string $kind = 'page',
    ): array {
        [$root, $site, $tracking] = $this->configuration($root);
        if (($tracking['enabled'] ?? false) !== true) {
            throw new PortableConfigurationException('TRANSLATION_TRACKING_DISABLED', 'Enable translation_tracking before accepting translation state.');
        }
        if (! in_array($review, ['ai_verified', 'human_reviewed'], true)) {
            throw new PortableConfigurationException('TRANSLATION_REVIEW_INVALID', 'Review must be ai_verified or human_reviewed.');
        }
        if (! in_array($kind, ['page', 'lang'], true)) {
            throw new PortableConfigurationException('TRANSLATION_KIND_INVALID', 'Translation kind must be page or lang.');
        }
        $sourceLocale = (string) $tracking['source_locale'];
        if ($locale === $sourceLocale) {
            throw new PortableConfigurationException('TRANSLATION_TARGET_LOCALE_INVALID', 'Target locale must differ from source_locale.');
        }
        $report = $this->report($root, $locale);
        $matches = array_values(array_filter(
            $report['items'],
            static fn (array $item): bool => $item['kind'] === $kind && $item['key'] === $key && $item['locale'] === $locale,
        ));
        if (count($matches) !== 1) {
            throw new PortableConfigurationException('TRANSLATION_ACCEPT_TARGET_INVALID', "Translation [$kind:$key:$locale] is missing or ambiguous.");
        }
        $item = $matches[0];
        if (in_array($item['status'], ['orphan', 'duplicate_key'], true)
            || ($excludeReason === null && $item['status'] === 'structure_mismatch')
        ) {
            throw new PortableConfigurationException('TRANSLATION_ACCEPT_TARGET_INVALID', "Translation [$kind:$key:$locale] cannot be accepted while status is [{$item['status']}].");
        }
        if ($excludeReason !== null && trim($excludeReason) === '') {
            throw new PortableConfigurationException('TRANSLATION_EXCLUSION_REASON_REQUIRED', 'An exclusion requires a visible reason.');
        }
        if ($excludeReason === null && ($item['source_path'] === null || $item['target_path'] === null)) {
            throw new PortableConfigurationException('TRANSLATION_ACCEPT_TARGET_INVALID', 'A normal acceptance requires both source and target content.');
        }
        $lockPath = (string) $tracking['lock_file'];
        $lock = $this->lock($root, $lockPath, $sourceLocale);
        $identity = static fn (array $entry): string => $entry['kind'] . "\0" . $entry['key'] . "\0" . $entry['locale'];
        $wanted = $kind . "\0" . $key . "\0" . $locale;
        $lock['entries'] = array_values(array_filter($lock['entries'], static fn (array $entry): bool => $identity($entry) !== $wanted));
        $lock['exclusions'] = array_values(array_filter($lock['exclusions'], static fn (array $entry): bool => $identity($entry) !== $wanted));
        if ($excludeReason !== null) {
            $lock['exclusions'][] = ['kind' => $kind, 'key' => $key, 'locale' => $locale, 'reason' => trim($excludeReason)];
        } else {
            $lock['entries'][] = [
                'kind' => $kind,
                'key' => $key,
                'locale' => $locale,
                'source_path' => $item['source_path'],
                'target_path' => $item['target_path'],
                'source_sha256' => $item['source_sha256'],
                'target_sha256' => $item['target_sha256'],
                'source_structure_sha256' => $item['source_structure_sha256'],
                'target_structure_sha256' => $item['target_structure_sha256'],
                'review' => $review,
            ];
        }
        usort($lock['entries'], static fn (array $left, array $right): int => [$left['locale'], $left['kind'], $left['key']] <=> [$right['locale'], $right['kind'], $right['key']]);
        usort($lock['exclusions'], static fn (array $left, array $right): int => [$left['locale'], $left['kind'], $left['key']] <=> [$right['locale'], $right['kind'], $right['key']]);
        (new SchemaRepository)->assertValid($lock, 'translation-lock.schema.json');
        $inputHashes = $this->inputHashes($root, $lockPath, $item);
        $core = [
            'schema' => 'docara.translation_accept_plan.v1',
            'lock_file' => $lockPath,
            'input_hashes' => $inputHashes,
            'lock' => $lock,
        ];
        $planId = hash('sha256', CanonicalJson::encode($core));
        $plan = ['plan_id' => $planId] + $core;
        $planDirectory = $root . '/.docara/translation-plans';
        if (is_link($root . '/.docara') || is_link($planDirectory)) {
            throw new PortableConfigurationException('TRANSLATION_PLAN_PATH_UNSAFE', 'Translation plan directory cannot be a symlink.');
        }
        if (! is_dir($planDirectory) && ! mkdir($planDirectory, 0755, true) && ! is_dir($planDirectory)) {
            throw new PortableConfigurationException('TRANSLATION_PLAN_WRITE_FAILED', 'Translation plan directory could not be created.');
        }
        $this->writeAtomic($planDirectory . '/' . $planId . '.json', CanonicalJson::encodePretty($plan), false);

        return $plan;
    }

    /** @return array<string,mixed> */
    public function apply(string $root, string $planId): array
    {
        [$root] = $this->configuration($root);
        if (preg_match('/\A[a-f0-9]{64}\z/D', $planId) !== 1) {
            throw new PortableConfigurationException('TRANSLATION_PLAN_ID_INVALID', 'Apply requires the exact SHA-256 plan id returned by dry-run.');
        }
        $planPath = $root . '/.docara/translation-plans/' . $planId . '.json';
        $plan = json_decode($this->regularUtf8File($root, substr($planPath, strlen($root) + 1), 'TRANSLATION_PLAN_INVALID'), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($plan) || ($plan['plan_id'] ?? null) !== $planId) {
            throw new PortableConfigurationException('TRANSLATION_PLAN_INVALID', 'Translation plan is invalid.');
        }
        $core = $plan;
        unset($core['plan_id']);
        if (! hash_equals($planId, hash('sha256', CanonicalJson::encode($core)))) {
            throw new PortableConfigurationException('TRANSLATION_PLAN_HASH_MISMATCH', 'Translation plan contents do not match its plan id.');
        }
        foreach ($plan['input_hashes'] as $path => $expected) {
            $actualPath = $root . '/' . $path;
            $actual = is_file($actualPath) && ! is_link($actualPath) ? (hash_file('sha256', $actualPath) ?: 'absent') : 'absent';
            if (! hash_equals((string) $expected, $actual)) {
                throw new PortableConfigurationException('TRANSLATION_PLAN_STALE', "Input [$path] changed after dry-run.");
            }
        }
        (new SchemaRepository)->assertValid($plan['lock'], 'translation-lock.schema.json');
        $lockPath = $this->safeLockPath($root, (string) $plan['lock_file']);
        $this->writeAtomic($lockPath, CanonicalJson::encodePretty($plan['lock']), true);

        return ['schema' => 'docara.translation_accept_result.v1', 'status' => 'applied', 'plan_id' => $planId, 'lock_file' => $plan['lock_file']];
    }

    /** @return array{0:string,1:array<string,mixed>,2:array<string,mixed>} */
    private function configuration(string $root): array
    {
        $real = realpath($root);
        if ($real === false || ! is_dir($real) || is_link($root)) {
            throw new PortableConfigurationException('TRANSLATION_PROJECT_ROOT_INVALID', 'Project root must be a real directory and not a symlink.');
        }
        $root = FilesystemPath::normalize($real);
        try {
            $site = json_decode($this->regularUtf8File($root, 'docara.json', 'TRANSLATION_CONFIG_INVALID'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException('TRANSLATION_CONFIG_INVALID', 'docara.json is not valid JSON.', $exception);
        }
        (new SchemaRepository)->assertValid($site, 'site.schema.json');
        $tracking = is_array($site['translation_tracking'] ?? null)
            ? $site['translation_tracking']
            : ['enabled' => false, 'source_locale' => (string) ($site['default_locale'] ?? ''), 'mode' => 'report', 'lock_file' => 'translations.lock.json'];

        return [$root, $site, $tracking];
    }

    /** @return array<string,mixed> */
    private function lock(string $root, string $relative, string $sourceLocale): array
    {
        $path = $this->safeLockPath($root, $relative);
        if (! file_exists($path) && ! is_link($path)) {
            return ['schema' => 'docara.translation_lock.v1', 'source_locale' => $sourceLocale, 'entries' => [], 'exclusions' => []];
        }
        try {
            $lock = json_decode($this->regularUtf8File($root, $relative, 'TRANSLATION_LOCK_INVALID'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException('TRANSLATION_LOCK_INVALID', 'Translation lock is not valid JSON.', $exception);
        }
        (new SchemaRepository)->assertValid($lock, 'translation-lock.schema.json');
        if (($lock['source_locale'] ?? null) !== $sourceLocale) {
            throw new PortableConfigurationException('TRANSLATION_LOCK_SOURCE_LOCALE_MISMATCH', 'Translation lock source locale differs from docara.json.');
        }

        return $lock;
    }

    /** @return array<string,mixed> */
    private function sourceRecord(string $path, string $contents): array
    {
        $normalized = $this->normalizeForHash($contents);

        return [
            'path' => $path,
            'sha256' => hash('sha256', $normalized),
            'structure_sha256' => hash('sha256', CanonicalJson::encode($this->structure($normalized))),
        ];
    }

    /** @param array<string,mixed>|null $accepted */
    private function acceptedItem(string $kind, string $key, string $locale, array $source, array $target, ?array $accepted): array
    {
        if (! hash_equals($source['structure_sha256'], $target['structure_sha256'])) {
            return $this->item($kind, $key, $locale, 'structure_mismatch', $source, $target, $accepted['review'] ?? null);
        }
        if ($accepted === null || ! hash_equals((string) $accepted['target_sha256'], $target['sha256'])) {
            return $this->item($kind, $key, $locale, 'unverified', $source, $target, $accepted['review'] ?? null);
        }
        if (! hash_equals((string) $accepted['source_sha256'], $source['sha256'])) {
            return $this->item($kind, $key, $locale, 'stale', $source, $target, $accepted['review']);
        }

        return $this->item($kind, $key, $locale, 'current', $source, $target, $accepted['review']);
    }

    private function item(
        string $kind,
        string $key,
        string $locale,
        string $status,
        ?array $source,
        ?array $target,
        ?string $review = null,
        ?string $reason = null,
        ?string $fallbackLocale = null,
    ): array {
        return [
            'kind' => $kind,
            'key' => $key,
            'locale' => $locale,
            'status' => $status,
            'source_path' => $source['path'] ?? null,
            'target_path' => $target['path'] ?? null,
            'source_sha256' => $source['sha256'] ?? null,
            'target_sha256' => $target['sha256'] ?? null,
            'source_structure_sha256' => $source['structure_sha256'] ?? null,
            'target_structure_sha256' => $target['structure_sha256'] ?? null,
            'review' => $review,
            'reason' => $reason,
            'fallback_locale' => $fallbackLocale,
        ];
    }

    /** @param array<string,array<string,mixed>> $accepted @param array<string,array<string,mixed>> $excluded @return list<array<string,mixed>> */
    private function languageItems(string $root, LocaleRegistry $registry, string $sourceLocale, ?string $locale, array $accepted, array $excluded): array
    {
        $packs = [];
        foreach ($registry->all() as $localeCode => $definition) {
            $path = rtrim($definition->contentRoot, '/') . '/lang.json';
            $decoded = json_decode($this->regularUtf8File($root, $path, 'TRANSLATION_LANG_INVALID'), true, 512, JSON_THROW_ON_ERROR);
            $packs[$localeCode] = $this->flatten($decoded, $path);
        }
        $items = [];
        foreach ($registry->all() as $targetLocale => $definition) {
            if ($targetLocale === $sourceLocale || ($locale !== null && $targetLocale !== $locale)) {
                continue;
            }
            $keys = array_values(array_unique([...array_keys($packs[$sourceLocale]), ...array_keys($packs[$targetLocale])]));
            sort($keys, SORT_STRING);
            foreach ($keys as $key) {
                $source = $packs[$sourceLocale][$key] ?? null;
                $target = $packs[$targetLocale][$key] ?? null;
                $identity = 'lang' . "\0" . $key . "\0" . $targetLocale;
                if ($source === null) {
                    $items[] = $this->item('lang', $key, $targetLocale, 'orphan', null, $target);
                } elseif (isset($excluded[$identity])) {
                    $items[] = $this->item('lang', $key, $targetLocale, 'excluded', $source, $target, null, $excluded[$identity]['reason']);
                } elseif ($target === null || $target['value'] === '') {
                    $items[] = $this->item(
                        'lang',
                        $key,
                        $targetLocale,
                        'missing',
                        $source,
                        null,
                        fallbackLocale: $this->languageFallbackLocale($definition->fallbacks, $packs, $key),
                    );
                } else {
                    $items[] = $this->acceptedItem('lang', $key, $targetLocale, $source, $target, $accepted[$identity] ?? null);
                }
            }
        }

        return $items;
    }

    /** @return array<string,array<string,mixed>> */
    private function flatten(mixed $value, string $path, string $prefix = ''): array
    {
        if (! is_array($value)) {
            throw new PortableConfigurationException('TRANSLATION_LANG_INVALID', "Language pack [$path] must be an object.");
        }
        $result = [];
        foreach ($value as $key => $item) {
            if ($prefix === '' && in_array($key, ['schema', 'version'], true)) {
                continue;
            }
            $full = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($item)) {
                $result += $this->flatten($item, $path, $full);

                continue;
            }
            if (! is_string($item)) {
                throw new PortableConfigurationException('TRANSLATION_LANG_INVALID', "Language key [$full] must be a string.");
            }
            $normalized = $this->normalize($item);
            $result[$full] = [
                'path' => $path . '#/' . str_replace('.', '/', $full),
                'value' => $item,
                'sha256' => hash('sha256', $normalized),
                'structure_sha256' => hash('sha256', 'lang-string'),
            ];
        }

        return $result;
    }

    /** @return array<string,mixed> */
    private function finalize(array $report): array
    {
        $summary = array_fill_keys(['current', 'stale', 'missing', 'unverified', 'orphan', 'duplicate_key', 'structure_mismatch', 'excluded'], 0);
        foreach ($report['items'] as $item) {
            $summary[$item['status']]++;
        }
        $report['summary'] = $summary + ['total' => count($report['items'])];
        $report['content_sha256'] = hash('sha256', CanonicalJson::encode($report));
        (new SchemaRepository)->assertValid($report, 'translation-status.schema.json');

        return $report;
    }

    /** @return array<string,mixed> */
    private function structure(string $markdown): array
    {
        preg_match_all('/^(#{1,6})\h+/m', $markdown, $headings);
        preg_match_all('/^:::(?<name>[a-z][a-z0-9_.-]*)(?:\h+\{(?<attrs>[^}]*)})?/m', $markdown, $directives, PREG_SET_ORDER);
        preg_match_all('/(?<fence>`{3,}|~{3,})(?<lang>[A-Za-z0-9_-]*)\h*\n(?<code>.*?)\n\k<fence>/s', $markdown, $code, PREG_SET_ORDER);
        preg_match_all('/!?\[[^]]*]\((?<url>[^)\h]+)(?:\h+"[^"]*")?\)/u', $markdown, $links);
        $directiveRecords = [];
        foreach ($directives as $directive) {
            $attrs = preg_replace('/\b(?:label|title|alt|caption|text)=(?:"[^"]*"|[^\s}]+)/u', '', (string) ($directive['attrs'] ?? ''));
            $directiveRecords[] = [$directive['name'], trim((string) $attrs)];
        }

        return [
            'headings' => array_map('strlen', $headings[1] ?? []),
            'directives' => $directiveRecords,
            'code' => array_map(static fn (array $match): array => [strtolower($match['lang']), $match['code']], $code),
            'links' => $links['url'] ?? [],
            'tables' => preg_match_all('/^\|.*\|\h*$/m', $markdown),
            'unordered_lists' => preg_match_all('/^\h*[-+*]\h+/m', $markdown),
            'ordered_lists' => preg_match_all('/^\h*\d+[.)]\h+/m', $markdown),
        ];
    }

    private function normalize(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        return str_replace(["\r\n", "\r"], "\n", $contents);
    }

    private function normalizeForHash(string $contents): string
    {
        $contents = $this->normalize($contents);
        if (! str_starts_with($contents, "---\n")) {
            return $contents;
        }
        $closing = strpos($contents, "\n---\n", 4);
        if ($closing === false) {
            return $contents;
        }
        $frontMatter = substr($contents, 4, $closing - 4);
        $frontMatter = preg_replace('/^translation_[A-Za-z0-9_-]+\h*:.*(?:\n|$)/m', '', $frontMatter) ?? $frontMatter;

        return "---\n" . $frontMatter . "---\n" . substr($contents, $closing + 5);
    }

    /** @param list<string> $fallbacks @param array<string,array<string,list<array<string,mixed>>>> $pages */
    private function pageFallbackLocale(array $fallbacks, array $pages, string $key): ?string
    {
        foreach ($fallbacks as $fallback) {
            if (count($pages[$fallback][$key] ?? []) === 1) {
                return $fallback;
            }
        }

        return null;
    }

    /** @param list<string> $fallbacks @param array<string,array<string,array<string,mixed>>> $packs */
    private function languageFallbackLocale(array $fallbacks, array $packs, string $key): ?string
    {
        foreach ($fallbacks as $fallback) {
            if (($packs[$fallback][$key]['value'] ?? '') !== '') {
                return $fallback;
            }
        }

        return null;
    }

    private function regularUtf8File(string $root, string $relative, string $code): string
    {
        if ($relative === '' || str_starts_with($relative, '/') || str_contains('/' . str_replace('\\', '/', $relative) . '/', '/../')) {
            throw new PortableConfigurationException($code, "Path [$relative] is unsafe.");
        }
        $path = $root . '/' . $relative;
        $stat = @lstat($path);
        $real = realpath($path);
        if (! is_array($stat) || $real === false || is_link($path) || ! is_file($real)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000 || ($stat['nlink'] ?? 1) !== 1
            || ! FilesystemPath::isWithin(FilesystemPath::normalize($real), $root)
        ) {
            throw new PortableConfigurationException($code, "File [$relative] is missing or unsafe.");
        }
        $contents = file_get_contents($real);
        if (! is_string($contents) || preg_match('//u', $contents) !== 1) {
            throw new PortableConfigurationException($code, "File [$relative] must be valid UTF-8.");
        }

        return $contents;
    }

    private function safeLockPath(string $root, string $relative): string
    {
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.json\z/D', $relative) !== 1 || $relative === 'docara.json') {
            throw new PortableConfigurationException('TRANSLATION_LOCK_PATH_INVALID', 'translation_tracking.lock_file must be a root JSON filename.');
        }

        return $root . '/' . $relative;
    }

    /** @param array<string,mixed> $item @return array<string,string> */
    private function inputHashes(string $root, string $lockPath, array $item): array
    {
        $paths = ['docara.json', $lockPath];
        foreach (['source_path', 'target_path'] as $field) {
            if (is_string($item[$field] ?? null)) {
                $paths[] = explode('#', $item[$field], 2)[0];
            }
        }
        $paths = array_values(array_unique($paths));
        sort($paths, SORT_STRING);
        $hashes = [];
        foreach ($paths as $path) {
            $absolute = $root . '/' . $path;
            $hashes[$path] = is_file($absolute) && ! is_link($absolute) ? (hash_file('sha256', $absolute) ?: 'absent') : 'absent';
        }

        return $hashes;
    }

    private function writeAtomic(string $path, string $contents, bool $replace): void
    {
        if (file_exists($path) || is_link($path)) {
            $stat = @lstat($path);
            if (! $replace && is_file($path) && ! is_link($path)
                && is_array($stat) && ($stat['nlink'] ?? 1) === 1
                && hash_equals(hash('sha256', (string) file_get_contents($path)), hash('sha256', $contents))
            ) {
                return;
            }
            if (! $replace || ! is_array($stat) || is_link($path) || ! is_file($path) || ($stat['nlink'] ?? 1) !== 1) {
                throw new PortableConfigurationException('TRANSLATION_WRITE_COLLISION', "Target [$path] exists or is unsafe.");
            }
        }
        $temporary = dirname($path) . '/.docara-translation-' . bin2hex(random_bytes(12));
        if (file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents)) {
            @unlink($temporary);
            throw new PortableConfigurationException('TRANSLATION_WRITE_FAILED', "Target [$path] could not be written.");
        }
        if (! @rename($temporary, $path)) {
            @unlink($temporary);
            throw new PortableConfigurationException('TRANSLATION_WRITE_FAILED', "Target [$path] could not be published atomically.");
        }
    }
}
