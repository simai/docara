<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Document\ComponentBlockDocumentNodeRenderer;
use Simai\Docara\Document\ComponentDocumentNodeRenderer;
use Simai\Docara\Document\DocumentRendererRegistry;
use Simai\Docara\Document\SourceDocumentNodeRenderer;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\PortableSite\PageBuilder;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class PageBuilderTest extends TestCase
{
    public function test_badge_uses_one_pagebuilder_with_typed_ir_and_sixteen_gateway_artifacts(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site';
        $plan = (new PortableConfigurationLoader($root))->resolve('content/ru/components/badge.md');
        $result = (new PageBuilder(new PortableMarkdownRenderer))->build(
            $plan,
            $root,
            FrameworkComponentRuntime::fromLock($plan->frameworkLock),
            3,
        );

        self::assertNotNull($result->document);
        self::assertCount(16, $result->componentArtifacts);
        self::assertSame(
            ['heading', 'paragraph', 'table', 'code_block', 'example', 'component', 'component_block'],
            $result->componentArtifacts === []
                ? []
                : (new DocumentRendererRegistry([
                    new SourceDocumentNodeRenderer(new PortableMarkdownRenderer),
                    new ComponentDocumentNodeRenderer(
                        (new PortableMarkdownRenderer)->componentGateway(),
                    ),
                    new ComponentBlockDocumentNodeRenderer(
                        (new PortableMarkdownRenderer)->componentGateway(),
                        new PortableMarkdownRenderer,
                    ),
                ]))->types(),
        );
        self::assertSame(
            'e4f3670e53b1da50fc9cf08268dc6c40c08c05dc9168643d9e8d371d0be7318f',
            hash('sha256', $result->contentHtml),
        );
        foreach ($result->componentArtifacts as $artifact) {
            self::assertSame('docara.badge', $artifact->hydration['smart']);
            self::assertSame('badge', $artifact->hydration['alias']);
            self::assertSame('content/ru/components/badge.md', $artifact->hydration['source']['file']);
            self::assertSame([], $artifact->assets);
        }
    }
}
