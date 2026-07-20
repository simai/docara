<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\DeclarativePipeline;
use Simai\Docara\Declarative\Semantic\SemanticParityChecker;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\PortableSite\PortableDocumentOutlineBuilder;
use Simai\Docara\PortableSite\PortableHtmlRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class DeclarativeSemanticParityTest extends TestCase
{
    public function test_legacy_and_declarative_pipelines_render_the_same_page_semantics(): void
    {
        $markdown = <<<'MD'
# Installation

Read the [guide](/guide/).

:::ui.alert
{"type":"info","title":"Before you begin","supporting-text":"Create a backup."}
:::

## Next step

Run the installer.
MD;
        $source = 'content/install.md';
        $lock = $this->frameworkLock();
        $legacyRuntime = FrameworkComponentRuntime::fromLock($lock);
        $legacyComponents = $legacyRuntime->extract($markdown, $source);
        $markdownRenderer = new PortableMarkdownRenderer;
        $legacyOutline = (new PortableDocumentOutlineBuilder)->build(
            $markdownRenderer->render($legacyComponents->markdownWithPlaceholders),
            3,
            (new PortableHtmlRenderer)->reservedDocumentIds(),
        );
        $legacyHtml = $legacyComponents->hydrate($legacyOutline['html']);

        $declarative = DeclarativePipeline::bundled(
            $lock,
            $markdownRenderer,
            (new PortableHtmlRenderer)->reservedDocumentIds(),
        )->build($markdown, $source, 'install', 'Installation', 3);

        $parity = (new SemanticParityChecker)->assertEquivalent(
            'Installation',
            $legacyHtml,
            $legacyComponents->normalizedCalls,
            $declarative,
        );

        self::assertTrue($parity->passed);
        self::assertSame($parity->legacy, $parity->declarative);
        self::assertSame(
            ['header', 'sidebar', 'main', 'outline', 'footer'],
            $parity->legacy['regions'],
        );
        self::assertSame('ui.alert', $parity->legacy['smart'][0]['smart']);
        self::assertStringContainsString('<sf-alert', $declarative->artifact->html);
    }

    /** @return array<string, mixed> */
    private function frameworkLock(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
