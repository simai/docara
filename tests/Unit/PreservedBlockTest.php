<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\PageInspectionService;
use Simai\Docara\Application\ValidationService;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\Document\MarkdownCompiler;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Markdown\PreservedBlocks;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableProjectInitializer;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

/**
 * A :::preserved block stays in the Markdown source and never reaches the
 * built page, its title or its description; the author is told with the
 * warning PRESERVED_NOT_SHOWN.
 */
final class PreservedBlockTest extends TestCase
{
    private const PAGE = <<<'MD'
---
title: "Kept notes"
---

:::preserved {reason="former wording"}
# Secret heading

Secret first paragraph.
:::

# Kept notes

Public paragraph.

::::preserved
:::details
## Secret details

Secret body.
:::
::::
MD;

    #[Test]
    public function the_renderer_and_the_compiler_drop_the_block(): void
    {
        $markdown = "# Title\n\nBefore.\n\n:::preserved\nSecret text.\n:::\n\nAfter.\n";

        $html = (new PortableMarkdownRenderer)->render($markdown);
        self::assertStringNotContainsString('Secret', $html);
        self::assertStringNotContainsString('preserved', $html);
        self::assertStringContainsString('<p>Before.</p>', $html);
        self::assertStringContainsString('<p>After.</p>', $html);

        $nodes = (new MarkdownCompiler)->compile($markdown, 'page.md')->nodes;
        self::assertSame(['Title', 'Before.', 'After.'], array_map(
            static fn ($node): string => (string) ($node->data['text'] ?? ''),
            $nodes,
        ));
        self::assertSame(9, $nodes[2]->location()->line, 'Later lines keep their numbers.');
    }

    #[Test]
    public function only_a_preserved_opening_outside_code_is_a_block(): void
    {
        $markdown = implode("\n", [
            'Text :::preserved inline.',
            '',
            '```markdown',
            ':::preserved',
            'Shown as code.',
            ':::',
            '```',
            '',
            ':::preservedx',
            '',
            ':::preserved',
            '```',
            ':::',
            '```',
            'Hidden after code.',
            ':::',
            '',
        ]);

        self::assertSame([11], PreservedBlocks::scan($markdown));
        $stripped = PreservedBlocks::strip($markdown);
        self::assertStringContainsString("```markdown\n:::preserved\nShown as code.\n:::\n```", $stripped);
        self::assertStringContainsString(':::preservedx', $stripped);
        self::assertStringNotContainsString('Hidden after code.', $stripped);
        self::assertSame(substr_count($markdown, "\n"), substr_count($stripped, "\n"));
        self::assertSame('No block here.', PreservedBlocks::strip('No block here.'));
        self::assertNull(PreservedBlocks::opening('  :::preserved'));
        self::assertNull(PreservedBlocks::opening(':::preserved text'));
        self::assertSame('::::', PreservedBlocks::opening('::::preserved {note="x"}'));
    }

    #[Test]
    public function an_unclosed_block_fails_instead_of_showing_its_text(): void
    {
        foreach ([
            static fn () => (new PortableMarkdownRenderer)->render("A\n\n:::preserved\nSecret.\n"),
            static fn () => (new MarkdownCompiler)->compile("A\n\n:::preserved\nSecret.\n", 'page.md'),
        ] as $run) {
            try {
                $run();
                self::fail('An unclosed preserved block must not render.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_BLOCK_UNCLOSED', $exception->errorCode);
                self::assertStringContainsString('[preserved] at line [3]', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function validate_page_warns_for_each_block_with_its_line(): void
    {
        $this->project();
        $result = (new ValidationService)->validate($this->tmp, 'page', '/kept-notes/')->toArray();

        self::assertSame('success', $result['status']);
        self::assertSame(0, $result['data']['errors']);
        $preserved = array_values(array_filter(
            $result['data']['checks'],
            static fn (array $check): bool => $check['code'] === 'PRESERVED_NOT_SHOWN',
        ));
        self::assertSame([5, 15], array_column($preserved, 'line'));
        foreach ($preserved as $check) {
            self::assertSame('warning', $check['severity']);
            self::assertSame('review_required', $check['status']);
        }
        self::assertSame(['blocks' => 2, 'pages' => ['/kept-notes/']], (new PageInspectionService)->preservedBlocks($this->tmp));
    }

    #[Test]
    public function the_build_succeeds_hides_the_text_and_prints_one_summary_line(): void
    {
        $this->project();
        $tester = new CommandTester(ApplicationFactory::create($this->tmp)->find('build'));
        $status = $tester->execute([]);
        $output = $tester->getDisplay();

        self::assertSame(0, $status, $output);
        self::assertSame(1, substr_count($output, 'PRESERVED_NOT_SHOWN'), $output);
        self::assertStringContainsString('Preserved blocks: 2 block(s) on 1 page(s)', $output);
        $html = (string) file_get_contents($this->tmpPath('build_local/kept-notes/index.html'));
        self::assertStringContainsString('Public paragraph.', $html);
        self::assertStringNotContainsString('Secret', $html);
        self::assertStringNotContainsString('former wording', $html);
        self::assertStringNotContainsString('preserved', $html);
    }

    private function project(): void
    {
        (new PortableProjectInitializer(new Filesystem))->initialize($this->tmp, 'minimal', 'en');
        file_put_contents($this->tmpPath('content/en/kept-notes.md'), self::PAGE);
    }
}
