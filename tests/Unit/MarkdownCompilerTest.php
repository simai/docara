<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Document\ComponentAliasRegistry;
use Simai\Docara\Document\ComponentBlockNode;
use Simai\Docara\Document\ComponentDocumentNodeRenderer;
use Simai\Docara\Document\ComponentNode;
use Simai\Docara\Document\DocumentIr;
use Simai\Docara\Document\DocumentNode;
use Simai\Docara\Document\DocumentRenderContext;
use Simai\Docara\Document\DocumentRendererRegistry;
use Simai\Docara\Document\MarkdownCompiler;
use Simai\Docara\Document\SourceDocumentNodeRenderer;
use Simai\Docara\Document\SourceLocation;
use Simai\Docara\Markdown\InlineComponentRenderer;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class MarkdownCompilerTest extends TestCase
{
    public function test_badge_markdown_compiles_to_typed_in_memory_ir_with_exact_source_locations(): void
    {
        $root = dirname(__DIR__, 2);
        $path = $root . '/docs/site/content/ru/components/badge.md';
        $document = (new MarkdownCompiler)->compile(
            (string) file_get_contents($path),
            'content/ru/components/badge.md',
        );
        $counts = [];
        foreach ($document->allNodes() as $node) {
            $counts[$node->type()] = ($counts[$node->type()] ?? 0) + 1;
        }
        ksort($counts, SORT_STRING);

        self::assertSame([
            'code_block' => 4,
            'component' => 16,
            'example' => 4,
            'heading' => 4,
            'paragraph' => 4,
            'table' => 3,
        ], $counts);
        $components = array_values(array_filter(
            $document->allNodes(),
            static fn (DocumentNode $node): bool => $node instanceof ComponentNode,
        ));
        self::assertSame('docara.badge', $components[0]->component);
        self::assertSame(['line' => 8, 'column' => 1], [
            'line' => $components[0]->location()->line,
            'column' => $components[0]->location()->column,
        ]);
        self::assertSame(75, $components[15]->location()->line);
        self::assertSame(
            trim((string) file_get_contents($root . '/tests/fixtures/document-ir/badge.json')),
            trim(CanonicalJson::encodePretty($document->toArray())),
        );
    }

    public function test_alias_prop_slot_and_renderer_errors_include_the_physical_source_location(): void
    {
        $compiler = new MarkdownCompiler;
        $this->assertError(
            'DOCUMENT_COMPONENT_ALIAS_UNKNOWN',
            'content/ru/broken.md:3:1',
            fn () => $compiler->compile($this->example(':unknown[Label]'), 'content/ru/broken.md'),
        );
        $this->assertError(
            'DOCUMENT_COMPONENT_SLOT_REQUIRED',
            'content/ru/broken.md:3:1',
            fn () => $compiler->compile($this->example(':badge[]{size=1}'), 'content/ru/broken.md'),
        );

        $badProp = $compiler->compile($this->example(':badge[Label]{unknown=yes}'), 'content/ru/broken.md');
        $this->assertError(
            'CONTENT_COMPONENT_PROP_UNKNOWN',
            'content/ru/broken.md:3:1',
            fn () => DocumentRendererRegistry::bundled(new PortableMarkdownRenderer)->render(
                $badProp,
                new DocumentRenderContext(null, null),
            ),
        );

        $unknown = new class implements DocumentNode
        {
            public function type(): string
            {
                return 'future_node';
            }

            public function raw(): string
            {
                return 'future';
            }

            public function location(): SourceLocation
            {
                return new SourceLocation('content/ru/future.md', 9, 4, 9);
            }

            public function children(): array
            {
                return [];
            }

            public function toArray(): array
            {
                return [];
            }
        };
        $this->assertError(
            'DOCUMENT_IR_RENDERER_UNKNOWN',
            'content/ru/future.md:9:4',
            fn () => DocumentRendererRegistry::bundled(new PortableMarkdownRenderer)->render(
                new DocumentIr('content/ru/future.md', [$unknown]),
                new DocumentRenderContext(null, null),
            ),
        );
    }

    public function test_alert_fences_compile_to_one_generic_component_block_contract(): void
    {
        $markdown = <<<'MD'
# Alert

:::alert {type=warning variant=outlined}
#### Обратите внимание

Проверьте параметры перед публикацией.
:::
MD;
        $document = (new MarkdownCompiler)->compile($markdown, 'content/ru/components/alert.md');
        $blocks = array_values(array_filter(
            $document->allNodes(),
            static fn (DocumentNode $node): bool => $node instanceof ComponentBlockNode,
        ));

        self::assertCount(1, $blocks);
        self::assertSame([
            'type' => 'component_block',
            'alias' => 'alert',
            'component' => 'docara.alert',
            'props' => ['type' => 'warning', 'variant' => 'outlined'],
            'source' => [
                'file' => 'content/ru/components/alert.md',
                'line' => 3,
                'column' => 1,
                'end_line' => 7,
            ],
            'children' => [
                [
                    'type' => 'heading',
                    'source' => [
                        'file' => 'content/ru/components/alert.md',
                        'line' => 4,
                        'column' => 1,
                        'end_line' => 4,
                    ],
                    'data' => ['level' => 4, 'text' => 'Обратите внимание'],
                    'children' => [],
                ],
                [
                    'type' => 'paragraph',
                    'source' => [
                        'file' => 'content/ru/components/alert.md',
                        'line' => 6,
                        'column' => 1,
                        'end_line' => 6,
                    ],
                    'data' => ['text' => 'Проверьте параметры перед публикацией.'],
                    'children' => [],
                ],
            ],
        ], $blocks[0]->toArray());
    }

    public function test_component_block_failures_are_closed_with_physical_locations(): void
    {
        $compiler = new MarkdownCompiler;
        $this->assertError(
            'DOCUMENT_COMPONENT_ALIAS_UNKNOWN',
            'content/ru/broken.md:2:1',
            fn () => $compiler->compile("# Broken\n:::unknown\nText\n:::\n", 'content/ru/broken.md'),
        );
        $this->assertError(
            'DOCUMENT_COMPONENT_BLOCK_UNCLOSED',
            'content/ru/broken.md:2:1',
            fn () => $compiler->compile("# Broken\n:::alert\nText\n", 'content/ru/broken.md'),
        );
        $this->assertError(
            'DOCUMENT_COMPONENT_BLOCK_CONTENT_REQUIRED',
            'content/ru/broken.md:2:1',
            fn () => $compiler->compile("# Broken\n:::alert\n:::\n", 'content/ru/broken.md'),
        );
        $this->assertError(
            'DOCUMENT_COMPONENT_BLOCK_NESTED_FORBIDDEN',
            'content/ru/broken.md:3:1',
            fn () => $compiler->compile("# Broken\n:::alert\n:::badge\nText\n:::\n:::\n", 'content/ru/broken.md'),
        );
        $unrendered = $compiler->compile("# Broken\n:::alert\nText\n:::\n", 'content/ru/broken.md');
        $this->assertError(
            'DOCUMENT_IR_RENDERER_UNKNOWN',
            'content/ru/broken.md:2:1',
            fn () => (new DocumentRendererRegistry([
                new SourceDocumentNodeRenderer(new PortableMarkdownRenderer),
                new ComponentDocumentNodeRenderer((new PortableMarkdownRenderer)->componentGateway()),
            ]))->render(
                $unrendered,
                new DocumentRenderContext(null, null),
            ),
        );
    }

    public function test_known_typed_directives_use_one_generic_ir_node_and_keep_empty_blocks(): void
    {
        $document = (new MarkdownCompiler)->compile(<<<'MD'
# Directives

:::details {view=lines open=true}
## Дополнительные сведения

Содержимое можно раскрыть.
:::

:::backlinks {limit=5}
:::
MD, 'content/ru/components/details.md');
        $directives = array_values(array_filter(
            $document->allNodes(),
            static fn (DocumentNode $node): bool => $node->type() === 'typed_directive',
        ));

        self::assertCount(2, $directives);
        self::assertSame('docara.details', $directives[0]->data['component']);
        self::assertSame(['open' => 'true', 'view' => 'lines'], $directives[0]->data['props']);
        self::assertSame([3, 7], [$directives[0]->location()->line, $directives[0]->location()->endLine]);
        self::assertSame('docara.backlinks', $directives[1]->data['component']);
        self::assertSame([9, 10], [$directives[1]->location()->line, $directives[1]->location()->endLine]);

        $rendered = DocumentRendererRegistry::bundled(new PortableMarkdownRenderer)->render(
            $document,
            new DocumentRenderContext(null, 'content/ru/components/details.md'),
        );
        self::assertStringContainsString('<details', $rendered['document']->html);
        self::assertStringContainsString('data-docara-backlinks', $rendered['document']->html);
        self::assertSame([], $rendered['components']);
    }

    public function test_component_block_uses_the_bundled_registry_and_single_smart_gateway(): void
    {
        $compiler = new MarkdownCompiler;
        $source = 'content/ru/components/alert.md';
        $document = $compiler->compile(
            "# Alert\n\n:::alert {type=warning variant=outlined}\n#### Обратите внимание\n\nПроверьте параметры перед публикацией.\n:::\n",
            $source,
        );
        $rendered = DocumentRendererRegistry::bundled(new PortableMarkdownRenderer)->render(
            $document,
            new DocumentRenderContext(null, null),
        );

        self::assertCount(1, $rendered['components']);
        self::assertSame('docara.alert', $rendered['components'][0]->hydration['smart']);
        self::assertSame('component_block', $rendered['components'][0]->hydration['node_type']);
        self::assertSame($source, $rendered['components'][0]->hydration['source']['file']);
        self::assertStringContainsString(
            '<section data-docara-block="alert" role="status" aria-label="Обратите внимание" class="sf-alert sf-alert--warning sf-alert--outlined flex items-start m-bottom-1">',
            $rendered['document']->html,
        );
        self::assertStringContainsString(
            '<div class="sf-alert-supporting-text"><p>Проверьте параметры перед публикацией.</p></div>',
            $rendered['document']->html,
        );
    }

    public function test_example_keeps_component_block_inside_markdown_fence_as_typed_ir(): void
    {
        $source = 'content/ru/components/alert.md';
        $document = (new MarkdownCompiler)->compile(<<<'MD'
# Alert

:::example {label="Общий пример"}
```markdown
:::alert {type=info variant=default}
#### Полезная информация

Продолжайте работу в обычном порядке.
:::
```
:::
MD, $source);
        $examples = array_values(array_filter(
            $document->nodes,
            static fn (DocumentNode $node): bool => $node->type() === 'example',
        ));
        $blocks = array_values(array_filter(
            $document->allNodes(),
            static fn (DocumentNode $node): bool => $node instanceof ComponentBlockNode,
        ));

        self::assertCount(1, $examples);
        self::assertCount(1, $blocks);
        self::assertSame('docara.alert', $blocks[0]->component);
        self::assertSame(5, $blocks[0]->location()->line);
        self::assertSame(9, $blocks[0]->location()->endLine);

        $rendered = DocumentRendererRegistry::bundled(new PortableMarkdownRenderer)->render(
            $document,
            new DocumentRenderContext(null, $source),
        );
        self::assertCount(1, $rendered['components']);
        self::assertStringContainsString('data-docara-example=', $rendered['document']->html);
        self::assertStringContainsString('data-docara-example-tab="example"', $rendered['document']->html);
        self::assertStringContainsString('data-docara-example-tab="markdown"', $rendered['document']->html);
        self::assertStringContainsString('data-docara-block="alert"', $rendered['document']->html);
    }

    public function test_component_block_prop_and_document_slot_failures_keep_source_locations(): void
    {
        $compiler = new MarkdownCompiler;
        $renderer = DocumentRendererRegistry::bundled(new PortableMarkdownRenderer);
        foreach ([
            ['CONTENT_COMPONENT_PROP_UNKNOWN', ':::alert {unknown=yes}', "#### Title\n\nText"],
            ['CONTENT_COMPONENT_PROP_INVALID', ':::alert {type=purple}', "#### Title\n\nText"],
            ['CONTENT_COMPONENT_BLOCK_HEADING_REQUIRED', ':::alert', 'Text only'],
            ['CONTENT_COMPONENT_BLOCK_CONTENT_REQUIRED', ':::alert', '#### Title'],
        ] as [$code, $opening, $body]) {
            $document = $compiler->compile(
                "# Broken\n$opening\n$body\n:::\n",
                'content/ru/broken.md',
            );
            $this->assertError(
                $code,
                'content/ru/broken.md:2:1',
                fn () => $renderer->render($document, new DocumentRenderContext(null, null)),
            );
        }
    }

    public function test_all_alert_variants_match_the_accepted_legacy_artifact(): void
    {
        $markdown = new PortableMarkdownRenderer;
        $registry = DocumentRendererRegistry::bundled($markdown);
        foreach ([
            ['info', 'default', 'Полезная информация', 'Продолжайте работу в обычном порядке.'],
            ['clear', 'default', 'К сведению', 'Это сообщение не требует действия.'],
            ['success', 'default', 'Готово', 'Сборка успешно прошла проверку.'],
            ['warning', 'outlined', 'Обратите внимание', 'Проверьте параметры перед публикацией.'],
            ['danger', 'flat', 'Требуется исправление', 'Конфигурация не прошла проверку.'],
        ] as [$type, $variant, $title, $supporting]) {
            $source = ":::alert {type=$type variant=$variant}\n#### $title\n\n$supporting\n:::\n";
            $legacy = $markdown->render($source, null, null);
            $document = (new MarkdownCompiler)->compile($source, 'content/ru/components/alert.md');
            $target = $registry->render($document, new DocumentRenderContext(null, null));

            self::assertSame(trim($legacy), trim($target['document']->html), "$type/$variant");
            self::assertCount(1, $target['components']);
        }
    }

    public function test_badge_alias_uses_the_single_content_gateway_and_no_hardcoded_inline_method_remains(): void
    {
        $renderer = new \ReflectionClass(InlineComponentRenderer::class);

        self::assertFalse($renderer->hasMethod('badge'));
        self::assertSame(
            ['alert' => 'docara.alert', 'badge' => 'docara.badge'],
            (new ComponentAliasRegistry)->aliases(),
        );
        self::assertSame(
            ['heading', 'paragraph', 'list', 'blockquote', 'image', 'table', 'code_block', 'example', 'typed_directive', 'component', 'component_block'],
            DocumentRendererRegistry::bundled(new PortableMarkdownRenderer)->types(),
        );
    }

    public function test_native_lists_and_quotes_keep_typed_source_ranges(): void
    {
        $document = (new MarkdownCompiler)->compile(<<<'MD'
# Content

- First
- Second
  - Nested

> Exact quote.
{author="Author" source="Source" url="https://example.com/source"}
MD, 'content/ru/components/lists-and-quotes.md');
        $nodes = $document->nodes;

        self::assertSame(['heading', 'list', 'blockquote'], array_map(
            static fn (DocumentNode $node): string => $node->type(),
            $nodes,
        ));
        self::assertSame([3, 5], [$nodes[1]->location()->line, $nodes[1]->location()->endLine]);
        self::assertSame([7, 8], [$nodes[2]->location()->line, $nodes[2]->location()->endLine]);
        $html = DocumentRendererRegistry::bundled(new PortableMarkdownRenderer)->render(
            $document,
            new DocumentRenderContext(null, null),
        )['document']->html;
        self::assertStringContainsString('<ul>', $html);
        self::assertStringContainsString('<blockquote', $html);
        self::assertStringContainsString('Author', $html);
        self::assertStringContainsString('https://example.com/source', $html);
    }

    public function test_block_image_keeps_alt_url_and_physical_location(): void
    {
        $document = (new MarkdownCompiler)->compile(<<<'MD'
# Image

![Знак Docara](/ru/assets/docara-mark.svg){ratio=16x9 fit=contain}
MD, 'content/ru/components/links-and-images.md');
        $image = $document->nodes[1];

        self::assertSame('image', $image->type());
        self::assertSame([
            'alt' => 'Знак Docara',
            'url' => '/ru/assets/docara-mark.svg',
        ], $image->data);
        self::assertSame('content/ru/components/links-and-images.md:3:1', $image->location()->label());
        $html = DocumentRendererRegistry::bundled(new PortableMarkdownRenderer)->render(
            $document,
            new DocumentRenderContext(null, null),
        )['document']->html;
        self::assertStringContainsString('alt="Знак Docara"', $html);
        self::assertStringContainsString('ratio-16-9', $html);
        self::assertStringContainsString('object-contain', $html);
    }

    private function example(string $call): string
    {
        return ":::example {label=Example}\n```markdown\n$call\n```\n:::\n";
    }

    private function assertError(string $code, string $location, callable $callback): void
    {
        try {
            $callback();
            self::fail("Expected [$code].");
        } catch (PortableConfigurationException $exception) {
            self::assertSame($code, $exception->errorCode);
            self::assertStringContainsString($location, $exception->getMessage());
        }
    }
}
