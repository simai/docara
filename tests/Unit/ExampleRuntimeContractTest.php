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
        self::assertStringContainsString('simai.framework.icon_fallback_font.css', $shell);
        self::assertStringContainsString('function exampleDesignTokens()', $shell);
        self::assertStringContainsString('function collectExampleTokenNames(rules,names)', $shell);
        self::assertStringContainsString("names=new Set(['--sf-text--size','--sf-text--height'])", $shell);
        self::assertStringContainsString('collectExampleTokenNames(sheet.cssRules,names)', $shell);
        self::assertStringContainsString('designTokens:exampleDesignTokens()', $shell);
        self::assertStringContainsString('rootFontSize:getComputedStyle(document.documentElement).fontSize', $shell);
        self::assertStringContainsString('Array.isArray(data.scripts)?data.scripts:[]', $renderer);
        self::assertStringContainsString('Array.isArray(data.inlineScripts)?data.inlineScripts:[]', $renderer);
        self::assertStringContainsString('Array.isArray(data.inlineStyles)?data.inlineStyles:[]', $renderer);
        self::assertStringContainsString('data-docara-example-framework-inline-style', $renderer);
        self::assertStringContainsString('URL.createObjectURL(new Blob([font.bytes]', $renderer);
        self::assertStringContainsString('data-docara-example-framework-inline-script', $renderer);
        self::assertStringContainsString('Promise.all(styleLoads)', $renderer);
        self::assertLessThan(
            strpos($renderer, "var current=Array.from(document.querySelectorAll('link[data-docara-example-framework-style]')"),
            strpos($renderer, "var currentInlineStyles=Array.from(document.querySelectorAll('style[data-docara-example-framework-inline-style]')"),
            'Portable font styles must be installed before external Framework stylesheets can trigger opaque-origin font requests.',
        );
        self::assertStringContainsString('data-docara-example-framework-script', $renderer);
        self::assertStringContainsString('script.async=false', $renderer);
    }

    public function test_example_height_measures_content_instead_of_its_current_viewport(): void
    {
        $shell = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');
        $renderer = (string) file_get_contents(dirname(__DIR__, 2) . '/src/PortableSite/PortableMarkdownRenderer.php');

        self::assertStringContainsString('scrolling="no"', $renderer);
        self::assertStringContainsString("document.documentElement.style.minHeight='0'", $renderer);
        self::assertStringContainsString("body.style.minHeight='0'", $renderer);
        self::assertStringContainsString("document.documentElement.style.overflow='hidden'", $renderer);
        self::assertStringContainsString("body.style.overflow='hidden'", $renderer);
        self::assertStringContainsString('function applyDesignEnvironment(data)', $renderer);
        self::assertStringContainsString('Object.keys(tokens).sort().slice(0,4096)', $renderer);
        self::assertStringContainsString('contentBottom-contentTop', $renderer);
        self::assertStringNotContainsString('body.scrollHeight,root.scrollHeight', $renderer);
        self::assertStringContainsString("frame.style.blockSize=Math.max(32,Math.ceil(height))+'px'", $shell);
        self::assertStringNotContainsString('Math.min(4096', $shell);
    }

    public function test_example_controls_keep_fullscreen_and_soft_wrap_scopes_separate(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root . '/resources/portable/declarative-shell.js');
        $styles = (string) file_get_contents($root . '/resources/portable/declarative-shell.css');

        self::assertStringContainsString("example.dataset.sourceActive=key==='example'?'false':'true'", $shell);
        self::assertStringContainsString('fullscreenButton.hidden=!active&&!selected', $shell);
        self::assertStringContainsString("wrapButton.hidden=example.dataset.sourceActive!=='true'", $shell);
        self::assertStringContainsString('document.fullscreenElement===example', $shell);
        self::assertStringContainsString('example.requestFullscreen()', $shell);
        self::assertStringContainsString('document.exitFullscreen()', $shell);
        self::assertStringContainsString("localStorage.setItem(sourceWrapStorageKey,sourceWrapActive?'true':'false')", $shell);
        self::assertStringContainsString("sourceWrapStorageKey='docara.source.wrap'", $shell);
        self::assertStringNotContainsString("message(active?'examples.unwrap':'examples.wrap')", $shell);
        self::assertStringContainsString("document.addEventListener('docara:source-wrap-change'", $shell);
        self::assertStringContainsString('if(text&&text.textContent!==label){text.textContent=label}', $shell);
        self::assertStringContainsString("button.dataset.unwrapIcon||'format_text_overflow'", $shell);
        self::assertStringContainsString('syncSourceWrapIcon(button,active)', $shell);
        self::assertStringContainsString('syncSourceWrapIcon(wrapButton,active)', $shell);
        self::assertStringContainsString("block.dataset.docaraCodeWrapActive=active?'true':'false'", $shell);
        self::assertStringContainsString("window.matchMedia('(max-width: 640px)').matches", $shell);
        self::assertStringContainsString("var lines=Array.from(code.querySelectorAll('.hljs-ln-code'))", $shell);
        self::assertStringContainsString('[data-docara-example-wrap-active="true"]', $styles);
        self::assertStringContainsString('white-space:pre-wrap', $styles);
        self::assertStringContainsString('.docara-example-preview:fullscreen', $styles);
        self::assertStringContainsString('.docara-example-preview .docara-example-preview__action:focus:not(:focus-visible)', $styles);
        self::assertStringNotContainsString('.docara-example-preview .docara-example-preview__action:focus,.docara-example-preview .docara-example-preview__action:focus-visible', $styles);
    }
}
