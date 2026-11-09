<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\PortableInlineCodeRenderer;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

/**
 * The article layout: nothing around the article captures the fixed popups
 * of inline examples, and long inline code never widens a phone page.
 */
final class ArticleLayoutContractTest extends TestCase
{
    private function shellCss(): string
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css');
        self::assertIsString($css);

        return $css;
    }

    #[Test]
    public function no_element_around_the_article_is_a_size_container(): void
    {
        $css = $this->shellCss();
        $template = file_get_contents(dirname(__DIR__, 2) . '/resources/publisher/templates/page.php');
        self::assertIsString($template);

        // A size container is the containing block of position:fixed
        // descendants where a browser gives it layout containment, so a
        // Framework popup in an inline example would open far from its field.
        self::assertStringNotContainsString('container-type', $css);
        self::assertDoesNotMatchRegularExpression('/[{;]\s*container\s*:/', $css);
        self::assertStringNotContainsString('container-type', $template);
        // Nothing reads container units or queries the main region.
        self::assertDoesNotMatchRegularExpression('/\d(?:cqw|cqi|cqh|cqb|cqmin|cqmax)\b/', $css);
        self::assertStringNotContainsString('@container', $css);
        // Nor does anything else around the article make it a containing block.
        self::assertDoesNotMatchRegularExpression(
            '/(?:\[data-docara-region="main"\]|\.docara-landing|\.docara-content|\.docara-prose)\{[^}]*(?:contain:|transform:|will-change|filter:|perspective:)/',
            $css,
        );
    }

    #[Test]
    public function full_width_blocks_reach_the_window_on_a_landing_and_the_column_in_the_docs_layout(): void
    {
        $css = $this->shellCss();

        self::assertStringContainsString(
            '.docara-landing .docara-content>[data-docara-width="full"],.docara-landing .docara-content>[data-docara-section][data-docara-region-owner="main"]>[data-docara-width="full"]{box-sizing:border-box;width:100vw;margin-inline:calc(50% - 50vw)}',
            $css,
        );
        // A classic scrollbar makes 100vw wider than the page: the landing
        // clips the excess instead of scrolling sideways.
        self::assertStringContainsString('.docara-landing{display:block;min-height:auto;overflow-x:clip}', $css);
        self::assertStringContainsString(
            '.docara-docs-layout [data-docara-region="main"]>.docara-prose>[data-docara-width="full"],.docara-docs-layout [data-docara-region="main"]>.docara-prose>[data-docara-section][data-docara-region-owner="main"]>[data-docara-width="full"]{box-sizing:border-box;width:100%;margin-inline:0}',
            $css,
        );
    }

    #[Test]
    public function inline_code_stays_on_one_line_unless_the_build_marks_it_long(): void
    {
        $css = $this->shellCss();

        self::assertStringContainsString(
            '.docara-prose :not(pre)>code:not(:where(.docara-example-inline *)){white-space:nowrap;hyphens:none}',
            $css,
        );
        // Only a marked piece wraps, never inside a live example or a table.
        self::assertStringContainsString(
            '.docara-prose :not(pre)>code[data-docara-code-wrap]:not(:where(.docara-example-inline *,table *)){white-space:normal;overflow-wrap:anywhere}',
            $css,
        );
        self::assertStringNotContainsString('.docara-prose code{', $css);
        self::assertStringNotContainsString('.docara-prose :not(pre)>code{', $css);
    }

    #[Test]
    public function the_build_marks_inline_code_longer_than_the_prose_or_heading_limit(): void
    {
        $exact = str_repeat('a', PortableInlineCodeRenderer::PROSE_LIMIT);
        $html = (new PortableMarkdownRenderer)->render(
            "## Heading `on-surface-variant` and `icon-start`\n\n"
            . "Short `icon-start`, exact `{$exact}`, long `resources/portable/declarative-shell.css`"
            . " and a long shortcode `[!icon name=\"home-regular-with-a-long-name\"]`.\n\n"
            . "```\nresources/portable/declarative-shell.css resources/portable/declarative-shell.js\n```\n",
        );

        self::assertStringContainsString('<code data-docara-code-wrap>on-surface-variant</code>', $html);
        self::assertStringContainsString('and <code>icon-start</code></h2>', $html);
        self::assertStringContainsString('Short <code>icon-start</code>', $html);
        self::assertStringContainsString('exact <code>' . $exact . '</code>', $html);
        self::assertStringContainsString('<code data-docara-code-wrap>resources/portable/declarative-shell.css</code>', $html);
        // The shortcode guard still reaches a marked piece.
        self::assertStringContainsString(
            '<code data-docara-code-wrap data-sf-observer="ignore">[!icon name=&quot;home-regular-with-a-long-name&quot;]</code>',
            $html,
        );
        // A code block is never marked: it scrolls or wraps on its own.
        self::assertDoesNotMatchRegularExpression('/<pre[^>]*>\s*<code[^>]*data-docara-code-wrap/', $html);

        self::assertFalse(PortableInlineCodeRenderer::wraps($exact));
        self::assertTrue(PortableInlineCodeRenderer::wraps($exact . 'a'));
        self::assertFalse(PortableInlineCodeRenderer::wraps(str_repeat('я', 16), true));
        self::assertTrue(PortableInlineCodeRenderer::wraps(str_repeat('я', 17), true));
    }
}
