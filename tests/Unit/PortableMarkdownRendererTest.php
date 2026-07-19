<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class PortableMarkdownRendererTest extends TestCase
{
    #[Test]
    public function it_renders_framework_utility_recipes_and_native_markdown_elements(): void
    {
        $markdown = <<<'MD'
:::card
### Один источник истины

Карточка остаётся обычным семантическим содержимым.
:::

:::steps
1. Установите PHP-зависимости.
2. Запустите переносимую сборку.
:::

| Возможность | Статус |
| --- | --- |
| PHP-only | Готово |

```php
echo 'Docara';
```
MD;

        $html = (new PortableMarkdownRenderer)->render($markdown);

        self::assertStringContainsString(
            '<section class="bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1">',
            $html,
        );
        self::assertStringContainsString(
            '<ol class="flex flex-col gap-2 p-inline-start-3">',
            $html,
        );
        self::assertStringContainsString(
            '<div class="overflow-auto"><table class="table table-border table-stripe">',
            $html,
        );
        self::assertStringContainsString(
            '<div data-docara-code-block class="source docara-code-block bg-surface-container border border-outline-variant radius-2 m-0">',
            $html,
        );
        self::assertStringNotContainsString('data-docara-code-language', $html);
        self::assertStringNotContainsString('data-docara-code-copy', $html);
        self::assertStringContainsString(
            '<pre class="docara-code-scroll overflow-auto m-0 p-2"><code class="language-php">',
            $html,
        );
        self::assertSame(1, substr_count($html, 'data-docara-code-block'));
        self::assertSame(1, substr_count(
            strstr($html, '<div data-docara-code-block') ?: '',
            ' border ',
        ));
        self::assertStringNotContainsString('docara-card', $html);
        self::assertStringNotContainsString('docara-steps', $html);
    }

    #[Test]
    public function fenced_code_has_one_framework_owned_surface_and_preserves_source_text(): void
    {
        $markdown = <<<'MD'
Before `inline`.

```shell
printf '<Docara> & exact'
  second line
```
MD;

        $html = (new PortableMarkdownRenderer)->render($markdown);

        self::assertSame(1, substr_count($html, '<div data-docara-code-block'));
        self::assertSame(1, substr_count($html, ' border border-outline-variant'));
        self::assertStringContainsString('class="source docara-code-block', $html);
        self::assertStringNotContainsString('data-docara-code-language', $html);
        self::assertStringNotContainsString('data-docara-code-copy', $html);
        self::assertStringContainsString(
            "<code class=\"language-shell\">printf '&lt;Docara&gt; &amp; exact'\n  second line\n</code>",
            $html,
        );
        self::assertStringNotContainsString('<pre class="bg-surface-container', $html);
        self::assertStringContainsString('<p>Before <code>inline</code>.</p>', $html);
    }

    #[Test]
    public function it_renders_typed_landing_recipes_with_native_links_and_framework_utilities(): void
    {
        $markdown = <<<'MD'
:::cta
[Начать работу](/start/ "Быстрый старт")
:::

:::features
- **Пишите в Markdown.** Страницы и код хранятся рядом с проектом.
- *Настраивайте через [JSON](/json/).* Макет наследуется и проверяется схемой.
- **Собирайте на `PHP`.** ~~Случайность~~ исключена.
:::
MD;

        $html = (new PortableMarkdownRenderer)->render($markdown);

        self::assertStringContainsString(
            '<a data-docara-block="cta" class="docara-cta-link sf-button sf-button--default sf-button--primary sf-button--size-1 bg-primary color-on-primary p-1/2 line-none radius-default inline-flex items-center content-main-center decoration-none w-full sm:w-auto sm:self-start" href="/start/" title="Быстрый старт"><span class="sf-button-text-container">Начать работу</span></a>',
            $html,
        );
        self::assertStringContainsString(
            '<ul data-docara-block="features" class="docara-feature-grid grid grid-col-1 lg:grid-col-3 gap-2 list-none m-0 p-0">',
            $html,
        );
        self::assertSame(3, substr_count(
            $html,
            '<li class="bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1">',
        ));
        self::assertStringNotContainsString('<sf-button', $html);
        self::assertStringNotContainsString('docara-feature-card', $html);
    }

    #[Test]
    public function columns_render_two_to_four_source_ordered_regions_with_exact_framework_layout(): void
    {
        $cases = [
            2 => 'grid grid-col-1 md:grid-col-2 gap-2',
            3 => 'grid grid-col-1 md:grid-col-2 lg:grid-col-3 gap-2',
            4 => 'grid grid-col-1 md:grid-col-2 lg:grid-col-4 gap-2',
        ];

        foreach ($cases as $count => $classes) {
            $regions = [];
            for ($region = 1; $region <= $count; $region++) {
                $regions[] = "### Region {$region}\n\nSource ordered content {$region}.";
            }
            $markdown = ":::columns\n"
                . implode("\n\n---\n\n", $regions)
                . "\n:::\n";

            $renderer = new PortableMarkdownRenderer;
            $first = $renderer->render($markdown);
            $second = $renderer->render($markdown);

            self::assertSame($first, $second, "{$count} columns must render deterministically.");
            self::assertStringContainsString(
                '<section data-docara-block="columns" data-docara-columns="' . $count
                . '" class="' . $classes . '">',
                $first,
            );
            self::assertSame($count, substr_count($first, '<div class="min-w-0">'));
            self::assertStringNotContainsString('<hr', $first);
            $cursor = -1;
            for ($region = 1; $region <= $count; $region++) {
                $position = strpos($first, "Region {$region}");
                self::assertIsInt($position);
                self::assertGreaterThan($cursor, $position, 'Column regions must preserve source order.');
                $cursor = $position;
            }
        }
    }

    #[Test]
    public function columns_use_only_exact_top_level_commonmark_thematic_breaks_as_separators(): void
    {
        $markdown = <<<'MD'
:::columns
### First region

```text
---
```

> ---

- Before

  ---

  After

---

### Second region

The exact root separator is consumed.
:::
MD;

        $html = (new PortableMarkdownRenderer)->render($markdown);

        self::assertStringContainsString('data-docara-columns="2"', $html);
        self::assertSame(2, substr_count($html, '<div class="min-w-0">'));
        self::assertStringContainsString("class=\"language-text\">---\n</code>", $html);
        self::assertStringContainsString('<blockquote>', $html);
        self::assertStringContainsString('<ul>', $html);
    }

    #[Test]
    public function columns_fail_closed_for_invalid_region_and_separator_contracts(): void
    {
        $cases = [
            [
                ":::columns\nOnly one region.\n:::\n",
                'MARKDOWN_COLUMNS_REGION_COUNT_INVALID',
            ],
            [
                ":::columns\nOne\n\n***\n\nTwo\n:::\n",
                'MARKDOWN_COLUMNS_REGION_COUNT_INVALID',
            ],
            [
                ":::columns\n---\n\nTwo\n:::\n",
                'MARKDOWN_COLUMNS_REGION_EMPTY',
            ],
            [
                ":::columns\nOne\n\n---\n\n---\n\nThree\n:::\n",
                'MARKDOWN_COLUMNS_REGION_EMPTY',
            ],
            [
                ":::columns\nOne\n\n---\nTwo\n:::\n",
                'MARKDOWN_COLUMNS_SEPARATOR_INVALID',
            ],
            [
                ":::columns\nOne\n---\n\nTwo\n:::\n",
                'MARKDOWN_COLUMNS_REGION_COUNT_INVALID',
            ],
            [
                ":::columns\nOne\n\n--- \n\nTwo\n:::\n",
                'MARKDOWN_COLUMNS_REGION_COUNT_INVALID',
            ],
            [
                ":::columns\n<!-- stripped -->\n\n---\n\nTwo\n:::\n",
                'MARKDOWN_COLUMNS_REGION_EMPTY',
            ],
            [
                ":::columns\nOne\n\n---\n\nTwo\n\n---\n\nThree\n\n---\n\nFour\n\n---\n\nFive\n:::\n",
                'MARKDOWN_COLUMNS_REGION_COUNT_INVALID',
            ],
        ];

        foreach ($cases as [$markdown, $expected]) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("Invalid columns unexpectedly rendered for [$expected].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function columns_share_page_level_references_without_per_region_syntax(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::columns
[First][guide]

---

[Second][guide]
:::

[guide]: /guide/ "Guide"
MD);

        self::assertSame(2, substr_count($html, 'href="/guide/" title="Guide"'));
        self::assertStringNotContainsString('<hr', $html);
    }

    #[Test]
    public function columns_reject_nested_typed_and_smart_directives(): void
    {
        foreach (['card', 'ui.alert'] as $inner) {
            $innerBody = $inner === 'card' ? "Nested card\n" : "{}\n";

            try {
                (new PortableMarkdownRenderer)->render(
                    "::::columns\n"
                    . "First region\n\n---\n\nSecond region\n\n"
                    . ":::{$inner}\n{$innerBody}:::\n"
                    . "::::\n",
                );
                self::fail("Nested [$inner] unexpectedly survived [columns].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_BLOCK_NESTING_UNSUPPORTED', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function cta_requires_exactly_one_safe_markdown_link(): void
    {
        $cases = [
            [":::cta\nPlain text\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
            [":::cta\n[One](/one/) and [Two](/two/)\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
            [":::cta\nBefore [One](/one/)\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
            [":::cta\n[Unsafe](javascript:alert(1))\n:::\n", 'MARKDOWN_CTA_LINK_UNSAFE'],
            [":::cta\n[Unsafe](data:image/png;base64,AA)\n:::\n", 'MARKDOWN_CTA_LINK_UNSAFE'],
            [":::cta\n[![Image](/image.png)](/start/)\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
            [":::cta\n[`Inline code`](/start/)\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
            [":::cta\n[Text <span>HTML</span>](/start/)\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
            [":::cta\n[\u{00A0}](/empty/)\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
            [":::cta\n[\u{200B}](/empty/)\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
            [":::cta\n[\u{FE0F}](/empty/)\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
            [":::cta\n[\u{0301}](/empty/)\n:::\n", 'MARKDOWN_CTA_LINK_REQUIRED'],
        ];

        foreach ($cases as [$markdown, $expected]) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("Invalid CTA unexpectedly rendered for [$expected].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function features_requires_one_flat_unordered_list_with_two_to_six_items(): void
    {
        $cases = [
            [":::features\n1. Ordered\n2. List\n:::\n", 'MARKDOWN_FEATURES_UNORDERED_LIST_REQUIRED'],
            [":::features\nPlain text\n:::\n", 'MARKDOWN_FEATURES_UNORDERED_LIST_REQUIRED'],
            [":::features\n- Only one\n:::\n", 'MARKDOWN_FEATURES_ITEM_COUNT_INVALID'],
            [":::features\n- \n- Text\n:::\n", 'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID'],
            [":::features\n- [ ](/empty/)\n- Text\n:::\n", 'MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED'],
            [":::features\n- [\u{00A0}](/empty/)\n- Text\n:::\n", 'MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED'],
            [":::features\n- [\u{200B}](/empty/)\n- Text\n:::\n", 'MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED'],
            [":::features\n- [\u{FE0F}](/empty/)\n- Text\n:::\n", 'MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED'],
            [":::features\n- [\u{0301}](/empty/)\n- Text\n:::\n", 'MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED'],
            [":::features\n- One\n  - Nested\n- Two\n:::\n", 'MARKDOWN_FEATURES_UNORDERED_LIST_REQUIRED'],
            [":::features\n- First paragraph\n\n  Second paragraph\n- Two\n:::\n", 'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID'],
            [":::features\n- ![Image only](/image.png)\n- Two\n:::\n", 'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID'],
            [":::features\n- First <span>inline HTML</span>\n- Two\n:::\n", 'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID'],
            [":::features\n- First\n\n      indented code\n- Two\n:::\n", 'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID'],
            [
                ":::features\n- One\n- Two\n- Three\n- Four\n- Five\n- Six\n- Seven\n:::\n",
                'MARKDOWN_FEATURES_ITEM_COUNT_INVALID',
            ],
        ];

        foreach ($cases as [$markdown, $expected]) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("Invalid feature list unexpectedly rendered for [$expected].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function directives_inside_code_fences_remain_code(): void
    {
        $html = (new PortableMarkdownRenderer)->render("```text\n:::card\nText\n\n:::\n```\n");

        self::assertStringContainsString(':::card', $html);
        self::assertStringNotContainsString('<section', $html);
    }

    #[Test]
    public function directives_inside_html_comments_are_stripped_without_being_executed(): void
    {
        $html = (new PortableMarkdownRenderer)->render("<!--\n:::card\nBody\n\n:::\n-->\n");

        self::assertStringNotContainsString('Body', $html);
        self::assertStringNotContainsString('<section', $html);
    }

    #[Test]
    public function directives_inside_list_contained_fences_remain_code(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            "- ```markdown\n  :::card\n  Example only.\n  :::\n  ```\n",
        );

        self::assertStringContainsString(':::card', $html);
        self::assertStringNotContainsString('<section', $html);
    }

    #[Test]
    public function commonmark_container_state_decides_where_a_list_fence_closes(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            "- ```markdown\n  :::card\n  First\n```\n:::card\nSecond\n\n:::\n",
        );

        self::assertStringContainsString(':::card', $html);
        self::assertStringNotContainsString('<section', $html);
    }

    #[Test]
    public function fenced_examples_inside_blocks_do_not_close_or_nest_the_block(): void
    {
        $markdown = <<<'MD'
:::card
```markdown
:::steps
1. Example only.
:::
```
:::
MD;

        $html = (new PortableMarkdownRenderer)->render($markdown);

        self::assertSame(1, substr_count($html, '<section'));
        self::assertStringContainsString(':::steps', $html);
        self::assertStringContainsString('Example only.', $html);
    }

    #[Test]
    public function legacy_closing_fences_accept_zero_to_three_spaces_and_crlf(): void
    {
        foreach (['', ' ', '  ', '   '] as $indent) {
            $html = (new PortableMarkdownRenderer)->render(
                ":::card\r\nLegacy body\r\n{$indent}:::\r\n",
            );

            self::assertSame(1, substr_count($html, '<section'));
            self::assertStringContainsString('Legacy body', $html);
        }
    }

    #[Test]
    public function longer_outer_fence_can_contain_a_literal_short_fence(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
::::card
Before
:::
After
::::
MD);

        self::assertSame(1, substr_count($html, '<section'));
        self::assertStringContainsString("Before\n:::\nAfter", $html);
    }

    #[Test]
    public function a_longer_outer_fence_handles_raw_html_with_a_short_fence(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
::::card
<div>
:::
</div>
::::
MD);

        self::assertSame(1, substr_count($html, '<section'));
    }

    #[Test]
    public function directive_like_text_inside_multiline_inline_code_and_reference_titles_is_literal(): void
    {
        $inline = (new PortableMarkdownRenderer)->render("Prefix ``\n:::card\n`` suffix\n");
        self::assertStringContainsString('<code>:::card</code>', $inline);
        self::assertStringNotContainsString('<section', $inline);

        $reference = (new PortableMarkdownRenderer)->render(<<<'MD'
[a\]b]: /guide/ "
:::card
"
[Guide][a\]b]
MD);
        self::assertStringContainsString('href="/guide/"', $reference);
        self::assertStringNotContainsString('<section', $reference);
    }

    #[Test]
    public function unmatched_inline_tokens_in_separate_blocks_do_not_hide_a_legacy_close(): void
    {
        foreach ([
            ":::card\n# Heading `\n:::\nLater `\n",
            ":::card\nEscaped \\`\n:::\nAfter \\`\n",
            ":::card\n# Heading <!--\n:::\nLater -->\n",
        ] as $markdown) {
            $html = (new PortableMarkdownRenderer)->render($markdown);
            self::assertSame(1, substr_count($html, '<section'));
        }
    }

    #[Test]
    public function indented_root_openers_remain_literal_but_lazy_container_openers_fail(): void
    {
        foreach ([' ', '  ', '   '] as $indent) {
            $html = (new PortableMarkdownRenderer)->render("{$indent}:::card\nBody\n{$indent}:::\n");
            self::assertStringNotContainsString('<section', $html);
            self::assertStringContainsString(':::card', $html);
        }

        foreach ([
            "- Before\n::::card\nBody\n::::\n",
            "1. Before\n::::card\nBody\n::::\n",
            "> Before\n::::card\nBody\n::::\n",
        ] as $markdown) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail('A lazy-container directive unexpectedly escaped its CommonMark parent.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_BLOCK_INDENTATION_UNSUPPORTED', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function a_top_level_directive_after_a_closed_list_is_recognized(): void
    {
        foreach (["\n\n", "\n\n\n"] as $separator) {
            $html = (new PortableMarkdownRenderer)->render(
                "- Before{$separator}:::card\nAfter list\n:::\n",
            );

            self::assertSame(1, substr_count($html, '<section'));
            self::assertStringContainsString('After list', $html);
        }
    }

    #[Test]
    public function custom_boundaries_do_not_hide_list_contained_fences_inside_a_block(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::card
2. ```markdown
   :::steps
   1. Example only.
   :::
   ```

:::
MD);

        self::assertSame(1, substr_count($html, '<section'));
        self::assertStringContainsString(':::steps', $html);
        self::assertStringContainsString('Example only.', $html);
    }

    #[Test]
    public function custom_boundaries_preserve_following_html_block_opacity(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            ":::card\nFirst\n\n:::\n<span>\n:::card\nSecond\n\n:::\n</span>\n",
        );

        self::assertSame(1, substr_count($html, '<section'));
        self::assertStringNotContainsString('Second', $html);
    }

    #[Test]
    public function four_space_indented_directives_remain_commonmark_code(): void
    {
        $html = (new PortableMarkdownRenderer)->render("    :::card\n    Text\n    :::\n");

        self::assertStringContainsString(':::card', $html);
        self::assertStringContainsString('<pre', $html);
        self::assertStringNotContainsString('<section', $html);
    }

    #[Test]
    public function block_directives_create_valid_boundaries_between_paragraphs(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            "Intro\n\n:::card\nBody\n\n:::\n\nOutro\n",
        );

        self::assertStringContainsString("<p>Intro</p>\n<section", $html);
        self::assertStringContainsString("</section>\n<p>Outro</p>", $html);
        self::assertStringNotContainsString("<p>Intro\n<section", $html);
    }

    #[Test]
    public function indented_blocks_fail_instead_of_being_reparented_out_of_a_list(): void
    {
        try {
            (new PortableMarkdownRenderer)->render(
                "- Before\n  :::card\n  Body\n  :::\n  After\n",
            );
            self::fail('Indented block unexpectedly changed its CommonMark container.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_BLOCK_INDENTATION_UNSUPPORTED', $exception->errorCode);
        }
    }

    #[Test]
    public function card_can_use_page_level_reference_definitions(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            ":::card\n[Открыть руководство][guide]\n\n:::\n\n[guide]: /guide/\n",
        );

        self::assertStringContainsString('<a href="/guide/">Открыть руководство</a>', $html);
    }

    #[Test]
    public function card_reuses_commonmark_multiline_titles_and_escaped_reference_labels(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::card
[Guide][guide] and [Escaped][a\]b].

:::

[guide]: /guide/
  "Long title"
[a\]b]: /escaped/
MD);

        self::assertStringContainsString('<a href="/guide/" title="Long title">Guide</a>', $html);
        self::assertStringContainsString('<a href="/escaped/">Escaped</a>', $html);
    }

    #[Test]
    public function reference_definitions_inside_a_block_remain_document_wide(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::card
[shared]: /inside/
[Inside][shared]

:::

[Outside][shared]
MD);

        self::assertSame(2, substr_count($html, 'href="/inside/"'));
        self::assertStringContainsString('>Outside</a>', $html);
    }

    #[Test]
    public function extracted_references_remain_available_before_an_unclosed_opaque_tail(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::card
[Inside][shared]

[shared]: /inside/

:::

[Outside][shared]

```text
unclosed example
MD);

        self::assertSame(2, substr_count($html, 'href="/inside/"'));
        self::assertStringContainsString('unclosed example', $html);
    }

    #[Test]
    public function author_text_cannot_collide_with_a_generated_block_placeholder(): void
    {
        $body = 'Body';
        $startLine = 4;
        $oldPlaceholder = 'DOCARA_MARKDOWN_BLOCK_' . strtoupper(substr(hash(
            'sha256',
            'card' . "\0" . $startLine . "\0" . $body . "\0" . 0,
        ), 0, 24));
        $markdown = "```text\n{$oldPlaceholder}\n```\n:::card\n{$body}\n\n:::\n";

        $html = (new PortableMarkdownRenderer)->render($markdown);

        self::assertStringContainsString($oldPlaceholder, $html);
        self::assertSame(1, substr_count($html, '<section'));
    }

    #[Test]
    public function entity_equivalent_block_placeholder_fails_closed(): void
    {
        $body = 'Body';
        $placeholder = 'DOCARA_MARKDOWN_BLOCK_' . strtoupper(substr(hash(
            'sha256',
            'card' . "\0" . 3 . "\0" . $body . "\0" . 0,
        ), 0, 24));
        $entityEquivalent = str_replace('_', '&#95;', $placeholder);

        try {
            (new PortableMarkdownRenderer)->render(
                "{$entityEquivalent}\n\n:::card\n{$body}\n\n:::\n",
            );
            self::fail('Entity-equivalent block placeholder unexpectedly hydrated.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_BLOCK_PLACEHOLDER_CARDINALITY_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function steps_require_exactly_one_root_ordered_list(): void
    {
        foreach ([
            ":::steps\nUse an unordered body.\n\n:::\n",
            ":::steps\n1. First\n\nParagraph\n\n:::\n",
            ":::steps\n1. First\n\n---\n\n1. Second\n\n:::\n",
        ] as $markdown) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail('Invalid steps body unexpectedly rendered.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_STEPS_ORDERED_LIST_REQUIRED', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function unclosed_and_nested_blocks_fail_closed(): void
    {
        foreach ([
            ":::card\nText\n",
            ":::card\n:::steps\n1. Nested\n\n:::\n:::\n",
        ] as $markdown) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail('Invalid Markdown block unexpectedly rendered.');
            } catch (PortableConfigurationException $exception) {
                self::assertContains(
                    $exception->errorCode,
                    ['MARKDOWN_BLOCK_UNCLOSED', 'MARKDOWN_BLOCK_NESTING_UNSUPPORTED'],
                );
            }
        }
    }

    #[Test]
    public function portable_blocks_reject_smart_components_instead_of_rendering_them_as_text(): void
    {
        foreach (['card', 'steps', 'cta', 'features'] as $outer) {
            $body = $outer === 'steps' ? "1. First step\n" : "Card body\n";
            if ($outer === 'cta') {
                $body = "[Start](/start/)\n";
            } elseif ($outer === 'features') {
                $body = "- First\n- Second\n";
            }
            foreach (['ui.button', 'ui.alert'] as $inner) {
                try {
                    (new PortableMarkdownRenderer)->render(
                        "::::{$outer}\n{$body}:::{$inner}\n{}\n:::\n::::\n",
                    );
                    self::fail("Nested [$inner] unexpectedly survived [$outer].");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame('MARKDOWN_BLOCK_NESTING_UNSUPPORTED', $exception->errorCode);
                }
            }
        }
    }

    #[Test]
    public function steps_rejects_nested_portable_blocks_with_the_nesting_error(): void
    {
        foreach (['card', 'steps', 'cta', 'features'] as $inner) {
            try {
                (new PortableMarkdownRenderer)->render(
                    "::::steps\n1. First step\n:::{$inner}\nNested\n:::\n::::\n",
                );
                self::fail("Nested [$inner] unexpectedly survived [steps].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_BLOCK_NESTING_UNSUPPORTED', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function invalid_utf8_fails_with_the_portable_markdown_error_contract(): void
    {
        try {
            (new PortableMarkdownRenderer)->render("\xFF\n:::card\nText\n\n:::\n");
            self::fail('Invalid UTF-8 unexpectedly reached CommonMark.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_BLOCK_INPUT_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function source_directive_marker_count_is_bounded_before_extraction(): void
    {
        $markdown = str_repeat(":::card\nBody\n:::\n", 65);

        try {
            (new PortableMarkdownRenderer)->render($markdown);
            self::fail('An oversized directive source unexpectedly reached extraction.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_BLOCK_LIMIT_EXCEEDED', $exception->errorCode);
        }
    }

    #[Test]
    public function combined_directive_marker_count_is_bounded_before_cross_family_parsing(): void
    {
        $portable = ":::card\nBody\n:::\n";
        $framework = ":::ui.button\n{}\n:::\n";
        $cases = [
            [
                str_repeat($portable, 32) . str_repeat($framework, 33),
                'FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED',
            ],
            [
                str_repeat($framework, 32) . str_repeat($portable, 33),
                'MARKDOWN_BLOCK_LIMIT_EXCEEDED',
            ],
        ];

        foreach ($cases as [$markdown, $expected]) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("A mixed-family directive overflow unexpectedly rendered for [$expected].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function very_long_matching_fences_do_not_become_dynamic_regex_quantifiers(): void
    {
        foreach ([65535, 65536] as $length) {
            $fence = str_repeat(':', $length);
            $html = (new PortableMarkdownRenderer)->render("{$fence}card\nBody\n{$fence}\n");

            self::assertSame(1, substr_count($html, '<section'));
        }
    }
}
