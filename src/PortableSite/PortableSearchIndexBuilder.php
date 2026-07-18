<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class PortableSearchIndexBuilder
{
    public function __construct(
        private PortableSearchTextExtractor $extractor = new PortableSearchTextExtractor,
        private SchemaRepository $schemas = new SchemaRepository,
        private string $runtimePath = __DIR__ . '/../../resources/portable/search.js',
    ) {}

    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  list<array<string, mixed>>  $navigation
     */
    public function plan(array $pages, array $navigation, string $baseUrl): PortableSearchPlan
    {
        $runtime = @file_get_contents($this->runtimePath);
        if (! is_string($runtime) || $runtime === '') {
            throw new PortableConfigurationException(
                'SEARCH_RUNTIME_MISSING',
                'The pinned Docara search runtime is missing or unreadable.',
            );
        }
        if (preg_match('//u', $runtime) !== 1) {
            throw new PortableConfigurationException(
                'SEARCH_RUNTIME_INVALID_UTF8',
                'The pinned Docara search runtime must be valid UTF-8.',
            );
        }

        $enabledLocales = [];
        $documents = [];
        $navigationBuilder = new PortableNavigationBuilder;
        foreach ($pages as $page) {
            $locale = (string) ($page['locale'] ?? '');
            if (($page['search_enabled'] ?? false) === true) {
                $enabledLocales[$locale] = true;
            }
            if (($page['search_indexed'] ?? true) !== true) {
                continue;
            }
            $url = (string) ($page['url'] ?? '');
            $extracted = $this->extractor->extract(
                (string) ($page['content_html'] ?? ''),
                is_array($page['component_calls'] ?? null) ? $page['component_calls'] : [],
            );
            $path = $navigationBuilder->pathForUrl($navigation, $url);
            $trail = array_column(array_slice($path, 0, -1), 'title');
            $documents[] = [
                'id' => hash('sha256', $locale . "\0" . $url),
                'url' => $url,
                'locale' => $locale,
                'title' => (string) ($page['title'] ?? ''),
                'description' => (string) ($page['description'] ?? ''),
                'trail' => array_values(array_map('strval', $trail)),
                'headings' => $extracted['headings'],
                'text' => $extracted['text'],
            ];
        }
        usort($documents, static fn (array $left, array $right): int => [
            $left['locale'],
            $left['url'],
        ] <=> [
            $right['locale'],
            $right['url'],
        ]);

        $indexedLocales = array_fill_keys(array_column($documents, 'locale'), true);
        foreach (array_keys($enabledLocales) as $locale) {
            if (! isset($indexedLocales[$locale])) {
                throw new PortableConfigurationException(
                    'SEARCH_INDEX_LOCALE_EMPTY',
                    "Search is enabled for locale [$locale], but it has no indexed page.",
                );
            }
        }

        $contentHash = hash('sha256', CanonicalJson::encode($documents));
        $index = [
            'schema' => 'docara.search_index.v1',
            'version' => 1,
            'algorithm' => 'docara-prefix-v1',
            'content_sha256' => $contentHash,
            'documents' => $documents,
        ];
        $this->schemas->assertValid($index, 'search-index.schema.json');
        $prefix = rtrim($baseUrl, '/');
        $runtimeHash = hash('sha256', $runtime);

        return new PortableSearchPlan(
            $index,
            CanonicalJson::encodePretty($index),
            $runtime,
            $contentHash,
            $runtimeHash,
            $prefix . '/_docara/search-index.json?docara_v=' . $contentHash,
            $prefix . '/_docara/search.js?docara_v=' . $runtimeHash,
        );
    }
}
