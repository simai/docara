<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\I18n\UiCopy;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\ComponentReferenceRepository;
use Simai\Docara\PortableSite\PortableComponentReferenceHydrator;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Tests\TestCase;

final class PortableComponentReferenceTest extends TestCase
{
    private const DOCS_SITE = __DIR__ . '/../../docs/site';

    #[Test]
    public function the_block_leaves_a_marker_for_the_build(): void
    {
        $html = (new PortableMarkdownRenderer)->render(":::component_reference {component=sf-view-table sections=events,methods}\n:::\n");

        self::assertSame(
            '<div data-docara-block="component-reference" data-docara-component-reference data-component="sf-view-table" data-sections="events,methods"></div>',
            trim($html),
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidBlocks(): iterable
    {
        yield 'no component' => [":::component_reference\n:::\n", 'MARKDOWN_COMPONENT_REFERENCE_COMPONENT_INVALID'];
        yield 'not a tag' => [":::component_reference {component=Table}\n:::\n", 'MARKDOWN_COMPONENT_REFERENCE_COMPONENT_INVALID'];
        yield 'no hyphen' => [":::component_reference {component=table}\n:::\n", 'MARKDOWN_COMPONENT_REFERENCE_COMPONENT_INVALID'];
        yield 'unknown section' => [":::component_reference {component=sf-x sections=slots}\n:::\n", 'MARKDOWN_COMPONENT_REFERENCE_SECTIONS_INVALID'];
        yield 'spaces in sections' => [":::component_reference {component=sf-x sections=\"events, methods\"}\n:::\n", 'MARKDOWN_COMPONENT_REFERENCE_SECTIONS_INVALID'];
        yield 'repeated section' => [":::component_reference {component=sf-x sections=events,events}\n:::\n", 'MARKDOWN_COMPONENT_REFERENCE_SECTIONS_INVALID'];
        yield 'unknown attribute' => [":::component_reference {component=sf-x tag=sf-x}\n:::\n", 'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN'];
    }

    #[Test]
    #[DataProvider('invalidBlocks')]
    public function an_invalid_block_stops_the_page(string $markdown, string $code): void
    {
        $this->assertFailsWith($code, fn () => (new PortableMarkdownRenderer)->render($markdown));
    }

    #[Test]
    public function the_reference_is_written_from_the_manifests_in_english_without_a_dictionary(): void
    {
        $html = $this->hydrate('en', ':::component_reference {component=demo-counter}');

        self::assertStringContainsString('<dt>Tag</dt>', $html);
        self::assertStringContainsString('<code>&lt;demo-counter&gt;</code>', $html);
        self::assertStringContainsString('<code>demo.counter</code>, version <code>1.0.0</code>', $html);
        self::assertStringContainsString('<h3>Attributes</h3>', $html);
        self::assertStringContainsString('<table class="table table-stripe">', $html);
        self::assertStringContainsString('<td><code>label</code> (required)</td>', $html);
        self::assertStringContainsString('<td><code>1/2</code>, <code>1</code>, <code>2</code></td>', $html);
        self::assertStringContainsString('<td>empty</td>', $html);
        self::assertStringContainsString('<code>{ value: number, previous?: number }</code>', $html);
        self::assertStringContainsString('<code>demo-counter-input</code> → <code>demo-counter-change</code>', $html);
        self::assertStringContainsString('<code>increment(amount?: number)</code>', $html);
        // Generated text that only repeats the name, and the unknown result
        // of an unannotated method, are left out.
        self::assertStringNotContainsString('Public runtime method', $html);
        self::assertStringNotContainsString('<code>object</code> | <code>boolean</code>', $html);
        self::assertStringContainsString('<code>--demo-counter-gap</code>', $html);
        self::assertStringContainsString('<dd><code>ArrowUp</code></dd>', $html);
        self::assertStringContainsString('<dd>Home sets the value to zero</dd>', $html);
        self::assertStringNotContainsString('data-docara-component-reference', $html);
    }

    #[Test]
    public function an_example_shows_the_reference_inline_and_in_its_responsive_frame(): void
    {
        $html = $this->hydrate('en', ":::example {label=\"Reference\"}\n```markdown\n:::component_reference {component=demo-counter sections=css}\n:::\n```", complete: false);

        self::assertStringNotContainsString('data-docara-component-reference', $html);
        self::assertSame(2, substr_count($html, '--demo-counter-gap'), 'once inline, once in the frame document');
        self::assertStringContainsString('&lt;table class=&quot;table table-stripe&quot;&gt;', $html);
    }

    #[Test]
    public function a_locale_dictionary_translates_every_shown_string(): void
    {
        $html = $this->hydrate('ru', ':::component_reference {component=demo-counter}');

        self::assertStringContainsString('<dt>Тег</dt>', $html);
        self::assertStringContainsString('<h3>События</h3>', $html);
        self::assertStringContainsString('Текущее значение.', $html);
        self::assertStringContainsString('Расстояние между полем и кнопками.', $html);
        self::assertStringContainsString('<dd>Home обнуляет значение</dd>', $html);
        self::assertStringNotContainsString('The current value.', $html);
    }

    #[Test]
    public function sections_choose_parts_and_a_single_part_has_no_heading(): void
    {
        $html = $this->hydrate('en', ':::component_reference {component=demo-counter sections=events}', complete: false);

        self::assertStringContainsString('<code>demo-counter-change</code>', $html);
        self::assertStringNotContainsString('<h3>', $html);
        self::assertStringNotContainsString('<dt>Tag</dt>', $html);
        self::assertStringNotContainsString('<code>label</code>', $html);

        $two = $this->hydrate('en', ':::component_reference {component=demo-counter sections=methods,events}', complete: false);
        self::assertLessThan(strpos($two, '<h3>Events</h3>'), strpos($two, '<h3>Methods</h3>'));
    }

    #[Test]
    public function site_lang_json_replaces_the_labels(): void
    {
        $copy = array_replace(UiCopy::buildDefaults('en'), ['reference.section_events' => 'Emitted events']);
        $markdown = $this->hydrator($this->project('en'))->markdownFor('demo-counter', ['attributes', 'events'], $copy, 'en');

        self::assertStringContainsString('### Emitted events', $markdown);
    }

    #[Test]
    public function a_part_the_manifest_does_not_declare_says_so(): void
    {
        $this->createSource([
            'components/bare-thing/smart.manifest.json' => json_encode(['custom_element' => 'bare-thing', 'id' => 'demo.bare', 'version' => '1.0.0']),
            'content/en/index.md' => "# Home\n",
        ]);
        $repository = new ComponentReferenceRepository($this->tmp, ['en' => 'content/en']);
        $markdown = (new PortableComponentReferenceHydrator($repository, new PortableMarkdownRenderer))
            ->markdownFor('bare-thing', PortableComponentReferenceHydrator::SECTIONS, UiCopy::buildDefaults('en'), 'en');

        self::assertSame(5, substr_count($markdown, 'Not declared in the manifest.'));
    }

    #[Test]
    public function a_missing_manifest_stops_the_build(): void
    {
        $this->createSource(['content/en/index.md' => "# Home\n"]);
        $repository = new ComponentReferenceRepository($this->tmp, ['en' => 'content/en']);

        $this->assertFailsWith(
            'COMPONENT_REFERENCE_MANIFEST_MISSING',
            fn () => $repository->component('sf-view-table'),
            'components/view-table/smart.manifest.json',
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidManifests(): iterable
    {
        yield 'not json' => ['smart.manifest.json', '{"custom_element":'];
        yield 'a list' => ['smart.manifest.json', '[1, 2]'];
        yield 'another tag' => ['smart.manifest.json', '{"custom_element": "sf-other"}'];
        yield 'events not an object' => ['smart.manifest.json', '{"custom_element": "sf-view-table", "events": "none"}'];
        yield 'css variables not an object' => ['component.json', '{"css-variables": "none"}'];
    }

    #[Test]
    #[DataProvider('invalidManifests')]
    public function a_malformed_manifest_stops_the_build(string $file, string $contents): void
    {
        $files = ['components/view-table/smart.manifest.json' => '{"custom_element": "sf-view-table"}'];
        $files['components/view-table/' . $file] = $contents;
        $this->createSource($files);
        $repository = new ComponentReferenceRepository($this->tmp, ['en' => 'content/en']);

        $this->assertFailsWith('COMPONENT_REFERENCE_MANIFEST_INVALID', fn () => $repository->component('sf-view-table'));
    }

    #[Test]
    public function a_linked_manifest_folder_is_refused(): void
    {
        $this->createSource(['elsewhere/smart.manifest.json' => '{"custom_element": "sf-view-table"}']);
        $this->filesystem->ensureDirectoryExists($this->tmpPath('components'));
        symlink($this->tmpPath('elsewhere'), $this->tmpPath('components/view-table'));
        $repository = new ComponentReferenceRepository($this->tmp, ['en' => 'content/en']);

        $this->assertFailsWith('COMPONENT_REFERENCE_MANIFEST_INVALID', fn () => $repository->component('sf-view-table'));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidDictionaries(): iterable
    {
        yield 'not json' => ['{"schema":'];
        yield 'wrong schema' => ['{"schema": "ui-view.reference-dictionary", "strings": {}}'];
        yield 'no strings' => ['{"schema": "docara.component_reference.v1"}'];
        yield 'extra key' => ['{"schema": "docara.component_reference.v1", "strings": {}, "locale": "ru"}'];
        yield 'empty translation' => ['{"schema": "docara.component_reference.v1", "strings": {"The current value.": " "}}'];
    }

    #[Test]
    #[DataProvider('invalidDictionaries')]
    public function a_malformed_dictionary_stops_the_build(string $contents): void
    {
        $this->createSource(['content/ru/component-reference.json' => $contents]);
        $repository = new ComponentReferenceRepository($this->tmp, ['ru' => 'content/ru']);

        $this->assertFailsWith('COMPONENT_REFERENCE_DICTIONARY_INVALID', fn () => $repository->dictionary('ru'));
    }

    #[Test]
    public function a_string_without_translation_is_drift(): void
    {
        $project = $this->project('ru', static function (array $strings): array {
            unset($strings['The current value.'], $strings['css:--demo-counter-gap']);

            return $strings;
        });

        $this->assertFailsWith(
            'COMPONENT_REFERENCE_TRANSLATION_MISSING',
            fn () => $this->hydrate('ru', ':::component_reference {component=demo-counter}', $project),
            'has no translation for 2 manifest string(s)',
        );
    }

    #[Test]
    public function a_translation_no_block_shows_is_stale_on_a_full_build_only(): void
    {
        $project = $this->project('ru', static fn (array $strings): array => $strings + ['A sentence the manifest lost.' => 'Предложение, которого нет.']);

        // One-page rebuild: it sees one page, so it cannot call a string stale.
        $html = $this->hydrate('ru', ':::component_reference {component=demo-counter}', $project, complete: false);
        self::assertStringContainsString('Текущее значение.', $html);

        $this->assertFailsWith(
            'COMPONENT_REFERENCE_TRANSLATION_STALE',
            fn () => $this->hydrate('ru', ':::component_reference {component=demo-counter}', $project),
            'A sentence the manifest lost.',
        );
    }

    #[Test]
    public function a_section_subset_makes_the_other_translations_stale(): void
    {
        $this->assertFailsWith(
            'COMPONENT_REFERENCE_TRANSLATION_STALE',
            fn () => $this->hydrate('ru', ':::component_reference {component=demo-counter sections=events}'),
        );
    }

    #[Test]
    public function manifest_text_cannot_inject_markup(): void
    {
        $this->createSource([
            'components/x-probe/smart.manifest.json' => json_encode([
                'custom_element' => 'x-probe',
                'summary' => 'A <script>alert(1)</script> [link](javascript:alert(1)) with `code` and a | pipe',
                'inputs' => ['properties' => ['mode' => ['type' => 'string', 'enum' => ['a|b'], 'description' => "Two\nlines *not bold*"]]],
            ]),
        ]);
        $repository = new ComponentReferenceRepository($this->tmp, ['en' => 'content/en']);
        $hydrator = new PortableComponentReferenceHydrator($repository, new PortableMarkdownRenderer);
        $pages = $hydrator->hydrate([[
            'locale' => 'en',
            'content_html' => '<div data-docara-block="component-reference" data-docara-component-reference data-component="x-probe"></div>',
        ]]);
        $html = (string) $pages[0]['content_html'];

        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('<a ', $html);
        self::assertStringNotContainsString('<em>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringContainsString('<code>code</code> and a | pipe', $html);
        self::assertStringContainsString('<code>a|b</code>', $html);
        self::assertStringContainsString('Two lines *not bold*', $html);
    }

    #[Test]
    public function the_docs_site_dictionary_matches_its_demo_manifest(): void
    {
        $repository = new ComponentReferenceRepository(self::DOCS_SITE, ['ru' => 'content/ru', 'en' => 'content/en']);
        $renderer = new PortableMarkdownRenderer;
        $pages = [];
        foreach (['ru', 'en'] as $locale) {
            $source = (string) file_get_contents(self::DOCS_SITE . "/content/$locale/components/component-reference.md");
            self::assertStringContainsString(':::component_reference {component=demo-counter}', $source);
            $pages[] = [
                'locale' => $locale,
                'content_html' => $renderer->render(":::component_reference {component=demo-counter}\n:::\n\n:::component_reference {component=demo-counter sections=events}\n:::\n"),
            ];
        }
        $pages = (new PortableComponentReferenceHydrator($repository, $renderer))->hydrate($pages);

        self::assertStringContainsString('Текущее значение.', (string) $pages[0]['content_html']);
        self::assertStringContainsString('The current value.', (string) $pages[1]['content_html']);
    }

    private function hydrate(string $locale, string $block, ?string $project = null, bool $complete = true): string
    {
        $project ??= $this->project($locale);
        $renderer = new PortableMarkdownRenderer;
        $pages = $this->hydrator($project, $renderer)->hydrate([[
            'locale' => $locale,
            'content_html' => $renderer->render($block . "\n:::\n"),
        ]], $complete);

        return (string) $pages[0]['content_html'];
    }

    private function hydrator(string $project, ?PortableMarkdownRenderer $renderer = null): PortableComponentReferenceHydrator
    {
        return new PortableComponentReferenceHydrator(
            new ComponentReferenceRepository($project, ['en' => 'content/en', 'ru' => 'content/ru']),
            $renderer ?? new PortableMarkdownRenderer,
        );
    }

    /**
     * A project with the docs site's demo manifest and, for ru, its
     * dictionary, optionally changed.
     *
     * @param  (callable(array<string,string>): array<string,string>)|null  $strings
     */
    private function project(string $locale, ?callable $strings = null): string
    {
        $files = [];
        foreach (['smart.manifest.json', 'component.json'] as $name) {
            $files['components/demo-counter/' . $name] = (string) file_get_contents(self::DOCS_SITE . '/components/demo-counter/' . $name);
        }
        if ($locale === 'ru') {
            $dictionary = json_decode((string) file_get_contents(self::DOCS_SITE . '/content/ru/component-reference.json'), true, 512, JSON_THROW_ON_ERROR);
            if ($strings !== null) {
                $dictionary['strings'] = $strings($dictionary['strings']);
            }
            $files['content/ru/component-reference.json'] = json_encode($dictionary, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        $this->createSource($files);

        return $this->tmp;
    }

    private function assertFailsWith(string $code, callable $action, ?string $message = null): void
    {
        try {
            $action();
        } catch (PortableConfigurationException $exception) {
            self::assertSame($code, $exception->errorCode, $exception->getMessage());
            if ($message !== null) {
                self::assertStringContainsString($message, $exception->getMessage());
            }

            return;
        }
        self::fail("Expected [$code].");
    }
}
