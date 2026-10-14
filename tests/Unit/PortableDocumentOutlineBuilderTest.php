<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableDocumentOutlineBuilder;

final class PortableDocumentOutlineBuilderTest extends TestCase
{
    #[Test]
    public function it_decorates_unicode_headings_and_builds_a_depth_limited_outline(): void
    {
        $html = '<h1>Документация</h1>'
            . '<p>Введение.</p>'
            . '<h2>Привет, мир!</h2>'
            . '<h3><em>Résumé</em> &amp; API</h3>'
            . '<h4>Скрытый уровень</h4>';

        $result = (new PortableDocumentOutlineBuilder)->build($html, 3);

        self::assertStringContainsString('<h1 id="документация">Документация</h1>', $result['html']);
        self::assertStringContainsString('<h2 id="привет-мир">Привет, мир!</h2>', $result['html']);
        self::assertStringContainsString('<h3 id="résumé-api"><em>Résumé</em> &amp; API</h3>', $result['html']);
        self::assertStringContainsString('<h4 id="скрытый-уровень">Скрытый уровень</h4>', $result['html']);
        self::assertSame([
            ['id' => 'привет-мир', 'level' => 2, 'text' => 'Привет, мир!'],
            ['id' => 'résumé-api', 'level' => 3, 'text' => 'Résumé & API'],
        ], $result['items']);
    }

    #[Test]
    public function combining_marks_and_skipped_levels_are_stable_at_both_depth_boundaries(): void
    {
        $html = '<h1>Document</h1>'
            . "<h2>Cafe\u{0301}</h2>"
            . '<h4>Skipped level</h4>'
            . '<h6>Deep section</h6>';

        $shallow = (new PortableDocumentOutlineBuilder)->build($html, 2);
        self::assertSame([
            ['id' => 'café', 'level' => 2, 'text' => 'Café'],
        ], $shallow['items']);
        self::assertStringContainsString('<h4 id="skipped-level">', $shallow['html']);
        self::assertStringContainsString('<h6 id="deep-section">', $shallow['html']);

        $deep = (new PortableDocumentOutlineBuilder)->build($html, 6);
        self::assertSame([2, 4, 6], array_column($deep['items'], 'level'));
        self::assertSame(['café', 'skipped-level', 'deep-section'], array_column($deep['items'], 'id'));
    }

    #[Test]
    public function duplicate_literal_and_empty_slugs_are_collision_safe(): void
    {
        $html = '<h2>Раздел</h2>'
            . '<h2>Раздел-1</h2>'
            . '<h2>Раздел</h2>'
            . '<h2>Раздел</h2>'
            . '<h2>!!!</h2>'
            . '<h2>???</h2>';

        $result = (new PortableDocumentOutlineBuilder)->build($html, 6);

        self::assertSame(
            ['раздел', 'раздел-1', 'раздел-2', 'раздел-3', 'section', 'section-1'],
            array_column($result['items'], 'id'),
        );
        foreach (array_column($result['items'], 'id') as $id) {
            self::assertSame(1, substr_count($result['html'], 'id="' . $id . '"'));
        }
    }

    #[Test]
    public function h1_only_content_has_stable_anchor_but_no_outline(): void
    {
        $result = (new PortableDocumentOutlineBuilder)->build('<h1>Only title</h1><p>Text.</p>', 3);

        self::assertStringContainsString('id="only-title"', $result['html']);
        self::assertSame([], $result['items']);
    }

    #[Test]
    public function generated_demo_headings_do_not_pollute_the_document_outline(): void
    {
        $result = (new PortableDocumentOutlineBuilder)->build(
            '<h1>Component</h1><h2>Example</h2>'
            . '<div data-docara-outline-exclude><h2>Demo heading</h2></div>'
            . '<h2>Parameters</h2>',
            3,
        );

        self::assertSame(['Example', 'Parameters'], array_column($result['items'], 'text'));
        self::assertStringNotContainsString('id="demo-heading"', $result['html']);
        self::assertStringContainsString('<h2>Demo heading</h2>', $result['html']);
    }

    #[Test]
    public function generated_heading_ids_do_not_collide_with_reserved_shell_ids(): void
    {
        $result = (new PortableDocumentOutlineBuilder)->build(
            '<h1>Docara main</h1><h2>Docara search dialog</h2>',
            3,
            ['docara-main', 'docara-search-dialog'],
        );

        self::assertStringContainsString('<h1 id="docara-main-1">', $result['html']);
        self::assertSame('docara-search-dialog-1', $result['items'][0]['id']);
    }

    #[Test]
    public function image_alt_text_becomes_the_heading_anchor_and_outline_label(): void
    {
        $result = (new PortableDocumentOutlineBuilder)->build(
            '<h2><img src="architecture.png" alt="Architecture overview"></h2>',
            3,
        );

        self::assertSame([
            ['id' => 'architecture-overview', 'level' => 2, 'text' => 'Architecture overview'],
        ], $result['items']);
        self::assertStringContainsString('id="architecture-overview"', $result['html']);
    }

    #[Test]
    public function heading_without_accessible_text_fails_closed(): void
    {
        try {
            (new PortableDocumentOutlineBuilder)->build('<h2><img src="divider.png" alt=""></h2>', 3);
            self::fail('A heading without accessible text unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('DOCUMENT_OUTLINE_HEADING_TEXT_REQUIRED', $exception->errorCode);
        }
    }

    #[Test]
    public function invalid_utf8_fails_closed(): void
    {
        try {
            (new PortableDocumentOutlineBuilder)->build("<h2>\xFF</h2>", 3);
            self::fail('Invalid UTF-8 unexpectedly produced an outline.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('DOCUMENT_OUTLINE_INVALID_UTF8', $exception->errorCode);
        }
    }

    #[Test]
    public function depth_outside_the_schema_contract_fails_closed(): void
    {
        foreach ([1, 7] as $depth) {
            try {
                (new PortableDocumentOutlineBuilder)->build('<h2>Section</h2>', $depth);
                self::fail("Outline depth [$depth] unexpectedly passed.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('DOCUMENT_OUTLINE_DEPTH_INVALID', $exception->errorCode);
            }
        }
    }
}
