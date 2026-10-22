<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSchemaReferenceHydrator;
use Simai\Docara\PortableSite\PortableSchemaReferenceProjector;
use Tests\TestCase;

final class PortableSchemaReferenceProjectorTest extends TestCase
{
    #[Test]
    public function schema_reference_is_exhaustive_deterministic_and_provenance_bound(): void
    {
        $projector = new PortableSchemaReferenceProjector;
        $first = $projector->project('site', 'site');
        $second = $projector->project('site', 'site');
        self::assertSame($first, $second);
        $paths = array_column($first, 'path');
        foreach (['/framework_lock', '/locales', '/locale_routing', '/branding', '/layout', '/settings', '/reader_preferences', '/search', '/reading', '/smart'] as $path) {
            self::assertContains($path, $paths);
        }
        foreach ($first as $record) {
            self::assertSame('site', $record['scope']);
            self::assertArrayHasKey('has_default', $record);
            self::assertNotSame('', $record['validation']);
            self::assertMatchesRegularExpression('~^resources/schemas/[a-z0-9.-]+\.schema\.json#~', $record['provenance']);
        }
        $receipt = $projector->receipt();
        self::assertSame('docara.public_schema_reference.v1', $receipt['schema']);
        self::assertCount(5, $receipt['sources']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $receipt['content_sha256']);
    }

    #[Test]
    public function the_schema_reference_table_speaks_the_page_language(): void
    {
        $placeholder = (new PortableMarkdownRenderer)->render(":::schema_reference {schema=page scope=page}\n:::\n");
        $pages = (new PortableSchemaReferenceHydrator)->hydrate([
            ['locale' => 'en', 'content_html' => $placeholder],
            ['locale' => 'ru', 'content_html' => $placeholder],
        ]);

        self::assertStringContainsString('<th>Field</th><th>What it does</th></tr>', $pages[0]['content_html']);
        self::assertDoesNotMatchRegularExpression('/[А-Яа-яЁё]/u', $pages[0]['content_html']);
        self::assertStringContainsString('<th>Поле</th><th>Что задаёт</th></tr>', $pages[1]['content_html']);
    }

    #[Test]
    public function selected_defs_compositions_conditionals_and_exact_pointers_are_projected(): void
    {
        $presentation = (new PortableSchemaReferenceProjector)->project('presentation', 'shared');
        self::assertNotEmpty($presentation);
        $byPath = [];
        foreach ($presentation as $record) {
            $byPath[$record['path']][] = $record;
            self::assertDoesNotMatchRegularExpression('~#$~', $record['provenance']);
            self::assertMatchesRegularExpression('~\.schema\.json#/~', $record['provenance']);
        }
        foreach ([
            '/branding/title',
            '/layout/regions/<^[a-z][a-z0-9_.-]+$>/sections/*/blocks/*/smart',
            '/layout/regions/<^[a-z][a-z0-9_.-]+$>/sections/*/blocks/*/element/utilities',
        ] as $path) {
            self::assertArrayHasKey($path, $byPath);
        }
        self::assertStringContainsString('uniqueItems=true', $byPath['/layout/regions/<^[a-z][a-z0-9_.-]+$>/sections/*/blocks/*/element/utilities'][0]['validation']);
        self::assertStringContainsString('minimum=0', $byPath['/layout/content/gap'][0]['validation']);
        self::assertTrue((bool) array_filter($presentation, static fn (array $record): bool => str_contains($record['validation'], 'oneOf=')));
        self::assertTrue((bool) array_filter($presentation, static fn (array $record): bool => str_contains($record['provenance'], '/if') || str_contains($record['provenance'], '/then')));
        self::assertArrayNotHasKey('/region', $byPath, 'Internal definitions are traversed through admitted public surfaces, not published as duplicate roots.');
    }

    #[Test]
    public function typed_placeholder_hydrates_description_default_and_technical_details(): void
    {
        $placeholder = (new PortableMarkdownRenderer)->render(":::schema_reference {schema=page scope=page}\n:::\n");
        $html = (new PortableSchemaReferenceHydrator)->hydrate([['locale' => 'ru', 'content_html' => $placeholder]])[0]['content_html'];
        foreach (['Поле', 'Что задаёт', 'По умолчанию', '<details><summary>Технические данные</summary>', 'Проверка', '/description', 'resources/schemas/page.schema.json'] as $marker) {
            self::assertStringContainsString($marker, $html);
        }
        self::assertStringNotContainsString('effective value', $html);
        // A section or page field inherits; the built-in value shows what applies when nothing sets it.
        self::assertMatchesRegularExpression('~<code>/reading/toc_depth</code></td><td>[^<]*<br>По умолчанию: наследуется, иначе <code>3</code><details>~u', $html);
        self::assertMatchesRegularExpression('~<code>/slug</code></td><td>Свой адрес страницы[^<]*<code>guides/install</code>~u', $html);
        // A pattern key reads as what it names; the exact pattern stays in the details.
        self::assertStringContainsString('<code>/layout/regions/&lt;область&gt;/enabled</code>', $html);
        self::assertStringContainsString('Путь: <code>/layout/regions/&lt;^[a-z][a-z0-9_.-]+$&gt;/enabled</code>', $html);
    }

    #[Test]
    public function site_fields_show_the_value_the_build_applies(): void
    {
        $records = [];
        foreach ((new PortableSchemaReferenceProjector)->project('site', 'site') as $record) {
            $records[$record['path']] = $record;
        }
        foreach ([
            '/settings/theme' => 'system',
            '/settings/ui_radius' => 'default',
            '/reading/toc_depth' => 3,
            '/search/enabled' => false,
            '/layout/container/max' => 7,
            '/branding/mode' => 'full',
            '/locales/missing_page_policy' => 'skip',
            '/not_found/enabled' => true,
        ] as $path => $expected) {
            self::assertTrue($records[$path]['has_default'], $path);
            self::assertSame($expected, $records[$path]['default'], $path);
            self::assertSame('docara', $records[$path]['default_source'], $path);
        }
        self::assertFalse($records['/framework_lock']['has_default']);
        self::assertSame(
            'site.schema.json#/properties/site_url',
            substr($records['/site_url']['provenance'], strlen('resources/schemas/')),
        );
        self::assertIsString($records['/site_url']['description']);
    }

    #[Test]
    public function fields_keep_only_the_named_top_level_fields(): void
    {
        $placeholder = (new PortableMarkdownRenderer)->render(":::schema_reference {schema=site scope=site fields=reading,search}\n:::\n");
        $html = (new PortableSchemaReferenceHydrator)->hydrate([['locale' => 'ru', 'content_html' => $placeholder]])[0]['content_html'];
        preg_match_all('~<tr><td><code>(/[^<]+)</code>~', $html, $paths);

        self::assertContains('/reading/toc_depth', $paths[1]);
        self::assertContains('/search/indexed', $paths[1]);
        foreach ($paths[1] as $path) {
            self::assertMatchesRegularExpression('~^/(reading|search)(/|$)~', $path);
        }
    }

    #[Test]
    public function unknown_fields_fail_while_the_page_renders(): void
    {
        try {
            (new PortableMarkdownRenderer)->render(":::schema_reference {schema=page scope=page fields=reading,base_url}\n:::\n");
            self::fail('A field of another schema must be rejected.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_SCHEMA_REFERENCE_FIELDS_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function unadmitted_schema_name_fails_before_projection(): void
    {
        $this->expectException(PortableConfigurationException::class);
        (new PortableMarkdownRenderer)->render(":::schema_reference {schema=../../secret scope=site}\n:::\n");
    }
}
