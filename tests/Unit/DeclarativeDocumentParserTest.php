<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Declarative\Document\MarkdownNode;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Framework\FrameworkComponentException;

final class DeclarativeDocumentParserTest extends TestCase
{
    public function test_it_parses_markdown_and_smart_calls_into_an_immutable_typed_ast(): void
    {
        $markdown = <<<'MD'
# Installation

Read the [guide](/guide/).

:::ui.alert
{"type":"info","title":"Before you begin","supporting-text":"Create a backup."}
:::

## Next step

Continue with the installer.
MD;

        $ast = (new DocumentParser)->parse($markdown . "\n", 'content/install.md');

        self::assertSame('docara.document_ast.v1', $ast->toArray()['schema']);
        self::assertSame('content/install.md', $ast->source);
        self::assertCount(3, $ast->nodes);
        self::assertInstanceOf(MarkdownNode::class, $ast->nodes[0]);
        self::assertInstanceOf(SmartCallNode::class, $ast->nodes[1]);
        self::assertInstanceOf(MarkdownNode::class, $ast->nodes[2]);

        $smart = $ast->nodes[1];
        self::assertInstanceOf(SmartCallNode::class, $smart);
        self::assertSame('ui.alert', $smart->smart);
        self::assertSame('default', $smart->view);
        self::assertSame('Before you begin', $smart->props['title']);
        self::assertSame(5, $smart->span()->startLine);

        self::assertSame(
            [
                ['id' => 'installation', 'level' => 1, 'text' => 'Installation'],
                ['id' => 'next-step', 'level' => 2, 'text' => 'Next step'],
            ],
            array_map(
                static fn ($heading): array => [
                    'id' => $heading->id,
                    'level' => $heading->level,
                    'text' => $heading->text,
                ],
                $ast->headings,
            ),
        );
        self::assertCount(1, $ast->links);
        self::assertSame('/guide/', $ast->links[0]->destination);
        self::assertSame('guide', $ast->links[0]->label);
        self::assertSame($ast->canonicalHash(), (new DocumentParser)->parse(
            $markdown . "\n",
            'content/install.md',
        )->canonicalHash());

        $this->expectException(\Error::class);
        $ast->nodes[] = new MarkdownNode(
            'forbidden',
            'mutation',
            $ast->nodes[0]->span(),
        );
    }

    public function test_it_keeps_smart_examples_inside_code_fences_as_markdown(): void
    {
        $ast = (new DocumentParser)->parse(<<<'MD'
# Example

```markdown
:::ui.alert
{"title":"Example only"}
:::
```
MD, 'content/example.md');

        self::assertCount(1, $ast->nodes);
        self::assertInstanceOf(MarkdownNode::class, $ast->nodes[0]);
        self::assertSame([], $ast->smartCalls());
    }

    public function test_it_fails_closed_for_unsupported_smart_components(): void
    {
        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_COMPONENT_UNSUPPORTED');

        (new DocumentParser)->parse(
            ":::ui.button\n{}\n:::\n",
            'content/unsupported.md',
        );
    }

    public function test_parser_source_has_no_render_implementation(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/src/Declarative/Document/DocumentParser.php',
        );

        self::assertDoesNotMatchRegularExpression('/<\\/?(?:html|body|main|section|div|p|script|style|sf-)/i', $source);
        self::assertStringNotContainsString('htmlspecialchars(', $source);
        self::assertStringNotContainsString('render(', $source);
    }
}
