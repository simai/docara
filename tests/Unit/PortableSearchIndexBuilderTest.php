<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\PortableSite\PortableSearchIndexBuilder;
use Simai\Docara\PortableSite\PortableSearchTextExtractor;

final class PortableSearchIndexBuilderTest extends TestCase
{
    private string $runtime;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runtime = tempnam(sys_get_temp_dir(), 'docara-search-runtime-');
        file_put_contents($this->runtime, '(function(){"use strict";}());');
    }

    protected function tearDown(): void
    {
        @unlink($this->runtime);
        parent::tearDown();
    }

    #[Test]
    public function it_builds_a_canonical_locale_isolated_index_and_framework_urls(): void
    {
        $pages = [
            $this->page('/project/guides/setup/', 'ru', 'Установка', '<h1>Установка</h1><p>Все ёлки зелёные.</p>'),
            $this->page('/project/hidden/', 'ru', 'Скрытая в меню', '<h1>Скрытая</h1><p>Но доступна поиску.</p>'),
            $this->page('/project/en/start/', 'en', 'Start', '<h1>Start</h1><p>Local search.</p>'),
        ];
        $navigation = [[
            'title' => 'Руководства',
            'url' => '/project/guides/',
            'children' => [[
                'title' => 'Установка',
                'url' => '/project/guides/setup/',
                'children' => [],
            ]],
        ]];

        $plan = $this->builder()->plan($pages, $navigation, '/project/');

        self::assertSame('docara.search_index.v1', $plan->index['schema']);
        self::assertSame('docara-prefix-v1', $plan->index['algorithm']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $plan->contentHash);
        self::assertSame(hash_file('sha256', $this->runtime), $plan->runtimeHash);
        self::assertSame(
            ['/project/en/start/', '/project/guides/setup/', '/project/hidden/'],
            array_column($plan->index['documents'], 'url'),
        );
        self::assertSame(['Руководства'], $plan->index['documents'][1]['trail']);
        self::assertSame([], $plan->index['documents'][2]['trail']);
        self::assertSame(
            '/project/_docara/search-index.json?docara_v=' . $plan->contentHash,
            $plan->indexUrl,
        );
        self::assertSame(
            '/project/_docara/search.js?docara_v=' . $plan->runtimeHash,
            $plan->runtimeUrl,
        );
        self::assertSame($plan->indexJson, $this->builder()->plan($pages, $navigation, '/project/')->indexJson);
        self::assertSame(
            hash('sha256', CanonicalJson::encode($plan->index['documents'])),
            $plan->contentHash,
        );
        self::assertSame(
            $plan->indexJson,
            $this->builder()->plan(array_reverse($pages), $navigation, '/project/')->indexJson,
        );
        (new SchemaRepository)->assertValid($plan->index, 'search-index.schema.json');
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function runtime_cache_revision_changes_independently_from_index_content(): void
    {
        $pages = [$this->page('/start/', 'ru', 'Старт', '<p>Старт</p>')];
        $first = $this->builder()->plan($pages, [], '/');

        file_put_contents($this->runtime, '(function(){"use strict";var revision=2;}());');
        $second = $this->builder()->plan($pages, [], '/');

        self::assertSame($first->contentHash, $second->contentHash);
        self::assertSame($first->indexUrl, $second->indexUrl);
        self::assertNotSame($first->runtimeHash, $second->runtimeHash);
        self::assertNotSame($first->runtimeUrl, $second->runtimeUrl);
    }

    #[Test]
    public function it_excludes_indexed_false_and_rejects_an_enabled_empty_locale(): void
    {
        $excluded = $this->page('/ru/private/', 'ru', 'Private', '<p>Private</p>');
        $excluded['search_indexed'] = false;
        $english = $this->page('/en/start/', 'en', 'Start', '<p>Start</p>');
        $english['search_enabled'] = false;

        try {
            $this->builder()->plan([$excluded, $english], [], '/');
            self::fail('An enabled locale without indexed pages unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SEARCH_INDEX_LOCALE_EMPTY', $exception->errorCode);
        }
    }

    #[Test]
    public function it_omits_an_excluded_page_from_an_otherwise_valid_locale_index(): void
    {
        $included = $this->page('/ru/start/', 'ru', 'Старт', '<p>Публичный текст</p>');
        $excluded = $this->page('/ru/private/', 'ru', 'Служебная', '<p>Не индексировать</p>');
        $excluded['search_indexed'] = false;

        $plan = $this->builder()->plan([$excluded, $included], [], '/');

        self::assertSame(['/ru/start/'], array_column($plan->index['documents'], 'url'));
        self::assertStringNotContainsString('Не индексировать', $plan->indexJson);
    }

    #[Test]
    public function it_rejects_a_missing_or_non_utf8_pinned_runtime(): void
    {
        $page = $this->page('/start/', 'ru', 'Старт', '<p>Старт</p>');

        $missing = new PortableSearchIndexBuilder(
            new PortableSearchTextExtractor,
            new SchemaRepository,
            $this->runtime . '-missing',
        );
        try {
            $missing->plan([$page], [], '/');
            self::fail('A missing search runtime unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SEARCH_RUNTIME_MISSING', $exception->errorCode);
        }

        file_put_contents($this->runtime, "\xFF");
        try {
            $this->builder()->plan([$page], [], '/');
            self::fail('A non-UTF-8 search runtime unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SEARCH_RUNTIME_INVALID_UTF8', $exception->errorCode);
        }
    }

    #[Test]
    public function it_rejects_unsafe_document_urls_in_the_generated_index(): void
    {
        foreach (['//evil.example/', '/\\evil.com/', '/project/%2e%2e/evil/', '/../', '/./', '/page/?q=1', '/page/#part', '/project/page'] as $url) {
            try {
                $this->builder()->plan([$this->page($url, 'ru', 'Старт', '<p>Старт</p>')], [], '/');
                self::fail("Unsafe search document URL [$url] unexpectedly passed.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function it_rejects_a_document_outside_the_deployment_base(): void
    {
        try {
            $this->builder()->plan(
                [$this->page('/outside/', 'ru', 'Вне базы', '<p>Текст</p>')],
                [],
                '/project/',
            );
            self::fail('A search document outside deployment base unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('SEARCH_DOCUMENT_OUTSIDE_BASE', $exception->errorCode);
        }
    }

    /** @return array<string, mixed> */
    private function page(string $url, string $locale, string $title, string $html): array
    {
        return [
            'url' => $url,
            'locale' => $locale,
            'title' => $title,
            'description' => '',
            'content_html' => $html,
            'component_calls' => [],
            'search_enabled' => true,
            'search_indexed' => true,
        ];
    }

    private function builder(): PortableSearchIndexBuilder
    {
        return new PortableSearchIndexBuilder(
            new PortableSearchTextExtractor,
            new SchemaRepository,
            $this->runtime,
        );
    }
}
