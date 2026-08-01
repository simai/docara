<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Document\ComponentAliasRegistry;
use Simai\Docara\Document\ComponentNode;
use Simai\Docara\Document\DocumentIr;
use Simai\Docara\Document\DocumentNode;
use Simai\Docara\Document\DocumentRenderContext;
use Simai\Docara\Document\DocumentRendererRegistry;
use Simai\Docara\Document\MarkdownCompiler;
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

    public function test_badge_alias_uses_the_single_content_gateway_and_no_hardcoded_inline_method_remains(): void
    {
        $renderer = new \ReflectionClass(InlineComponentRenderer::class);

        self::assertFalse($renderer->hasMethod('badge'));
        self::assertSame(
            ['badge' => 'docara.badge'],
            (new ComponentAliasRegistry)->aliases(),
        );
        self::assertSame(
            ['heading', 'paragraph', 'table', 'code_block', 'example', 'component'],
            DocumentRendererRegistry::bundled(new PortableMarkdownRenderer)->types(),
        );
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
