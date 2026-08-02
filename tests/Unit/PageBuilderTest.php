<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\ComponentBlockDocumentNodeRenderer;
use Simai\Docara\Document\ComponentDocumentNodeRenderer;
use Simai\Docara\Document\DocumentRendererRegistry;
use Simai\Docara\Document\SmartComponentNode;
use Simai\Docara\Document\SourceDocumentNodeRenderer;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\PortableSite\PageBuilder;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class PageBuilderTest extends TestCase
{
    public function test_framework_smart_directives_use_typed_ir_and_the_shared_gateway(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site';
        $plan = (new PortableConfigurationLoader($root))->resolve('content/ru/demonstrator-results/smart-alert.md');
        $gateway = SmartComponentGateway::bundled($plan->frameworkLock);
        $templates = new TrustedTemplateRegistry;
        $result = (new PageBuilder(
            new PortableMarkdownRenderer(components: $gateway),
            smartRenderer: new SmartRenderer($templates),
        ))->build(
            $plan,
            $root,
            FrameworkComponentRuntime::fromLock($plan->frameworkLock),
            3,
        );

        $smartNodes = array_values(array_filter(
            $result->document->allNodes(),
            static fn ($node): bool => $node instanceof SmartComponentNode,
        ));
        self::assertCount(1, $smartNodes);
        self::assertSame('ui.alert', $smartNodes[0]->smart);
        self::assertSame(1, $smartNodes[0]->ordinal);
        self::assertSame('ui.alert', $result->componentArtifacts[0]->hydration['smart']);
        self::assertSame('simai-framework', $result->componentArtifacts[0]->hydration['runtime']);
        self::assertSame('shared_smart_gateway', $result->frameworkComponents->diagnostics['mode']);
        self::assertSame(['ui.alert'], array_column($result->frameworkComponents->normalizedCalls, 'id'));
        self::assertSame([], $result->frameworkComponents->renderedHtml);
        self::assertStringNotContainsString('DOCARA_FRAMEWORK_COMPONENT_', $result->contentHtml);
        self::assertStringContainsString('<sf-alert', $result->contentHtml);
    }

    public function test_alert_uses_the_same_pagebuilder_with_five_typed_gateway_blocks(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site';
        $plan = (new PortableConfigurationLoader($root))->resolve('content/ru/components/alert.md');
        $result = (new PageBuilder(new PortableMarkdownRenderer))->build(
            $plan,
            $root,
            FrameworkComponentRuntime::fromLock($plan->frameworkLock),
            3,
        );

        self::assertNotNull($result->document);
        self::assertCount(5, $result->componentArtifacts);
        self::assertSame(
            ['docara.alert'],
            array_values(array_unique(array_column(
                array_map(static fn ($artifact): array => $artifact->hydration, $result->componentArtifacts),
                'smart',
            ))),
        );
        self::assertSame(5, substr_count($result->contentHtml, 'data-docara-block="alert"'));
        self::assertSame(2, substr_count($result->contentHtml, 'data-docara-code-block'));
        foreach ($result->componentArtifacts as $artifact) {
            self::assertSame('component_block', $artifact->hydration['node_type']);
            self::assertSame('content/ru/components/alert.md', $artifact->hydration['source']['file']);
            self::assertSame([], $artifact->assets);
        }
    }

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
            ['heading', 'paragraph', 'list', 'blockquote', 'image', 'table', 'code_block', 'example', 'typed_directive', 'html_comment', 'component', 'component_block'],
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

    public function test_all_physical_component_pages_use_typed_ir_and_native_family_nodes(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site';
        foreach ([
            'backlinks' => ['heading', 'paragraph', 'table', 'code_block', 'example', 'typed_directive'],
            'banner' => ['heading', 'paragraph', 'table', 'code_block', 'example', 'typed_directive'],
            'code' => ['heading', 'paragraph', 'table', 'code_block', 'example'],
            'details' => ['heading', 'paragraph', 'table', 'code_block', 'example', 'typed_directive'],
            'download' => ['heading', 'paragraph', 'table', 'code_block', 'example', 'typed_directive'],
            'footnotes-and-sources' => ['heading', 'paragraph', 'table', 'code_block', 'example'],
            'headings-and-text' => ['heading', 'paragraph', 'table', 'code_block', 'example'],
            'lists-and-quotes' => ['heading', 'paragraph', 'list', 'blockquote', 'code_block', 'example'],
            'links-and-images' => ['heading', 'paragraph', 'list', 'image', 'table', 'code_block', 'example'],
            'table' => ['heading', 'paragraph', 'table', 'code_block', 'example'],
            'syntax' => ['heading', 'paragraph', 'list', 'code_block'],
        ] as $slug => $expectedTypes) {
            $plan = (new PortableConfigurationLoader($root))->resolve("content/ru/components/$slug.md");
            $result = (new PageBuilder(new PortableMarkdownRenderer))->build(
                $plan,
                $root,
                FrameworkComponentRuntime::fromLock($plan->frameworkLock),
                3,
            );

            self::assertNotNull($result->document, $slug);
            $types = array_values(array_unique(array_map(
                static fn ($node): string => $node->type(),
                $result->document->allNodes(),
            )));
            sort($types, SORT_STRING);
            sort($expectedTypes, SORT_STRING);
            self::assertSame($expectedTypes, $types, $slug);
            if ($slug === 'table') {
                self::assertStringContainsString('Когда использовать', $result->contentHtml);
                self::assertStringContainsString('Полная сборка', $result->contentHtml);
                self::assertStringContainsString('Первая строка задаёт заголовки', $result->contentHtml);
            }
        }
    }
}
