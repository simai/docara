<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class PageExampleTest extends TestCase
{
    private ?string $root = null;

    protected function tearDown(): void
    {
        if ($this->root !== null) {
            foreach (glob($this->root . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->root);
            $this->root = null;
        }
    }

    #[Test]
    public function a_page_example_frames_the_built_page_and_lists_its_files_as_tabs(): void
    {
        $page = $this->project(['demo.page.json' => "{\n  \"title\": \"Demo\"\n}\n"]);
        $html = (new PortableMarkdownRenderer)->render(
            "::::example {src=\"/ru/demo/\" label=\"Результат\"}\n### page.json\n\n"
            . ":::code {src=\"demo.page.json\" lang=json highlight=2}\n:::\n::::\n",
            $this->root,
            $page,
        );

        self::assertStringContainsString('<iframe src="/ru/demo/" title="Результат" data-docara-example-frame data-docara-example-page', $html);
        self::assertStringContainsString('data-docara-example-preview-resolved="page"', $html);
        self::assertStringContainsString('data-docara-example-tab="page-json"', $html);
        self::assertStringContainsString('>page.json</button>', $html);
        self::assertStringContainsString('&quot;title&quot;', $html);
        self::assertSame(1, substr_count($html, 'data-line-highlight'));
        // A built page has its own width, so the responsive check is offered.
        self::assertStringContainsString('data-docara-example-viewer', $html);
        self::assertStringNotContainsString('sandbox=', $html);
        self::assertStringNotContainsString('data-docara-srcdoc', $html);
    }

    #[Test]
    public function a_page_example_keeps_the_order_of_its_tabs(): void
    {
        $page = $this->project(['a.json' => "{}\n", 'b.md' => "# B\n"]);
        $html = (new PortableMarkdownRenderer)->render(
            "::::example {src=\"/demo/\"}\n### Second\n\n:::code {src=\"b.md\" lang=markdown}\n:::\n\n"
            . "### First\n\n:::code {src=\"a.json\" lang=json}\n:::\n::::\n",
            $this->root,
            $page,
        );

        self::assertLessThan(strpos($html, '>First</button>'), strpos($html, '>Second</button>'));
    }

    #[Test]
    public function tabs_named_without_latin_letters_get_distinct_ids(): void
    {
        $page = $this->project(['a.json' => "{}\n", 'b.json' => "[]\n"]);
        $html = (new PortableMarkdownRenderer)->render(
            "::::example {src=\"/demo/\"}\n### Значения\n\n:::code {src=\"a.json\" lang=json}\n:::\n\n"
            . "### Шаблон\n\n:::code {src=\"b.json\" lang=json}\n:::\n::::\n",
            $this->root,
            $page,
        );

        self::assertStringContainsString('data-docara-example-tab="source"', $html);
        self::assertStringContainsString('data-docara-example-tab="source-2"', $html);
        preg_match_all('/\sid="([^"]+)"/', $html, $ids);
        self::assertSame($ids[1], array_values(array_unique($ids[1])));
    }

    #[Test]
    public function a_page_example_reports_unsafe_addresses_conflicts_and_missing_sources(): void
    {
        $cases = [
            'MARKDOWN_EXAMPLE_SRC_UNSAFE' => "::::example {src=\"https://example.com/\"}\n### A\n\ntext\n::::\n",
            'MARKDOWN_EXAMPLE_SRC_CONFLICT' => "::::example {src=\"/demo/\" preview=sandbox}\n### A\n\ntext\n::::\n",
            'MARKDOWN_EXAMPLE_PAGE_SOURCE_INVALID' => "::::example {src=\"/demo/\"}\nintro\n### A\n\ntext\n::::\n",
        ];
        foreach ($cases as $code => $markdown) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("[$code] was not reported.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function a_page_example_accepts_only_code_blocks_inside(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('cannot contain another Docara or Smart block');

        (new PortableMarkdownRenderer)->render("::::example {src=\"/demo/\"}\n### A\n\n:::alert {type=info}\n#### B\n\nC\n:::\n::::\n");
    }

    /** @param array<string,string> $files */
    private function project(array $files): string
    {
        $this->root = sys_get_temp_dir() . '/docara-page-example-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->root, 0700, true));
        foreach ($files as $name => $content) {
            file_put_contents($this->root . '/' . $name, $content);
        }

        return $this->root . '/page.md';
    }
}
