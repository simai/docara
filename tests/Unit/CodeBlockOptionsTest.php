<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Markdown\AuthoringAttributeParser;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableFencedCodeRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSearchTextExtractor;

/**
 * Code block options (A2): one option syntax for fences and :::code, line
 * work done at build time, a copy that leaves out what is not code, file
 * regions, and a diagnostic for every way an option can be wrong.
 */
final class CodeBlockOptionsTest extends TestCase
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
    public function the_fence_info_string_splits_into_language_and_braced_options(): void
    {
        self::assertSame(['php', null], PortableFencedCodeRenderer::splitInfo('php'));
        self::assertSame(['', null], PortableFencedCodeRenderer::splitInfo(''));
        self::assertSame(['php', '{title="app.php" numbers}'], PortableFencedCodeRenderer::splitInfo('php {title="app.php" numbers}'));
        self::assertSame(['js', '{1,4-6}'], PortableFencedCodeRenderer::splitInfo('js{1,4-6}'));
        self::assertSame(['', '{numbers}'], PortableFencedCodeRenderer::splitInfo('{numbers}'));
        self::assertSame(['php', 'junk {numbers}'], PortableFencedCodeRenderer::splitInfo('php junk {numbers}'));
    }

    #[Test]
    public function code_options_accept_a_bare_flag_and_other_components_do_not(): void
    {
        $parser = new AuthoringAttributeParser;
        self::assertSame(
            ['highlight' => '2-3', 'numbers' => 'true', 'title' => 'app.php'],
            $parser->parse('title="app.php" highlight="2-3" numbers', 'fence'),
        );
        self::assertSame(['numbers' => '5'], $parser->parse('numbers = 5', 'fence'));
        self::assertSame(['dedent' => 'true', 'src' => 'a.php'], $parser->parse('src=a.php dedent', 'code'));
        foreach (['numbers numbers', 'numbers numbers=2'] as $source) {
            try {
                $parser->parse($source, 'fence');
                self::fail("Duplicate option [$source] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_COMPONENT_ATTRIBUTE_DUPLICATE', $exception->errorCode);
            }
        }
        try {
            $parser->parse('open', 'details');
            self::fail('A bare flag was accepted outside code.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_COMPONENT_ATTRIBUTES_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function a_fence_without_options_renders_exactly_as_before(): void
    {
        $html = (new PortableMarkdownRenderer)->render("```php\necho \"<a>\" & 1;\n```\n");

        self::assertStringContainsString(
            '<pre class="docara-code-scroll overflow-auto m-0 p-2"><code class="language-php">echo &quot;&lt;a&gt;&quot; &amp; 1;' . "\n" . '</code></pre>',
            $html,
        );
        self::assertStringNotContainsString('data-docara-code-lines', $html);
        self::assertStringNotContainsString('data-highlighted', $html);
        self::assertStringNotContainsString(PortableFencedCodeRenderer::OPTIONS_ATTRIBUTE, $html);
    }

    #[Test]
    public function a_title_replaces_the_language_in_the_header_without_wrapping_lines(): void
    {
        $html = (new PortableMarkdownRenderer)->render("```php {title=\"config/app.php\"}\nreturn [];\n```\n");

        self::assertStringContainsString('data-docara-code-title="config/app.php"', $html);
        self::assertStringContainsString('<span class="flex sf-text-1/3 weight-5">config/app.php</span>', $html);
        self::assertStringContainsString('<code class="language-php">return [];' . "\n" . '</code>', $html);
        self::assertStringNotContainsString('data-docara-code-lines', $html);
    }

    #[Test]
    public function highlight_and_numbers_are_written_into_build_time_lines(): void
    {
        $html = (new PortableMarkdownRenderer)->render("```php {highlight=\"1,3-4\" numbers=10}\na\nb\nc\nd\n```\n");

        self::assertStringContainsString('<pre class="docara-code-scroll docara-code-scroll--lines overflow-auto m-0"><code class="language-php" data-highlighted="lines" data-docara-code-lines>', $html);
        self::assertStringContainsString('data-docara-code-lines="4" data-docara-code-digits="2"', $html);
        self::assertSame(4, substr_count($html, 'class="docara-code-line"'));
        self::assertSame(3, substr_count($html, 'data-line-highlight'));
        foreach ([10, 11, 12, 13] as $number) {
            self::assertStringContainsString('<span class="docara-code-line__number" aria-hidden="true" data-line-number="' . $number . '"></span>', $html);
        }
        self::assertSame(['a', 'b', 'c', 'd'], $this->lineTexts($html));
        // A line number is generated content of an aria-hidden span: it is
        // neither text a selection copies nor text a screen reader reads.
        self::assertStringNotContainsString('>10<', $html);

        $shorthand = (new PortableMarkdownRenderer)->render("```js{1,3}\na\nb\nc\n```\n");
        self::assertSame(2, substr_count($shorthand, 'data-line-highlight'));
        self::assertStringContainsString('<code class="language-js" data-highlighted="lines"', $shorthand);
    }

    #[Test]
    public function diff_markers_become_marked_lines_with_spoken_status_and_leave_the_code(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
```js
const site = createSite();
site.build(); // [!code --]
await site.build(); // [!code ++]
# [!code ++]
<p>x</p> <!-- [!code highlight] -->
show(); // [!code focus]
```
MD);

        self::assertStringContainsString('data-docara-code-diff', $html);
        self::assertStringContainsString('data-docara-code-focus', $html);
        self::assertSame(2, substr_count($html, 'data-line-diff="add"'));
        self::assertSame(1, substr_count($html, 'data-line-diff="remove"'));
        self::assertSame(6, substr_count($html, '<span class="docara-code-line__sign" aria-hidden="true"></span>'));
        self::assertStringContainsString('<span class="docara-code-line__status sr-only" data-docara-code-diff-label="added">added: </span>', $html);
        self::assertStringContainsString('<span class="docara-code-line__status sr-only" data-docara-code-diff-label="removed">removed: </span>', $html);
        self::assertSame(1, substr_count($html, 'data-line-highlight'));
        self::assertSame(1, substr_count($html, 'data-line-focus'));
        self::assertStringNotContainsString('[!code', $html);
        self::assertSame(
            ['const site = createSite();', 'site.build();', 'await site.build();', '', '<p>x</p>', 'show();'],
            $this->lineTexts($html),
        );
    }

    #[Test]
    public function the_copy_text_leaves_out_numbers_signs_removed_lines_prompts_and_output(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $diff = $renderer->render("```php {numbers}\n\$a = 1;\n\$a = 2; // [!code --]\n\$a = 3; // [!code ++]\n```\n");
        self::assertSame("\$a = 1;\n\$a = 3;", $this->copyText($diff));

        $terminal = $renderer->render("```bash {layout=terminal}\n\$ composer install\nInstalled 3 packages.\n\$ php docara build\n```\n");
        self::assertStringContainsString('data-docara-code-layout="terminal"', $terminal);
        self::assertStringContainsString('docara-code-block docara-code-block--terminal', $terminal);
        self::assertSame(2, substr_count($terminal, '<span class="docara-code-line__prompt" aria-hidden="true"></span>'));
        self::assertSame(1, substr_count($terminal, 'data-line-output'));
        self::assertSame("composer install\nphp docara build", $this->copyText($terminal));

        // A terminal block with no prompt line is all code to copy.
        $plain = $renderer->render("```bash {layout=terminal}\nls -la\npwd\n```\n");
        self::assertSame("ls -la\npwd", $this->copyText($plain));
        self::assertStringNotContainsString('data-copy-skip', $plain);
    }

    #[Test]
    public function markers_stay_text_in_markdown_fences_and_when_escaped(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $markdown = $renderer->render("````markdown\n```js\nrun(); // [!code ++]\n```\n````\n");
        self::assertStringNotContainsString('data-line-diff', $markdown);
        self::assertStringContainsString('run(); // [!code ++]', $markdown);

        $escaped = $renderer->render("```js\nshow('[!!code ++]');\n```\n");
        self::assertSame(["show('[!code ++]');"], $this->lineTexts($escaped));
        self::assertStringNotContainsString('data-line-diff', $escaped);
    }

    #[Test]
    public function text_the_framework_would_take_for_a_shortcode_is_guarded(): void
    {
        $html = (new PortableMarkdownRenderer)->render("Write `[!code ++]` or `plain`.\n\n```markdown\n> [!NOTE]\n```\n");

        self::assertStringContainsString('<code data-sf-observer="ignore">[!code ++]</code>', $html);
        self::assertStringContainsString('<code>plain</code>', $html);
        self::assertStringContainsString('<span class="docara-code-line__content" data-sf-observer="ignore">&gt; [!NOTE]</span>', $html);
    }

    #[Test]
    public function wrong_options_fail_with_their_own_diagnostic(): void
    {
        $cases = [
            ["```php {highlight=\"5\"}\na\nb\n```\n", 'MARKDOWN_CODE_RANGE_INVALID'],
            ["```php {highlight=\"2-1\"}\na\nb\n```\n", 'MARKDOWN_CODE_RANGE_INVALID'],
            ["```php {focus=\"a-b\"}\na\n```\n", 'MARKDOWN_CODE_RANGE_INVALID'],
            ["```php {highlight=\"0\"}\na\n```\n", 'MARKDOWN_CODE_RANGE_INVALID'],
            ["```php {numbers=zero}\na\n```\n", 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID'],
            ["```php {layout=window}\na\n```\n", 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID'],
            ["```php {lines=1-2}\na\n```\n", 'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN'],
            ["```php junk {numbers}\na\n```\n", 'MARKDOWN_COMPONENT_ATTRIBUTES_INVALID'],
            ["```php {numbers} {title=x}\na\n```\n", 'MARKDOWN_COMPONENT_ATTRIBUTES_INVALID'],
            ["```php {title=\"open\n```\n", 'MARKDOWN_COMPONENT_ATTRIBUTES_INVALID'],
        ];
        foreach ($cases as [$markdown, $code]) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("Invalid code options rendered: $markdown");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode, $markdown);
            }
        }
    }

    #[Test]
    public function code_from_file_shows_a_region_with_file_line_numbers(): void
    {
        $page = $this->project([
            'builder.php' => "<?php\n\nfinal class Builder\n{\n    // #region build\n    public function build(): string\n    {\n        // #region inner\n        return 'ok';\n        // #endregion inner\n    }\n    // #endregion build\n}\n",
            'page.html' => "<!-- #region intro -->\n<p>Hi</p>\n<!-- #endregion intro -->\n",
            'script.sh' => "# region setup\nexport A=1\n# endregion\n",
        ]);
        $renderer = new PortableMarkdownRenderer;

        $html = $renderer->render(":::code {src=builder.php region=build dedent=true numbers highlight=3}\n:::\n", $this->root, $page);
        self::assertSame(['public function build(): string', '{', "    return 'ok';", '}'], $this->lineTexts($html));
        self::assertStringContainsString('data-line-number="6"', $html);
        self::assertStringContainsString('data-line-number="11"', $html);
        self::assertSame(1, substr_count($html, 'data-line-highlight'));
        self::assertStringContainsString('data-docara-code-title="builder.php"', $html);
        self::assertStringNotContainsString('#region', $html);

        $indented = $renderer->render(":::code {src=builder.php region=inner}\n:::\n", $this->root, $page);
        self::assertStringContainsString("<code class=\"language-php\">        return 'ok';\n</code>", $indented);

        $markup = $renderer->render(":::code {src=page.html region=intro}\n:::\n", $this->root, $page);
        self::assertStringContainsString("<code class=\"language-html\">&lt;p&gt;Hi&lt;/p&gt;\n</code>", $markup);

        $shell = $renderer->render(":::code {src=script.sh region=setup lang=bash}\n:::\n", $this->root, $page);
        self::assertStringContainsString("<code class=\"language-bash\">export A=1\n</code>", $shell);

        $between = $renderer->render(":::code {src=builder.php start_after=\"#region inner\" end_before=\"#endregion inner\" dedent}\n:::\n", $this->root, $page);
        self::assertStringContainsString("<code class=\"language-php\">return 'ok';\n</code>", $between);

        $lines = $renderer->render(":::code {src=builder.php lines=6-7 numbers}\n:::\n", $this->root, $page);
        self::assertStringContainsString('data-line-number="6"', $lines);
        self::assertStringContainsString('data-line-number="7"', $lines);
    }

    #[Test]
    public function code_from_file_reports_unknown_regions_and_conflicting_selections(): void
    {
        $page = $this->project(['a.php' => "<?php\n// #region open\necho 1;\n"]);
        $cases = [
            [':::code {src=a.php region=missing}', 'MARKDOWN_CODE_REGION_UNKNOWN'],
            [':::code {src=a.php region=open}', 'MARKDOWN_CODE_REGION_UNKNOWN'],
            [':::code {src=a.php start_after="nothing like this"}', 'MARKDOWN_CODE_REGION_UNKNOWN'],
            [':::code {src=a.php end_before="nothing like this"}', 'MARKDOWN_CODE_REGION_UNKNOWN'],
            [':::code {src=a.php start_after="echo" end_before="echo"}', 'MARKDOWN_CODE_REGION_UNKNOWN'],
            [':::code {src=a.php start_after="region" end_before="echo"}', 'MARKDOWN_CODE_RANGE_INVALID'],
            [':::code {src=a.php lines=1-2 region=open}', 'MARKDOWN_CODE_OPTIONS_CONFLICT'],
            [':::code {src=a.php region=open start_after="x"}', 'MARKDOWN_CODE_OPTIONS_CONFLICT'],
            [':::code {src=a.php lines=1 end_before="echo"}', 'MARKDOWN_CODE_OPTIONS_CONFLICT'],
            [':::code {src=a.php highlight=9}', 'MARKDOWN_CODE_RANGE_INVALID'],
            [':::code {src=a.php layout=window}', 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID'],
            [':::code {src=a.php dedent=maybe}', 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID'],
            [':::code {src=a.php region="a b"}', 'MARKDOWN_CODE_REGION_UNKNOWN'],
        ];
        foreach ($cases as [$call, $code]) {
            try {
                (new PortableMarkdownRenderer)->render($call . "\n:::\n", $this->root, $page);
                self::fail("Invalid code block rendered: $call");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode, $call);
            }
        }
    }

    #[Test]
    public function the_same_source_builds_to_the_same_bytes(): void
    {
        $page = $this->project(['a.php' => "<?php\n// #region one\n  echo 1; // [!code ++]\n// #endregion one\n"]);
        $markdown = "```php {title=\"x.php\" highlight=\"1\" numbers focus=2}\na\nb // [!code --]\n```\n\n"
            . "```bash {layout=terminal}\n\$ ls\nout\n```\n\n:::code {src=a.php region=one dedent numbers}\n:::\n";

        $first = (new PortableMarkdownRenderer)->render($markdown, $this->root, $page);
        $second = (new PortableMarkdownRenderer)->render($markdown, $this->root, $page);

        self::assertSame($first, $second);
        self::assertSame(3, substr_count($first, 'data-docara-code-lines='));
    }

    #[Test]
    public function search_reads_build_time_lines_as_separate_words_without_status_text(): void
    {
        $html = (new PortableMarkdownRenderer)->render("```js\nfirst(); // [!code --]\nsecond(); // [!code ++]\n```\n");
        $text = (new PortableSearchTextExtractor)->extract($html, [])['text'];

        self::assertStringContainsString('first(); second();', $text);
        self::assertStringNotContainsString('added', $text);
        self::assertStringNotContainsString('removed', $text);
    }

    /** @param array<string, string> $files @return string the page file path */
    private function project(array $files): string
    {
        $this->root = sys_get_temp_dir() . '/docara-code-options-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->root, 0700, true));
        foreach ($files as $name => $content) {
            file_put_contents($this->root . '/' . $name, $content);
        }

        return $this->root . '/page.md';
    }

    /** @return list<string> */
    private function lineTexts(string $html): array
    {
        return array_map(
            static fn (DOMElement $line): string => (string) $line->textContent,
            $this->query($html, '//span[contains(concat(" ", @class, " "), " docara-code-line__content ")]'),
        );
    }

    /**
     * The copy rule of declarative-shell.js (codeCopyText): every line not
     * marked data-copy-skip, its content only, joined by newlines.
     */
    private function copyText(string $html): string
    {
        $lines = [];
        foreach ($this->query($html, '//code[@data-docara-code-lines]/span[not(@data-copy-skip)]/span[contains(concat(" ", @class, " "), " docara-code-line__content ")]') as $content) {
            $lines[] = (string) $content->textContent;
        }

        return implode("\n", $lines);
    }

    /** @return list<DOMElement> */
    private function query(string $html, string $expression): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $nodes = [];
        foreach ((new DOMXPath($document))->query($expression) ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }
}
