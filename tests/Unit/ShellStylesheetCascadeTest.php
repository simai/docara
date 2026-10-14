<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\ShellStylesheetCascade;

final class ShellStylesheetCascadeTest extends TestCase
{
    private const LAYERS = ['sf.reset', 'sf.tokens', 'sf.base', 'sf.utilities', 'sf.components', 'sf.overrides', 'sf.states'];

    public function test_it_reads_the_layers_a_foundation_stylesheet_declares(): void
    {
        $css = "/* core */\n@charset \"UTF-8\";\n@layer sf.reset, sf.tokens,\n  sf.base;\n"
            . "@layer sf.base { @layer nested; .a { color: red } }\n:root { --x: '{'; }\n@layer late;";

        self::assertSame(['sf.reset', 'sf.tokens', 'sf.base', 'late'], ShellStylesheetCascade::declaredLayers($css));
        self::assertSame([], ShellStylesheetCascade::declaredLayers('@layer a, b; .x { color: red'));
    }

    /** @return iterable<string, array{0: string, 1: string, 2: bool}> */
    public static function pairs(): iterable
    {
        $utility = static fn (string $body): string => '@layer sf.utilities { ' . $body . ' }';
        $component = static fn (string $body): string => '@layer sf.components { ' . $body . ' }';

        yield 'same property, same layer' => [$utility('.a { color: red }'), $utility('.b { color: blue }'), true];
        yield 'same property, different layers' => [$utility('.a { color: red }'), $component('.b { color: blue }'), false];
        yield 'unlayered and layered' => ['.a { color: red }', $utility('.b { color: blue }'), false];
        yield 'both unlayered' => ['.a { color: red }', '.b { color: blue !important }', true];
        yield 'different properties' => [$utility('.a { font-size: 1rem }'), $utility('.b { font-weight: 700 }'), false];
        yield 'shorthand and longhand' => [$utility('.a { margin: 0 }'), $utility('.b { margin-left: 1px }'), true];
        yield 'logical and physical margin' => [$utility('.a { margin-inline-start: 0 }'), $utility('.b { margin-left: 1px }'), true];
        yield 'logical and physical inset' => [$utility('.a { inset-inline-start: 0 }'), $utility('.b { left: 1px }'), true];
        yield 'logical and physical size' => [$utility('.a { inline-size: 0 }'), $utility('.b { width: 1px }'), true];
        yield 'font shorthand and line height' => [$utility('.a { font: 1rem/1 serif }'), $utility('.b { line-height: 2 }'), true];
        yield 'font longhands' => [$utility('.a { font-size: 1rem }'), $utility('.b { line-height: 2 }'), false];
        yield 'border shorthand and side colour' => [$utility('.a { border: 1px solid }'), $utility('.b { border-top-color: red }'), true];
        yield 'border colour and side colour' => [$utility('.a { border-color: red }'), $utility('.b { border-inline-start-color: blue }'), true];
        yield 'border colour and radius' => [$utility('.a { border-color: red }'), $utility('.b { border-radius: 2px }'), false];
        yield 'physical and logical radius' => [$utility('.a { border-top-left-radius: 0 }'), $utility('.b { border-start-start-radius: 2px }'), true];
        yield 'vendor alias' => [$utility('.a { -webkit-transform: none }'), $utility('.b { transform: scale(2) }'), true];
        yield 'place shorthand' => [$utility('.a { place-items: center }'), $utility('.b { justify-items: start }'), true];
        yield 'white space and text wrap' => [$utility('.a { white-space: nowrap }'), $utility('.b { text-wrap: balance }'), true];
        yield 'all and a property' => [$utility('.a { all: unset }'), $utility('.b { color: red }'), true];
        yield 'all and a custom property' => [$utility('.a { all: unset }'), $utility('.b { --x: 1 }'), false];
        yield 'same custom property' => [$component('.a { --x: 1 }'), $component('.b { --x: 2 }'), true];
        yield 'escaped custom property' => [$component('.a { --a\\/b: 1 }'), $component('.b { --a\\2f b: 2 }'), true];
        yield 'different custom properties' => [$component('.a { --x: 1 }'), $component('.b { --y: 2 }'), false];
        yield 'conditional rules keep their layer' => [$utility('@media (min-width: 1px) { .a { color: red } }'), $utility('.b { color: blue }'), true];
        yield 'nested style rules' => [$utility('.a { &:hover { color: red } }'), $utility('.b { color: blue }'), true];
        yield 'same keyframes' => ['@keyframes spin { to { opacity: 1 } }', '@keyframes spin { to { opacity: 0 } }', true];
        yield 'different keyframes' => ['@keyframes spin { to { opacity: 1 } }', '@-webkit-keyframes fade { to { opacity: 0 } }', false];
        yield 'same registered property' => ['@property --x { syntax: "*"; inherits: true }', '@property --x { syntax: "<length>"; inherits: false; initial-value: 0px }', true];
        yield 'font faces' => ['@font-face { font-family: A; src: url(a.woff2) }', '@font-face { font-family: B; src: url(b.woff2) }', true];
        yield 'two undeclared layers' => ['@layer sf.system { .a { color: red } }', '@layer other { .b { width: 0 } }', true];
        yield 'one undeclared layer' => ['@layer sf.system { .a { color: red } }', $utility('.b { color: blue }'), false];
        yield 'declared layer statement' => ['@layer sf.base, sf.utilities;', '@layer other { .b { width: 0 } }', false];
        yield 'url with a semicolon' => [$utility('.a { background: url(data:image/svg+xml;utf8,<svg/>) }'), $utility('.b { color: red }'), false];
        yield 'escaped selector' => [$utility('.p-1\\/2 { padding: 0 } .x\\:hover\\(a { color: red }'), $utility('.b { padding-top: 0 }'), true];
        yield 'charset in a segment' => ["/* docara-shell-source: a */\n@charset \"UTF-8\";\n.a { color: red }", '@layer sf.base { .b { color: red } }', false];
    }

    #[DataProvider('pairs')]
    public function test_it_lets_segments_change_places_only_when_no_declaration_can_tie(string $a, string $b, bool $conflict): void
    {
        $cascade = new ShellStylesheetCascade(self::LAYERS);

        self::assertSame($conflict, ShellStylesheetCascade::conflicts($cascade->signature($a), $cascade->signature($b)));
        self::assertSame($conflict, ShellStylesheetCascade::conflicts($cascade->signature($b), $cascade->signature($a)));
    }

    /** @return iterable<string, array{0: string}> */
    public static function opaque(): iterable
    {
        yield 'import' => ['@import url(a.css);'];
        yield 'namespace' => ['@namespace svg url(http://www.w3.org/2000/svg);'];
        yield 'anonymous layer' => ['@layer { .a { color: red } }'];
        yield 'unknown at-rule' => ['@custom-thing x { .a { color: red } }'];
        yield 'top-level declaration' => ['color: red;'];
        yield 'unbalanced braces' => ['.a { color: red'];
        yield 'stray closing brace' => ['.a { color: red } }'];
        yield 'unterminated comment' => ['.a { color: red } /* x'];
        yield 'custom property with a block value' => ['.a { --x: { color: red }; }'];
    }

    #[DataProvider('opaque')]
    public function test_anything_it_does_not_understand_stays_in_place(string $css): void
    {
        $cascade = new ShellStylesheetCascade(self::LAYERS);
        $signature = $cascade->signature($css);

        self::assertTrue($signature['universal']);
        self::assertTrue(ShellStylesheetCascade::conflicts($signature, $cascade->signature('@layer sf.states { .z { --q: 1 } }')));
    }

    public function test_without_declared_layers_every_layered_segment_keeps_its_order(): void
    {
        $cascade = new ShellStylesheetCascade([]);

        self::assertTrue(ShellStylesheetCascade::conflicts(
            $cascade->signature('@layer sf.utilities { .a { color: red } }'),
            $cascade->signature('@layer sf.components { .b { width: 0 } }'),
        ));
        self::assertFalse(ShellStylesheetCascade::conflicts(
            $cascade->signature('.a { color: red }'),
            $cascade->signature('@layer sf.components { .b { width: 0 } }'),
        ));
    }
}
