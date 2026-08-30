<?php

declare(strict_types=1);

namespace Docara\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExampleRuntimeContractTest extends TestCase
{
    public function test_framework_scripts_are_propagated_into_sandboxed_examples(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $renderer = (string) file_get_contents($root . '/src/PortableSite/PortableMarkdownRenderer.php');

        self::assertStringContainsString("scripts:Array.from(document.querySelectorAll('script[data-docara-framework-asset^=\"simai.framework.preloaded.component.\"][src]'))", $shell);
        self::assertStringContainsString('simai.framework.icon_font.ready', $shell);
        self::assertStringContainsString('inlineScripts:', $shell);
        self::assertStringContainsString('exampleFontAsset', $shell);
        self::assertStringContainsString('simai.framework.icon_font.css', $shell);
        self::assertStringContainsString('inlineStyles=styles', $shell);
        self::assertStringContainsString('Array.isArray(data.scripts)?data.scripts:[]', $renderer);
        self::assertStringContainsString('Array.isArray(data.inlineScripts)?data.inlineScripts:[]', $renderer);
        self::assertStringContainsString('Array.isArray(data.inlineStyles)?data.inlineStyles:[]', $renderer);
        self::assertStringContainsString('data-docara-example-framework-inline-style', $renderer);
        self::assertStringContainsString('URL.createObjectURL(new Blob([font.bytes]', $renderer);
        self::assertStringContainsString('data-docara-example-framework-inline-script', $renderer);
        self::assertStringContainsString('Promise.all(styleLoads)', $renderer);
        self::assertStringContainsString('data-docara-example-framework-script', $renderer);
        self::assertStringContainsString('script.async=false', $renderer);
    }

    public function test_example_height_measures_content_instead_of_its_current_viewport(): void
    {
        $renderer = (string) file_get_contents(dirname(__DIR__, 2) . '/src/PortableSite/PortableMarkdownRenderer.php');

        self::assertStringContainsString("document.documentElement.style.minHeight='0'", $renderer);
        self::assertStringContainsString("body.style.minHeight='0'", $renderer);
        self::assertStringContainsString('contentBottom-contentTop', $renderer);
        self::assertStringNotContainsString('body.scrollHeight,root.scrollHeight', $renderer);
    }
}
