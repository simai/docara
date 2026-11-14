<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Document\SourceLocation;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class PortableMarkdownRendererTest extends TestCase
{
    #[Test]
    public function raw_html_fails_closed_while_sandboxed_html_and_example_sources_remain_explicit(): void
    {
        $renderer = new PortableMarkdownRenderer;
        foreach ([
            "# Unsafe\n\n<script>alert(1)</script>\n",
            "Text <span data-secret=\"x\">unsafe</span>.\n",
        ] as $markdown) {
            try {
                $renderer->render($markdown, null, 'content/en/security.md');
                self::fail('Unsafe raw HTML unexpectedly rendered.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_RAW_HTML_FORBIDDEN', $exception->errorCode);
                self::assertStringContainsString('content/en/security.md:', $exception->getMessage());
            }
        }

        $inlineCode = $renderer->render(
            'Use `<page>.page.json` and ``<literal>`` as code.',
            '/workspace',
            '/workspace/content/en/security.md',
        );
        self::assertStringContainsString('&lt;page&gt;.page.json', $inlineCode);
        try {
            $renderer->render('<script>unsafe</script>', '/workspace', '/workspace/content/en/security.md');
            self::fail('Absolute source location unexpectedly passed raw HTML validation.');
        } catch (PortableConfigurationException $exception) {
            self::assertStringContainsString('content/en/security.md:1:1', $exception->getMessage());
            self::assertStringNotContainsString('/workspace', $exception->getMessage());
        }

        $sandboxed = $renderer->render(<<<'MD'
:::html
```html
<button onclick="document.body.dataset.ready='yes'">Run</button>
```
:::

:::example {label=Source preview=sandbox}
```html
<strong>Safe source</strong>
```
:::
MD);
        // Both frames load when the reader nears them.
        self::assertStringContainsString('sandbox loading="lazy" data-docara-srcdoc=', $sandboxed);
        self::assertStringContainsString('sandbox="allow-scripts" allow="clipboard-write" scrolling="no" loading="lazy" data-docara-srcdoc=', $sandboxed);
        self::assertStringNotContainsString(' srcdoc=', $sandboxed);
    }

    #[Test]
    public function inline_example_headings_nest_under_the_page_and_keep_their_look(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $markdown = <<<'MD'
:::example {label="Hero"}
```markdown
:::hero {variant=compact}
# Short introduction

A compact first screen.
:::
```
:::

:::example {label="Headings"}
```html
<section><h1 class="m-0">Title</h1><h2>Part</h2><h6>Note</h6><h3 class="sf-h-1">Big</h3></section>
```
:::
MD;
        $html = $renderer->render($markdown);
        $previews = (string) preg_replace('~<pre\b.*?</pre>~s', '', $html);

        self::assertStringNotContainsString('<h1', $previews);
        self::assertStringContainsString('<h2 class="sf-h-1 text-6 heading m-0">Short introduction</h2>', $previews);
        self::assertStringContainsString(
            '<h2 class="sf-h-1 text-6 heading m-0">Title</h2><h3 class="sf-h-2 text-5 heading">Part</h3><h6>Note</h6><h4 class="sf-h-1">Big</h4>',
            $previews,
        );
        // The Source tab still shows the markup as written.
        self::assertStringContainsString('&lt;h1 class=&quot;m-0&quot;&gt;Title&lt;/h1&gt;', $html);
    }

    #[Test]
    public function inline_example_headings_leave_markup_in_attribute_values_alone(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::example {label="Rich text"}
```html
<div data-value="<h2>About</h2><p>Text</p>" title='<h1 class="x">'><h1 data-note="a > b">Title</h1></div>
```
:::
MD);
        $previews = (string) preg_replace('~<pre\b.*?</pre>~s', '', $html);

        self::assertStringNotContainsString('<h3', $previews);
        self::assertStringContainsString('<h2 class="sf-h-1 text-6 heading" data-note="a > b">Title</h2>', $previews);
        self::assertStringNotContainsString('text-5', $previews);
    }

    #[Test]
    public function example_preview_mode_is_selected_deterministically_and_can_be_overridden_safely(): void
    {
        $renderer = new PortableMarkdownRenderer;

        $automatic = $renderer->render(<<<'MD'
:::example {label="Safe HTML"}
```html
<button class="sf-button" type="button">Continue</button>
```
:::
MD);
        self::assertStringContainsString('data-docara-example-preview-requested="auto"', $automatic);
        self::assertStringContainsString('data-docara-example-preview-resolved="inline"', $automatic);
        self::assertStringContainsString('data-docara-example-preview-reason="admitted_html"', $automatic);
        self::assertStringContainsString('data-docara-example-inline-preview', $automatic);
        // The inline preview is the page's own markup; the only frame is the
        // hidden one the full-screen viewer loads on demand.
        self::assertSame(1, substr_count($automatic, '<iframe '));
        self::assertStringContainsString('<iframe data-docara-example-responsive-frame ', $automatic);
        self::assertStringContainsString('data-docara-srcdoc=', $automatic);
        self::assertStringNotContainsString(' srcdoc=', $automatic);
        self::assertStringContainsString('data-docara-example-viewer hidden', $automatic);

        $isolated = $renderer->render(<<<'MD'
:::example {label="Interactive"}
```html
<button type="button">Run</button>
```
```javascript
document.querySelector('button').dataset.ready = 'true';
```
:::
MD);
        self::assertStringContainsString('data-docara-example-preview-resolved="sandbox"', $isolated);
        self::assertStringContainsString('data-docara-example-preview-reason="javascript"', $isolated);
        self::assertStringContainsString('data-docara-example-frame', $isolated);
        // The frame body leaves room for a focus ring or a shadow drawn
        // outside a sample at its edge, which the frame would otherwise clip.
        self::assertStringContainsString(
            'body{padding:calc(var(--docara-example-inset,0px) + var(--sf-focus--width,2px) + var(--sf-focus--offset,1px)) calc(var(--sf-focus--width,2px) + var(--sf-focus--offset,1px))}',
            html_entity_decode($isolated),
        );

        $forced = $renderer->render(<<<'MD'
:::example {label="Isolated HTML" preview=sandbox}
```html
<strong>Safe source</strong>
```
:::
MD);
        self::assertStringContainsString('data-docara-example-preview-requested="sandbox"', $forced);
        self::assertStringContainsString('data-docara-example-preview-reason="forced_sandbox"', $forced);
        self::assertStringContainsString('data-docara-example-frame', $forced);
    }

    #[Test]
    public function sandboxed_examples_name_their_frame_and_declare_the_icons_their_sources_use(): void
    {
        $renderer = new PortableMarkdownRenderer;

        $labelled = $renderer->render(<<<'MD'
:::example {label="Save <button>" preview=sandbox}
```html
<button type="button">Save</button>
```
:::
MD);
        self::assertStringContainsString('<iframe title="Save &lt;button&gt;" data-docara-example-frame', $labelled);
        self::assertStringContainsString(htmlspecialchars('<title>Save &lt;button&gt;</title>', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'), $labelled);
        // The frame keeps the author's label; only the first tab reads the localized "Example".
        self::assertStringNotContainsString('<iframe title="Save &lt;button&gt;" data-docara-example-default-label', $labelled);
        self::assertSame(1, substr_count($labelled, 'data-docara-example-default-label'));
        self::assertStringContainsString('class="docara-example-preview__caption">Save &lt;button&gt;</figcaption>', $labelled);
        self::assertStringNotContainsString('data-docara-example-icons', $labelled);

        $unlabelled = $renderer->render(<<<'MD'
:::example {preview=sandbox}
```html
<i class="sf-icon sf-icon-rounded" aria-hidden="true">home</i>
```
:::
MD);
        self::assertStringContainsString('<iframe title="Example" data-docara-example-default-label data-docara-example-icons="outlined rounded" data-docara-example-frame', $unlabelled);
        self::assertStringContainsString('data-docara-example-tab="example" data-docara-example-default-label', $unlabelled);
        self::assertStringContainsString(htmlspecialchars('<title>Example</title>', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'), $unlabelled);

        $smart = $renderer->render(<<<'MD'
:::example {label="Alert" preview=sandbox}
```html
<sf-alert type="info" title="Notice"></sf-alert>
```
:::
MD);
        // A Smart component's own icons are reported by the frame at run time.
        self::assertStringNotContainsString('data-docara-example-icons', $smart);
    }

    #[Test]
    public function explicit_inline_example_fails_closed_when_isolation_is_required(): void
    {
        $renderer = new PortableMarkdownRenderer;
        foreach ([
            ":::example {preview=inline}\n```html\n<button id=unsafe>Run</button>\n```\n:::\n",
            ":::example {preview=inline}\n```html\n<button>Run</button>\n```\n```css\nbutton { color: red; }\n```\n:::\n",
            ":::example {preview=unknown}\n```html\n<strong>Example</strong>\n```\n:::\n",
        ] as $index => $markdown) {
            try {
                $renderer->render($markdown);
                self::fail('An inadmissible preview mode unexpectedly rendered.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame(
                    $index === 2 ? 'MARKDOWN_EXAMPLE_PREVIEW_INVALID' : 'MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED',
                    $exception->errorCode,
                );
            }
        }
    }

    #[Test]
    public function embeds_use_an_explicit_provider_origin_consent_and_sandbox_allowlist(): void
    {
        $renderer = new PortableMarkdownRenderer;
        foreach ([
            ":::embed {provider=generic consent=none}\n[Open](/preview/)\n:::\n",
            ":::embed {provider=video}\n[Open](https://www.youtube-nocookie.com/embed/example)\n:::\n",
            ":::embed {provider=map}\n[Open](https://www.openstreetmap.org/export/embed.html)\n:::\n",
            ":::embed {provider=external consent=required}\n[Open](https://example.com/widget)\n:::\n",
        ] as $markdown) {
            $html = $renderer->render($markdown);
            self::assertStringContainsString('sandbox="allow-scripts allow-same-origin allow-presentation"', $html);
        }
        foreach ([
            ":::embed {provider=generic}\n[Open](https://example.com/)\n:::\n",
            ":::embed {provider=video}\n[Open](https://evil.example/video)\n:::\n",
            ":::embed {provider=map}\n[Open](http://www.openstreetmap.org/export/embed.html)\n:::\n",
            ":::embed {provider=external consent=none}\n[Open](https://example.com/widget)\n:::\n",
        ] as $markdown) {
            try {
                $renderer->render($markdown);
                self::fail('An embed outside the explicit policy unexpectedly rendered.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_EMBED_ORIGIN_FORBIDDEN', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function internal_preview_accepts_only_same_origin_routes_and_preserves_preview_size(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $html = $renderer->render(
            ":::internal_preview {size=tall title=\"Результат примера\"}\n[Открыть](/ru/result/)\n:::\n",
        );

        self::assertStringContainsString('data-docara-block="internal-preview"', $html);
        self::assertStringContainsString('data-preview-size="tall"', $html);
        self::assertStringContainsString('src="/ru/result/"', $html);
        self::assertStringNotContainsString(' sandbox=', $html);

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('same-origin absolute path');
        $renderer->render(
            ":::internal_preview {title=\"Unsafe\"}\n[Открыть](https://example.com/)\n:::\n",
        );
    }

    #[Test]
    public function an_internal_preview_without_a_title_is_marked_for_the_page_language(): void
    {
        $html = (new PortableMarkdownRenderer)->render(":::internal_preview\n[Open](/en/result/)\n:::\n");

        self::assertStringContainsString(
            ' title="' . PortableMarkdownRenderer::INTERNAL_PREVIEW_DEFAULT_TITLE . '" '
                . PortableMarkdownRenderer::INTERNAL_PREVIEW_DEFAULT_TITLE_MARKER,
            $html,
        );
        $titled = (new PortableMarkdownRenderer)->render(":::internal_preview {title=\"Own\"}\n[Open](/en/result/)\n:::\n");
        self::assertStringNotContainsString(PortableMarkdownRenderer::INTERNAL_PREVIEW_DEFAULT_TITLE_MARKER, $titled);
    }

    #[Test]
    public function an_example_label_is_a_caption_under_the_frame_and_the_first_tab_stays_short(): void
    {
        $renderer = new PortableMarkdownRenderer;
        // A descriptive label as long as the longest one on a component site.
        $label = 'Положения стрелки подсказки относительно элемента на странице и в таблицах';
        self::assertSame(74, mb_strlen($label));
        $html = $renderer->render(":::example {label=\"{$label}\"}\n```html\n<p>x</p>\n```\n:::");

        $caption = 'class="docara-example-preview__caption">' . $label . '</figcaption></figure>';
        self::assertStringStartsWith('<figure data-docara-example-figure class="docara-example-figure"><section data-docara-example="', $html);
        self::assertStringEndsWith($caption, trim($html));
        preg_match('/<figcaption id="([^"]+)"/', $html, $id);
        self::assertNotEmpty($id);
        // The frame and its tab list are named by the caption; no tab holds the label.
        self::assertStringContainsString(' aria-labelledby="' . $id[1] . '"><div class="docara-example-preview__header">', $html);
        self::assertStringContainsString('<div role="tablist" aria-labelledby="' . $id[1] . '" class="docara-example-preview__tabs">', $html);
        self::assertSame(0, preg_match('~role="tab"[^>]*>[^<]*' . preg_quote($label, '~') . '~u', $html));
        // The viewer header repeats the text; the preview frame keeps it as its title.
        self::assertStringContainsString('<span data-docara-example-title class="docara-example-preview__title">' . $label . '</span>', $html);
        self::assertStringContainsString('title="' . $label . '"', $html);

        $resultOnly = $renderer->render(":::example {label=\"{$label}\" sources=false}\n```html\n<p>x</p>\n```\n:::");
        self::assertSame(1, substr_count($resultOnly, 'role="tab"'));
        self::assertStringEndsWith($caption, trim($resultOnly));

        // Without a label the frame has no caption and no figure.
        $plain = $renderer->render(":::example\n```html\n<p>x</p>\n```\n:::");
        self::assertStringStartsWith('<section data-docara-example="', trim($plain));
        self::assertStringNotContainsString('<figcaption', $plain);
        self::assertStringNotContainsString('data-docara-example-title', $plain);
        self::assertStringContainsString('<div role="tablist" aria-label="Example" class="docara-example-preview__tabs">', $plain);

        // The caption reads like one under a picture and the tab row never scrolls.
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css');
        self::assertStringContainsString('.docara-example-preview__caption{margin-block-start:var(--sf-space-1\\/3);font-size:var(--sf-text-size-1\\/2);line-height:var(--sf-text-height-1\\/2);color:var(--sf-on-surface-variant);overflow-wrap:break-word}', $css);
        self::assertStringContainsString('.docara-example-preview__tabs{display:inline-flex;inline-size:max-content;max-inline-size:100%;align-items:center;gap:0}', $css);
        self::assertStringContainsString('.docara-example-preview__title{display:none}', $css);
    }

    #[Test]
    public function it_renders_markdown_and_multifile_examples_as_one_tabbed_surface(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $markdown = $renderer->render(<<<'MD'
:::example {label=Пример}
```markdown
:badge[Готово]{type=main scheme=success size=1/2}
```
:::
MD);

        self::assertStringContainsString('data-docara-example=', $markdown);
        // The first tab is the localized "Example"; the author's label is the caption under the frame.
        self::assertStringContainsString('data-docara-example-default-label class="docara-example-preview__tab">Example</button>', $markdown);
        self::assertStringContainsString('class="docara-example-preview__caption">Пример</figcaption></figure>', $markdown);
        self::assertStringContainsString('data-docara-example-tab="markdown"', $markdown);
        self::assertStringContainsString(
            'data-docara-example-panel="markdown" aria-hidden="true" class="docara-example-preview__panel docara-example-preview__panel--source"',
            $markdown,
        );
        self::assertStringContainsString('data-docara-example-tab="example" data-docara-example-default-label class="docara-example-preview__tab"', $markdown);
        self::assertStringContainsString('data-docara-example-tab="markdown" class="docara-example-preview__tab"', $markdown);
        self::assertStringContainsString('data-docara-example-copy', $markdown);
        self::assertStringContainsString('data-docara-example-fullscreen="true"', $markdown);
        self::assertStringContainsString('data-docara-example-wrap="true"', $markdown);
        // An inline Markdown example offers the device-width check: the inline
        // preview stays on the page and a hidden sandboxed frame with the same
        // markup waits, its document in data-docara-srcdoc, until the viewer opens.
        self::assertStringContainsString('data-docara-example-viewer', $markdown);
        self::assertStringContainsString('data-docara-example-viewports', $markdown);
        self::assertStringContainsString('<div data-docara-example-inline-preview>', $markdown);
        self::assertSame(1, substr_count($markdown, '<iframe data-docara-example-responsive-frame title="Пример"'));
        self::assertMatchesRegularExpression('~<iframe data-docara-example-responsive-frame [^>]*data-docara-example-frame [^>]*sandbox="allow-scripts"[^>]*data-docara-srcdoc="~', $markdown);
        self::assertStringNotContainsString(' srcdoc="', $markdown);
        self::assertStringContainsString('data-docara-example-wrap', $markdown);
        self::assertStringContainsString('<sf-icon icon="wrap_text" aria-hidden="true"></sf-icon>', $markdown);
        self::assertStringContainsString('data-wrap-icon="wrap_text" data-unwrap-icon="format_text_overflow"', $markdown);
        self::assertStringContainsString('sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1', $markdown);
        self::assertStringContainsString('content-main-center m-0', $markdown);
        self::assertStringContainsString('data-copy-icon="content_copy" data-copied-icon="check"', $markdown);
        self::assertStringNotContainsString('data-copied-icon="check""', $markdown);
        self::assertSame(substr_count($markdown, '<div'), substr_count($markdown, '</div>'));
        self::assertStringContainsString('<sf-icon icon="content_copy" aria-hidden="true"></sf-icon>', $markdown);
        self::assertStringContainsString('class="docara-example-preview__header"', $markdown);
        self::assertStringContainsString('class="docara-example-preview__tabs"', $markdown);
        self::assertStringContainsString('class="docara-example-preview__indicator"', $markdown);
        self::assertStringContainsString('class="docara-example-preview__panels"', $markdown);
        self::assertStringNotContainsString('sf-tabs sf-tabs--underline', $markdown);
        // The frame document waiting in data-docara-srcdoc is its own page;
        // the example markup around it carries no inline styles.
        self::assertStringNotContainsString('style=', (string) preg_replace('/ data-docara-srcdoc="[^"]*"/', '', $markdown));
        self::assertStringContainsString('sf-badge--success', $markdown);

        $withoutControls = $renderer->render(<<<'MD'
:::example {label=Пример fullscreen=false wrap=false}
```markdown
Текст
```
:::
MD);
        self::assertStringContainsString('data-docara-example-fullscreen="false"', $withoutControls);
        self::assertStringContainsString('data-docara-example-wrap="false"', $withoutControls);
        self::assertStringNotContainsString('data-docara-example-fullscreen hidden', $withoutControls);
        self::assertStringNotContainsString('data-docara-example-wrap hidden', $withoutControls);
        // Without the viewer there is no responsive check, so no hidden frame.
        self::assertStringNotContainsString('data-docara-example-responsive-frame', $withoutControls);

        $resultOnly = $renderer->render(<<<'MD'
:::example {label=Пример sources=false}
```html
<p class="landing-sample">Готово</p>
```
:::
MD);
        // The result alone: one tab, the preview built from the source, no
        // source tab and no wrap button.
        self::assertSame(1, substr_count($resultOnly, 'role="tab"'));
        self::assertStringContainsString('landing-sample', $resultOnly);
        self::assertStringNotContainsString('docara-example-preview__panel--source', $resultOnly);
        self::assertStringContainsString('data-docara-example-wrap="false"', $resultOnly);
        self::assertStringNotContainsString('data-docara-example-wrap hidden', $resultOnly);
        $wide = $renderer->render(":::example {layout=wide sources=false}\n```html\n<p>x</p>\n```\n:::");
        // A wide example carries the shell's full-width marker on its root.
        self::assertMatchesRegularExpression('/<section data-docara-example="[^"]+"[^>]* data-docara-width="full" class="docara-example-preview docara-example-preview--wide"/', $wide);
        self::assertStringNotContainsString('data-docara-width', $resultOnly);
        // With a caption the figure around the frame takes the full-width marker.
        $wideCaptioned = $renderer->render(":::example {label=Витрина layout=wide sources=false}\n```html\n<p>x</p>\n```\n:::");
        self::assertStringContainsString('<figure data-docara-example-figure data-docara-width="full" class="docara-example-figure"><section ', $wideCaptioned);
        self::assertSame(1, substr_count($wideCaptioned, 'data-docara-width="full"'));
        try {
            $renderer->render(":::example {layout=full}\n```html\n<p>x</p>\n```\n:::");
            self::fail('An example accepted an unknown layout.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID', $exception->errorCode);
        }

        $pageAlone = $renderer->render(":::example {src=/start/ sources=false}\n:::");
        self::assertStringContainsString('data-docara-example-page', $pageAlone);
        self::assertSame(1, substr_count($pageAlone, 'role="tab"'));

        try {
            $renderer->render(":::example {sources=maybe}\n```html\n<p>x</p>\n```\n:::");
            self::fail('An example accepted a sources value that is not true or false.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID', $exception->errorCode);
        }

        $table = $renderer->render(<<<'MD'
:::example {label=Таблица}
```markdown
| Имя | Значение |
| --- | --- |
| theme | system |
```
:::
MD);
        // The hidden responsive frame holds the same table in its escaped document.
        $table = (string) preg_replace('/ data-docara-srcdoc="[^"]*"/', '', $table);
        self::assertSame(1, substr_count($table, 'data-docara-table-scroll'));
        self::assertSame(substr_count($table, '<div'), substr_count($table, '</div>'));

        $nestedCode = $renderer->render(<<<'MD'
:::example {label=Код}
~~~markdown
```php
echo 'Docara';
```
~~~
:::
MD);
        self::assertStringContainsString('<code class="language-php">', $nestedCode);
        self::assertStringContainsString('data-docara-example-tab="markdown"', $nestedCode);
        self::assertStringContainsString("```php\necho 'Docara';\n```", $nestedCode);
        // The hidden responsive frame repeats the preview in its escaped document.
        self::assertSame(2, substr_count((string) preg_replace('/ data-docara-srcdoc="[^"]*"/', '', $nestedCode), 'data-docara-code-block'));

        $web = $renderer->render(<<<'MD'
:::example {label=Preview}
```html
<button id="hello">Hello</button>
```
```css
#hello { color: red; }
```
```javascript
document.querySelector('#hello').dataset.ready = 'true';
```
:::
MD);

        self::assertStringContainsString('sandbox="allow-scripts"', $web);
        self::assertStringContainsString('data-docara-example-frame', $web);
        self::assertStringContainsString('data-sf-observer="ignore"', $web);
        self::assertStringContainsString('type:&apos;docara:example-height&apos;', $web);
        self::assertStringContainsString('type===&apos;docara:example-measure&apos;', $web);
        self::assertStringContainsString('lastHeight=-1;', $web);
        self::assertStringContainsString('contentBottom-contentTop', $web);
        self::assertStringContainsString('document.documentElement.style.minHeight=&apos;0&apos;', $web);
        self::assertStringContainsString('document.documentElement.style.overflow=&apos;hidden&apos;', $web);
        self::assertStringContainsString('body.style.overflow=&apos;hidden&apos;', $web);
        self::assertStringContainsString('function applyDesignEnvironment(data)', $web);
        self::assertStringContainsString('data&amp;&amp;data.designTokens', $web);
        self::assertStringContainsString('root.style.fontSize=data.rootFontSize', $web);
        self::assertStringNotContainsString('body.scrollHeight,root.scrollHeight', $web);
        self::assertStringContainsString('function measureSettled()', $web);
        // An open menu sits in the top layer, which the frame clips; the frame
        // reaches the menu's drawn box. Rows the list scrolls stay out, or a
        // list capped to the viewport would grow the frame without end.
        self::assertStringContainsString('querySelectorAll(&apos;:popover-open,dialog[open]&apos;)', $web);
        self::assertStringContainsString('var reach=layerRect.bottom+12;', $web);
        self::assertStringNotContainsString('part.scrollHeight-part.clientHeight', $web);
        self::assertStringContainsString('document.addEventListener(&apos;toggle&apos;,measureSettled,true)', $web);
        self::assertStringContainsString('setTimeout(measureNow,100)', $web);
        // A toast container fixed to the frame's bottom edge moves down as the
        // frame grows: it counts with its own uncapped height and its gap to
        // that edge, so the frame settles instead of chasing it.
        // The page turns the Framework preloader off; a frame does the same,
        // or a slow load covers the finished example.
        self::assertStringContainsString('window.SF_BOOT_CONFIG=window.SF_BOOT_CONFIG||{};window.SF_BOOT_CONFIG.preloader={enabled:false};', $web);
        self::assertStringContainsString('function bottomPinned(layer)', $web);
        self::assertStringContainsString('map.get(&apos;top&apos;).toString()===&apos;auto&apos;', $web);
        self::assertStringContainsString('function naturalBlockSize(layer)', $web);
        self::assertStringContainsString('layer.style.setProperty(&apos;max-block-size&apos;,&apos;none&apos;,&apos;important&apos;)', $web);
        self::assertStringContainsString('if(bodyObserver)bodyObserver.takeRecords()', $web);
        self::assertStringContainsString('if(childStyle.position===&apos;fixed&apos;&amp;&amp;bottomPinned(child))', $web);
        self::assertStringContainsString('pinnedExtent=Math.max(pinnedExtent,Math.max(0,innerHeight-childRect.bottom)+naturalBlockSize(child))', $web);
        self::assertStringContainsString('contentBottom=Math.max(contentBottom,contentTop+pinnedExtent)', $web);
        self::assertStringNotContainsString('new MutationObserver(measure).observe(body', $web);
        self::assertStringContainsString('script.setAttribute(&apos;data-docara-example-framework-boot&apos;,item.key)', $web);
        self::assertStringContainsString('link.setAttribute(&apos;data-docara-example-framework-style&apos;,&apos;&apos;)', $web);
        self::assertStringContainsString('document.documentElement.classList.add(&apos;theme-&apos;+theme)', $web);
        self::assertStringContainsString('data-docara-example-tab="html"', $web);
        self::assertStringContainsString('data-docara-example-tab="css"', $web);
        self::assertStringContainsString('data-docara-example-tab="javascript"', $web);
        self::assertStringContainsString('&lt;style&gt;#hello { color: red; }&lt;/style&gt;', $web);
        self::assertStringContainsString('data-docara-example-viewer', $web);
        self::assertStringContainsString('data-docara-example-viewports', $web);
        self::assertStringContainsString('data-docara-example-viewport="desktop"', $web);
        self::assertStringContainsString('data-docara-example-viewport="tablet"', $web);
        self::assertStringContainsString('data-docara-example-viewport="mobile"', $web);
        self::assertStringContainsString('role="group" aria-label="Preview width" class="docara-example-preview__viewports"', $web);
        self::assertStringContainsString('<sf-icon icon="desktop_windows" aria-hidden="true"></sf-icon>', $web);
        self::assertStringContainsString('<sf-icon icon="smartphone" aria-hidden="true"></sf-icon>', $web);
        self::assertStringContainsString('<sf-icon icon="devices" aria-hidden="true"></sf-icon>', $web);
    }

    #[Test]
    public function example_controls_inherit_document_settings_and_allow_a_block_override(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $source = ":::example {label=Example preview=sandbox}\n```html\n<p>Text</p>\n```\n:::\n";
        $location = new SourceLocation('content/en/example.md', 1, 1, 1);
        $disabled = $renderer->renderAt($source, null, null, $location, [
            'fullscreen' => false,
            'wrap' => false,
        ]);
        self::assertStringContainsString('data-docara-example-fullscreen="false"', $disabled);
        self::assertStringContainsString('data-docara-example-wrap="false"', $disabled);
        self::assertStringNotContainsString('data-docara-example-viewer', $disabled);
        self::assertStringNotContainsString('data-docara-example-wrap hidden', $disabled);

        $override = str_replace('{label=Example preview=sandbox}', '{label=Example preview=sandbox fullscreen=true wrap=true}', $source);
        $enabled = $renderer->renderAt($override, null, null, $location, [
            'fullscreen' => false,
            'wrap' => false,
        ]);
        self::assertStringContainsString('data-docara-example-fullscreen="true"', $enabled);
        self::assertStringContainsString('data-docara-example-wrap="true"', $enabled);
        self::assertStringContainsString('data-docara-example-viewer', $enabled);
        self::assertStringContainsString('data-docara-example-wrap hidden', $enabled);
    }

    #[Test]
    public function alert_inside_markdown_example_uses_the_content_smart_gateway(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $html = $renderer->render(<<<'MD'
:::example {label="Общий пример"}
```markdown
:::alert {type=warning variant=outlined}
#### Обратите внимание

Проверьте параметры перед публикацией.
:::
```
:::
MD, null, 'content/ru/components/alert.md');

        self::assertStringContainsString('data-docara-example=', $html);
        self::assertStringContainsString('data-docara-example-tab="example"', $html);
        self::assertStringContainsString('data-docara-example-tab="markdown"', $html);
        self::assertStringContainsString(
            'data-docara-block="alert" role="status" aria-label="Обратите внимание" class="sf-alert sf-alert--warning sf-alert--outlined',
            $html,
        );
    }

    #[Test]
    public function external_code_inside_markdown_example_keeps_the_authored_source_context(): void
    {
        $root = sys_get_temp_dir() . '/docara-code-example-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root, 0700, true));
        file_put_contents($root . '/snippet.php', "<?php\necho 'ready';\n");

        try {
            $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::example {label="Code"}
```markdown
:::code {src="snippet.php" lang=php lines="1-2" title="snippet.php"}
:::
```
:::
MD, $root, $root . '/page.md');

            self::assertStringContainsString('data-docara-example=', $html);
            self::assertStringContainsString('data-docara-code-title="snippet.php"', $html);
            self::assertStringContainsString('&lt;?php', $html);
            self::assertStringContainsString("echo 'ready';", $html);
        } finally {
            @unlink($root . '/snippet.php');
            @rmdir($root);
        }
    }

    #[Test]
    public function it_renders_local_diagrams_math_and_consent_gated_embeds(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $diagram = $renderer->render(<<<'MD'
:::diagram {engine=mermaid title="Build flow"}
flowchart LR
  Markdown --> HTML
:::
MD);
        self::assertStringContainsString('data-docara-block="diagram"', $diagram);
        self::assertStringContainsString('data-docara-diagram-source role="img" aria-label="Build flow"', $diagram);
        self::assertStringContainsString('flowchart LR', $diagram);

        // A text alternative replaces the caption as the picture's name, and
        // the caption above it stays what the reader sees.
        $described = $renderer->render(<<<'MD'
:::diagram {title="Build flow" label="A source becomes a recipe, then a document, then HTML"}
flowchart LR
  A --> B
:::
MD);
        self::assertStringContainsString('>Build flow</figcaption>', $described);
        self::assertStringContainsString('role="img" aria-label="A source becomes a recipe, then a document, then HTML"', $described);

        $math = $renderer->render(<<<'MD'
:::math {display=block label="Energy formula"}
E=mc^2
:::
MD);
        self::assertStringContainsString('data-docara-block="math"', $math);
        self::assertStringContainsString('data-docara-math-source role="math" aria-label="Energy formula"', $math);

        $embed = $renderer->render(<<<'MD'
:::embed {provider=external title="External widget"}
[Open widget](https://example.com/widget)
:::
MD);
        self::assertStringContainsString('data-provider="external" data-consent="required"', $embed);
        self::assertStringContainsString('class="aspect-16x9 ', $embed);
        self::assertStringContainsString('data-docara-embed-consent', $embed);
        self::assertStringContainsString('data-docara-embed-template', $embed);
        self::assertStringContainsString('data-docara-embed-load>Open widget</button>', $embed);
        self::assertStringNotContainsString('External content is loaded', $embed);
        self::assertStringNotContainsString('>Load content</button>', $embed);
        self::assertStringNotContainsString(' src="https://example.com/widget"', $embed);
        self::assertStringContainsString(' data-src="https://example.com/widget"', $embed);

        $cinemaEmbed = $renderer->render(<<<'MD'
:::embed {provider=external ratio=21/9 title="Cinema widget"}
[Open widget](https://example.com/widget)
:::
MD);
        self::assertStringContainsString('class="docara-aspect-21x9 ', $cinemaEmbed);
    }

    #[Test]
    public function it_renders_bounded_inline_components_without_touching_code_examples(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
Статус :badge[Готово]{type=main scheme=success size=1/2}, нажмите :kbd[Esc].

:icon[search]{size=1 family=rounded weight=medium filled=true label="Поиск"}

:icon[bolt]{size=1 container=circle variant=main scheme=success label="Быстро"}

:button[Открыть]{href=/start/ type=outline scheme=on-surface icon=arrow_forward}

`:badge[Не компонент]{type=main}`

```text
:icon[also_not_a_component]
```
MD);

        self::assertStringContainsString('sf-badge--main sf-badge--success sf-badge--size-1/2', $html);
        self::assertStringContainsString('<kbd class="inline-flex items-center', $html);
        self::assertStringContainsString('sf-icon-rounded sf-icon-filled', $html);
        self::assertStringContainsString('role="img" aria-label="Поиск"', $html);
        self::assertStringContainsString(
            'class="docara-icon inline-grid" data-docara-icon-container="circle" data-docara-icon-variant="main" data-docara-icon-scheme="success" data-docara-icon-size="1"',
            $html,
        );
        self::assertStringContainsString('role="img" aria-label="Быстро"', $html);
        self::assertStringContainsString('sf-button--outline sf-button--on-surface', $html);
        self::assertStringContainsString('href="/start/"', $html);
        self::assertStringContainsString('<code>:badge[Не компонент]{type=main}</code>', $html);
        self::assertStringContainsString(':icon[also_not_a_component]', $html);
    }

    #[Test]
    public function trees_render_as_the_framework_tree_and_static_trees_as_nested_lists_with_type_icons(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $interactive = $renderer->render(<<<'MD'
:::tree {interactive=true}
- content
  - ru
    - `index.md`
- docara.json
:::
MD);
        $folder = static fn (string $name, string $toggle): string => '<div class="sf-tree-item open"><div class="sf-tree-item-container flex items-cross-center">'
            . $toggle . '<span class="sf-tree-item-name">' . $name . '</span></div><div class="sf-tree-group">';
        $file = static fn (string $name): string => '<div class="sf-tree-item"><div class="sf-tree-item-container flex items-cross-center">'
            . '<span class="sf-tree-toggle-spacer" aria-hidden="true"></span><span class="sf-tree-item-name">' . $name . '</span></div></div>';
        $button = '<button type="button" class="sf-icon-button sf-icon-button--link sf-icon-button--size-1/3" tabindex="-1" aria-hidden="true"><i class="sf-icon" aria-hidden="true">folder_open</i></button>';
        self::assertSame(
            '<div data-docara-block="tree" data-interactive="true" class="sf-tree flex flex-col m-bottom-1">'
            . $folder('content', $button) . $folder('ru', $button) . $file('<code>index.md</code>') . '</div></div></div></div>'
            . $file('docara.json') . '</div>',
            trim($interactive),
        );

        // A static tree is a nested list (C102, C109): no toggle column, an
        // icon before each name, nothing to press.
        $static = $renderer->render(<<<'MD'
:::tree {interactive=false}
- content
  - index.md
- docara.json
:::
MD);
        $row = static fn (string $icon, string $name): string => '<span class="docara-tree__row"><i class="sf-icon docara-tree__icon" aria-hidden="true">'
            . $icon . '</i><span class="docara-tree__name">' . $name . '</span></span>';
        self::assertSame(
            '<ul data-docara-block="tree" data-interactive="false" class="docara-tree m-bottom-1">'
            . '<li class="docara-tree__item">' . $row('folder_open', 'content')
            . '<ul class="docara-tree__group"><li class="docara-tree__item">' . $row('draft', 'index.md') . '</li></ul></li>'
            . '<li class="docara-tree__item">' . $row('draft', 'docara.json') . '</li></ul>',
            trim($static),
        );
        self::assertStringNotContainsString('sf-tree', $static);

        // The level step is the icon and the row gap, the same gap as inside
        // a row, so a child's icon stands under its parent's name.
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css');
        self::assertStringContainsString('.docara-tree__group{margin-block:0;padding-inline-start:calc(var(--sf-icon-size-3) + var(--sf-space-1\\/2))}', $css);
        self::assertStringContainsString('.docara-tree__row{display:flex;align-items:center;gap:var(--sf-space-1\\/2);', $css);
        self::assertStringContainsString('.docara-tree__icon{flex:none;inline-size:var(--sf-icon-size-3);', $css);
    }

    #[Test]
    public function inline_components_fail_closed_on_unknown_or_unsafe_parameters(): void
    {
        $cases = [
            [':badge[Test]{type=unknown}', 'CONTENT_COMPONENT_PROP_INVALID'],
            [':icon[search]{weight=bold}', 'CONTENT_COMPONENT_PROP_INVALID'],
            [':icon[search]{variant=main}', 'CONTENT_COMPONENT_PROP_COMBINATION_INVALID'],
            [':icon[search]{container=square variant=plain}', 'CONTENT_COMPONENT_PROP_COMBINATION_INVALID'],
            [':icon[not an identifier]', 'CONTENT_COMPONENT_SLOT_INVALID'],
            [':icon[serach]', 'CONTENT_COMPONENT_ICON_UNKNOWN'],
            [':button[Run]{href=javascript:alert(1)}', 'CONTENT_COMPONENT_PROP_INVALID'],
            [':button[Run]', 'CONTENT_COMPONENT_PROP_INVALID'],
            [':kbd[Esc]{size=1}', 'CONTENT_COMPONENT_PROP_UNKNOWN'],
        ];

        foreach ($cases as [$markdown, $code]) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("Invalid inline component unexpectedly rendered for [$code].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($code, $exception->errorCode);
            }
        }
    }

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
            '<section data-docara-block="card" data-docara-card-variant="default" class="bg-surface-page border border-outline-variant radius-surface p-3 flex flex-col gap-1 m-bottom-1">',
            $html,
        );
        self::assertStringContainsString(
            '<ol class="m-0 p-0">',
            $html,
        );
        self::assertStringContainsString('class="docara-step grid items-cross-start gap-1 list-none"', $html);
        self::assertStringContainsString('class="docara-step-marker inline-flex items-cross-center content-main-center"', $html);
        self::assertStringContainsString('<div class="docara-step-content">Установите PHP-зависимости.</div>', $html);
        self::assertStringNotContainsString('absolute inset-inline-start-0', $html);
        self::assertStringContainsString(
            '<div data-docara-table-scroll class="table-frame m-bottom-1"><table class="table table-stripe">',
            $html,
        );
        self::assertStringContainsString(
            '<div data-docara-code-block data-sf-highlight-chrome="static" data-docara-code-language="php" data-docara-code-wrap="true" class="source init sf-code-surface docara-code-block min-w-0 overflow-hidden bg-surface-container border border-outline-variant radius-surface m-bottom-1">',
            $html,
        );
        self::assertStringContainsString('data-docara-code-fallback', $html);
        // The header sits on the block's own surface, so it needs no backdrop
        // blur; a blur on every code header made the sticky header and outline
        // blank out for a frame while the page scrolled.
        self::assertStringContainsString('<div data-docara-code-fallback class="docara-code-header flex content-main-between border-outline-variant items-cross-center">', $html);
        self::assertStringNotContainsString('bg-surface-overlay', $html);
        self::assertStringContainsString('data-docara-code-actions', $html);
        self::assertStringContainsString('data-docara-code-wrap-toggle', $html);
        self::assertStringContainsString('data-docara-code-copy', $html);
        self::assertStringContainsString(
            '<pre class="docara-code-scroll overflow-auto m-0 p-2"><code class="language-php">',
            $html,
        );
        self::assertSame(1, substr_count($html, 'data-docara-code-block'));
        self::assertSame(1, substr_count(
            strstr($html, '<div data-docara-code-block') ?: '',
            ' border ',
        ));
        self::assertStringNotContainsString('class="docara-card', $html);
        self::assertStringNotContainsString('docara-steps', $html);
    }

    #[Test]
    public function standalone_code_wrap_control_inherits_document_settings(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $source = "```text\nA long source line\n```\n";
        $location = new SourceLocation('content/en/code.md', 1, 1, 1);

        $enabled = $renderer->renderAt($source, null, null, $location, [], ['wrap' => true]);
        self::assertStringContainsString('data-docara-code-wrap="true"', $enabled);
        self::assertStringContainsString('data-docara-code-wrap-toggle', $enabled);

        $disabled = $renderer->renderAt($source, null, null, $location, [], ['wrap' => false]);
        self::assertStringContainsString('data-docara-code-wrap="false"', $disabled);
        self::assertStringNotContainsString('data-docara-code-wrap-toggle', $disabled);
        self::assertStringContainsString("<code class=\"language-text\">A long source line\n</code>", $disabled);
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
        self::assertStringContainsString('class="source init sf-code-surface docara-code-block', $html);
        self::assertStringContainsString('data-docara-code-language="shell"', $html);
        self::assertStringContainsString('data-docara-code-fallback', $html);
        self::assertStringContainsString('data-docara-code-copy', $html);
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
- ![](/assets/markdown.png) **Пишите в Markdown.** Страницы и код хранятся рядом с проектом.
- *Настраивайте через [JSON](/json/).* Макет наследуется и проверяется схемой.
- **Собирайте на `PHP`.** ~~Случайность~~ исключена.
:::
MD;

        $html = (new PortableMarkdownRenderer)->render($markdown);

        self::assertStringContainsString(
            '<a data-docara-block="cta" class="docara-cta-link sf-button sf-button--default sf-button--primary sf-button--size-1 radius-default inline-flex items-cross-center content-main-center decoration-none w-full sm:w-auto sm:self-cross-start m-bottom-1" href="/start/" title="Быстрый старт"><span class="sf-button-text-container">Начать работу</span></a>',
            $html,
        );
        self::assertStringNotContainsString('bg-primary color-on-primary p-1/2 line-none', $html);
        self::assertStringContainsString(
            '<ul data-docara-block="features" class="grid grid-col-1 lg:grid-col-3 gap-2 list-none m-0 m-bottom-1 p-0">',
            $html,
        );
        self::assertSame(3, substr_count(
            $html,
            '<li class="bg-surface-page border border-outline-variant radius-surface p-3 flex min-w-0 max-w-none flex-col gap-1">',
        ));
        self::assertStringContainsString(
            '<img data-docara-media="feature-icon" loading="lazy" decoding="async" src="/assets/markdown.png" alt="" />',
            $html,
        );
        self::assertStringNotContainsString('<sf-button', $html);
        self::assertStringNotContainsString('docara-feature-card', $html);
    }

    #[Test]
    public function features_keep_inline_code_and_links_inside_one_text_run(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::features
- ![](/assets/cli.png) **Build `docara build`.** Run `php docara build` and open [the output](/build/) in a browser.
- Plain text with `inline code` and *emphasis* in one line.
:::
MD);

        self::assertStringContainsString(
            '<img data-docara-media="feature-icon" loading="lazy" decoding="async" src="/assets/cli.png" alt="" />'
            . '<strong>Build <code>docara build</code>.</strong>'
            . '<span class="min-w-0">Run <code>php docara build</code> and open <a href="/build/">the output</a> in a browser.</span></li>',
            $html,
        );
        self::assertStringContainsString(
            'flex-col gap-1"><span class="min-w-0">Plain text with <code>inline code</code> and <em>emphasis</em> in one line.</span></li>',
            $html,
        );
    }

    #[Test]
    public function a_leading_icon_component_becomes_the_feature_card_icon(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::features
- :icon[bolt]{size=1 container=square variant=tonal scheme=primary} **Fast.** Static output.
- Text with :icon[search] inline and :badge[New]{type=main} after it.
- :badge[Beta]{type=main} **Badge first.** A badge is not lifted.
:::
MD);

        $icon = '<span class="docara-icon inline-grid" data-docara-icon-container="square" data-docara-icon-variant="tonal" data-docara-icon-scheme="primary" data-docara-icon-size="1">'
            . '<i class="sf-icon sf-icon-loaded sf-icon-regular sf-icon--size-1" aria-hidden="true">bolt</i></span>';
        // The icon is a flex row of its own, like a feature image, so the
        // shell rule [data-docara-block="features"]>li>.docara-icon reaches it.
        self::assertStringContainsString(
            'flex-col gap-1">' . $icon . '<strong>Fast.</strong><span class="min-w-0">Static output.</span></li>',
            $html,
        );
        // An icon later in the text stays inline, and only an icon is lifted.
        self::assertMatchesRegularExpression('~flex-col gap-1"><span class="min-w-0">Text with <span class="docara-icon[^>]*><i[^>]*>search</i></span> inline~', $html);
        self::assertMatchesRegularExpression('~flex-col gap-1"><span class="min-w-0"><span class="sf-badge[^"]*"[^>]*>~', $html);
        self::assertStringNotContainsString('DOCARA_INLINE_', $html);
    }

    #[Test]
    public function a_fence_without_a_language_that_opens_with_a_tag_is_html(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $markup = $renderer->render("```\n<sf-button text=\"Save\"></sf-button>\n```\n");
        $plain = $renderer->render("```\nnpm install\n```\n");

        self::assertStringContainsString('data-docara-code-language="html"', $markup);
        self::assertStringContainsString('<span class="flex sf-text-1/3 weight-5">HTML</span>', $markup);
        self::assertStringContainsString('class="language-html"', $markup);
        self::assertStringContainsString('data-docara-code-language="code"', $plain);
        self::assertStringContainsString('<span class="flex sf-text-1/3 weight-5">Code</span>', $plain);
    }

    #[Test]
    public function a_bullet_between_spaces_in_prose_is_a_separator(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            "`appearance` • по умолчанию `default` • [справочник](#attributes)\n\n"
            . "а•б, `x • y`\n\n```text\na • b\n```\n",
        );
        $separator = '<span class="docara-sep" aria-hidden="true">•</span>';

        self::assertSame(2, substr_count($html, $separator));
        self::assertStringContainsString('<code>appearance</code> ' . $separator . ' по умолчанию', $html);
        self::assertStringContainsString('а•б', $html);
        self::assertStringContainsString('<code>x • y</code>', $html);
        self::assertStringContainsString('a • b', $html);
    }

    #[Test]
    public function the_mark_that_joins_a_feature_title_to_its_text_is_dropped(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::features
- **[Catalogue](/components/)**. Every component with a live example.
- **Layouts** — pages with different regions.
- **Fast.** Static output.
:::
MD);

        self::assertStringContainsString('<strong><a href="/components/">Catalogue</a></strong><span class="min-w-0">Every component with a live example.</span>', $html);
        self::assertStringContainsString('<strong>Layouts</strong><span class="min-w-0">pages with different regions.</span>', $html);
        self::assertStringContainsString('<strong>Fast.</strong><span class="min-w-0">Static output.</span>', $html);
    }

    #[Test]
    public function a_named_current_step_is_announced_and_an_unnamed_one_is_not(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $named = $renderer->render(":::steps {current=2}\n1. A\n2. B\n3. C\n:::\n");
        $plain = $renderer->render(":::steps\n1. A\n2. B\n:::\n");

        self::assertSame(1, substr_count($named, 'aria-current="step"'));
        self::assertStringContainsString('<li data-step-state="current" aria-current="step"', $named);
        self::assertStringNotContainsString('aria-current', $plain);
    }

    #[Test]
    public function the_panorama_ratio_uses_the_shell_class_the_framework_does_not_ship(): void
    {
        $html = (new PortableMarkdownRenderer)->render(":::figure {ratio=21/9}\n![Панорама](a.webp)\n:::\n");

        self::assertStringContainsString('class="overflow-hidden radius-surface docara-aspect-21x9"', $html);
        self::assertStringNotContainsString('aspect-21x9"', str_replace('docara-aspect-21x9', '', $html));
        self::assertStringContainsString('.docara-aspect-21x9{aspect-ratio:21/9}', (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css'));
    }

    #[Test]
    public function tabs_render_as_the_framework_underline_tabs_with_the_first_tab_selected(): void
    {
        $html = (new PortableMarkdownRenderer)->render(":::tabs\n### Linux\n\nОдин.\n\n### macOS\n\nДва.\n:::\n");

        self::assertMatchesRegularExpression(
            '~^<div data-docara-block="tabs" data-active-index="0" class="sf-tabs sf-tabs--underline flex flex-col m-bottom-1">'
            . '<div class="sf-tabs-top-container flex"><nav class="sf-tabs-top flex" role="tablist">'
            . '<button type="button" role="tab" id="(docara-tabs-[0-9a-f]{12})-tab-0" aria-controls="\\1-panel-0" aria-selected="true" data-docara-tab="linux" class="sf-button sf-button--default sf-button--primary sf-button--size-1 selected"><span class="sf-button-text-container">Linux</span></button>'
            . '<button type="button" role="tab" id="\\1-tab-1" aria-controls="\\1-panel-1" aria-selected="false" tabindex="-1" data-docara-tab="macos" class="sf-button sf-button--default sf-button--primary sf-button--size-1"><span class="sf-button-text-container">macOS</span></button>'
            . '</nav></div><div class="sf-tabs-main-container">'
            . '<section role="tabpanel" id="\\1-panel-0" aria-labelledby="\\1-tab-0" data-docara-tab="linux" class="sf-tabs-main-tab selected"><p>Один.</p></section>'
            . '<section role="tabpanel" id="\\1-panel-1" aria-labelledby="\\1-tab-1" data-docara-tab="macos" class="sf-tabs-main-tab" hidden><p>Два.</p></section>'
            . '</div></div>$~',
            trim($html),
        );
        // A block without a sync name carries no sync marker, and the
        // Framework tabs script alone switches its panels.
        self::assertStringNotContainsString('data-docara-tabs-sync', $html);

        // variant=pills is the Framework's rounded tabs style; the panels
        // may hold live examples.
        $pills = (new PortableMarkdownRenderer)->render("::::tabs {variant=pills}\n### Кнопки\n\n:::example\n```html\n<p>a</p>\n```\n:::\n\n### Поля\n\n:::example\n```html\n<p>b</p>\n```\n:::\n::::\n");
        self::assertStringContainsString('class="sf-tabs sf-tabs--pills flex flex-col m-bottom-1"', $pills);
        self::assertSame(2, substr_count($pills, 'data-docara-example="'));
        try {
            (new PortableMarkdownRenderer)->render(":::tabs {variant=segmented}\n### A\n\nx\n\n### B\n\ny\n:::\n");
            self::fail('Tabs accepted a style the Framework does not have.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function tabs_open_the_default_tab_and_name_the_synced_group(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            ":::tabs {sync=os default=\"Пакетный менеджер\"}\n### Linux\n\nОдин.\n\n### Пакетный менеджер\n\nДва.\n:::\n",
        );

        self::assertStringContainsString('data-docara-tabs-sync="os" data-active-index="1"', $html);
        self::assertStringContainsString('data-docara-tab="пакетный-менеджер" class="sf-button', $html);
        self::assertMatchesRegularExpression('~role="tab"[^>]*data-docara-tab="пакетный-менеджер"[^>]*selected~', $html);
        self::assertStringContainsString('data-docara-tab="пакетный-менеджер" class="sf-tabs-main-tab selected"', $html);
        self::assertStringContainsString('data-docara-tab="linux" class="sf-tabs-main-tab" hidden', $html);

        // The name of a tab also works in place of its label.
        $byKey = (new PortableMarkdownRenderer)->render(":::tabs {default=macos}\n### Linux\n\nОдин.\n\n### macOS\n\nДва.\n:::\n");
        self::assertStringContainsString('data-active-index="1"', $byKey);

        // The shell script passes a click on to the other blocks of the name.
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');
        self::assertStringContainsString("var syncPrefix='docara.tabs.';", $script);
        self::assertStringContainsString('[data-docara-block="tabs"][data-docara-tabs-sync]', $script);
    }

    #[Test]
    public function an_empty_diagram_label_stops_the_build(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('MARKDOWN_DIAGRAM_LABEL_EMPTY');
        (new PortableMarkdownRenderer)->render(":::diagram {label=\" \"}\nflowchart LR\n  A --> B\n:::\n");
    }

    #[Test]
    public function a_tabs_default_that_names_no_tab_stops_the_build(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('MARKDOWN_TABS_DEFAULT_NOT_FOUND');
        (new PortableMarkdownRenderer)->render(":::tabs {default=windows}\n### Linux\n\nОдин.\n\n### macOS\n\nДва.\n:::\n");
    }

    #[Test]
    public function a_tabs_sync_name_outside_the_vocabulary_stops_the_build(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID');
        (new PortableMarkdownRenderer)->render(":::tabs {sync=OS}\n### Linux\n\nОдин.\n\n### macOS\n\nДва.\n:::\n");
    }

    #[Test]
    public function details_render_as_the_framework_accordion_in_every_view(): void
    {
        foreach (['outline' => 'sf-accordion sf-accordion--outline', 'surface' => 'sf-accordion', 'lines' => 'sf-accordion sf-accordion--flush'] as $view => $classes) {
            $html = (new PortableMarkdownRenderer)->render(":::details {layout=$view}\n### Вопрос\n\nОтвет.\n:::\n");

            // Closed by default: the panel waits for find-in-page.
            self::assertStringContainsString(
                '<article data-docara-block="details" data-view="' . $view . '" class="' . $classes . ' m-bottom-1">'
                . '<div class="sf-accordion-heading"><button class="sf-accordion-trigger" type="button" aria-expanded="false">'
                . '<span class="sf-accordion-title flex-1 min-w-0">Вопрос</span>'
                . '<i class="sf-icon" aria-hidden="true">chevron_right</i></button></div>'
                . '<div class="sf-accordion-content" role="region" hidden="until-found"><p>Ответ.</p></div></article>',
                $html,
            );
        }

        $open = (new PortableMarkdownRenderer)->render(":::details {open=true}\n### Вопрос\n\nОтвет.\n:::\n");
        // Outline is the default layout.
        self::assertStringContainsString('class="sf-accordion sf-accordion--outline active m-bottom-1"><div class="sf-accordion-heading"><button class="sf-accordion-trigger" type="button" aria-expanded="true">', $open);
        self::assertStringContainsString('<div class="sf-accordion-content" role="region"><p>Ответ.</p></div>', $open);
        self::assertStringNotContainsString('<details', $open);

        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css');
        self::assertStringContainsString('[data-docara-block="details"]>.sf-accordion-content[hidden]{padding:0}', $css);
        self::assertStringNotContainsString('[data-docara-block="details"]>summary', $css);
    }

    #[Test]
    public function hero_renders_a_bounded_first_screen_with_an_optional_action_and_media(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::hero
# Документация, которую удобно развивать

Пишите содержимое в Markdown, а Docara соберёт адаптивный сайт.

[Начать работу](/start/)

![Схема сборки Docara](/assets/hero.png)
:::
MD);

        self::assertStringContainsString(
            '<section data-docara-block="hero" data-variant="split" data-docara-width="full" class="bg-surface-container',
            $html,
        );
        self::assertStringContainsString(
            '<div data-docara-container class="container m-inline-auto grid grid-col-1 lg:grid-col-2',
            $html,
        );
        self::assertStringContainsString('<h1 class="m-0">Документация, которую удобно развивать</h1>', $html);
        self::assertStringContainsString('<a data-docara-hero-action class="sf-button', $html);
        self::assertStringContainsString('href="/start/"', $html);
        self::assertStringContainsString(
            '<img data-docara-media="hero" loading="eager" fetchpriority="high" decoding="async" src="/assets/hero.png" alt="Схема сборки Docara" />',
            $html,
        );
    }

    #[Test]
    public function hero_groups_two_actions_into_one_responsive_row(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::hero
# Docara

Пишите содержимое в Markdown.

[Начать](/start/)

[Компоненты](/components/)
:::
MD);

        self::assertStringContainsString(
            '<div data-docara-hero-actions class="flex flex-wrap items-cross-center gap-1">',
            $html,
        );
        self::assertSame(2, substr_count($html, 'data-docara-hero-action class='));
        self::assertStringContainsString('>Начать</span></a>', $html);
        self::assertStringContainsString('>Компоненты</span></a>', $html);
    }

    #[Test]
    public function compact_hero_accepts_explicit_large_padding(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::hero {variant=compact padding=xl}
# Docara

Пишите содержимое в Markdown.
:::
MD);

        self::assertStringContainsString('data-docara-block="hero"', $html);
        // Inside the container the padding is vertical only (3.3.0).
        self::assertStringContainsString(' p-top-4 p-bottom-4', $html);
    }

    #[Test]
    public function four_features_use_the_four_column_responsive_grid(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::features
- One
- Two
- Three
- Four
:::
MD);

        self::assertStringContainsString(
            '<ul data-docara-block="features" class="grid grid-col-1 md:grid-col-2 lg:grid-col-4 gap-2 list-none m-0 m-bottom-1 p-0">',
            $html,
        );
    }

    #[Test]
    public function hero_fails_closed_when_its_semantic_contract_is_broken(): void
    {
        $cases = [
            [":::hero\nТолько текст.\n:::\n", 'MARKDOWN_HERO_H1_REQUIRED'],
            [":::hero\n# Заголовок\n:::\n", 'MARKDOWN_HERO_DESCRIPTION_REQUIRED'],
            [":::hero\n# Заголовок\n\nОдин.\n\nДва.\n\nТри.\n:::\n", 'MARKDOWN_HERO_DESCRIPTION_REQUIRED'],
            [":::hero\n# Заголовок\n\nТекст.\n\n[Пуск](javascript:alert(1))\n:::\n", 'MARKDOWN_HERO_LINK_UNSAFE'],
            [":::hero\n# Заголовок\n\nТекст.\n\n![Схема](data:image/png;base64,AA)\n:::\n", 'MARKDOWN_HERO_IMAGE_UNSAFE'],
            [":::hero\n# Заголовок\n\nТекст.\n\n![Схема](/image.png)\n\n[Пуск](/start/)\n:::\n", 'MARKDOWN_HERO_STRUCTURE_INVALID'],
        ];

        foreach ($cases as [$markdown, $expected]) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("Invalid hero unexpectedly rendered for [$expected].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function logos_render_a_compact_responsive_ecosystem_list(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::logos
- [SIMAI](https://simai.io/)
- ![Docara](/assets/docara.svg)
- SIMAI Effects
:::
MD);

        self::assertStringContainsString(
            '<ul data-docara-block="logos" data-tone="normal" class="grid grid-col-2 md:grid-col-3 lg:grid-col-6',
            $html,
        );
        self::assertSame(3, substr_count(
            $html,
            '<li class="min-w-0 flex items-cross-center content-main-center">',
        ));
        self::assertStringContainsString('<a href="https://simai.io/">SIMAI</a>', $html);
        self::assertStringContainsString(
            '<img data-docara-media="logo" loading="lazy" decoding="async" src="/assets/docara.svg" alt="Docara" />',
            $html,
        );
    }

    #[Test]
    public function showcase_renders_bounded_product_proof_with_lazy_media(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::showcase
## Проверяемый результат

Собранная документация видна до публикации.

[Открыть пример](/landing/)

![Интерфейс Docara](/assets/screen.png)
:::
MD);

        self::assertStringContainsString(
            '<section data-docara-block="showcase" data-docara-width="full" class="bg-surface-page',
            $html,
        );
        self::assertStringContainsString(
            '<img data-docara-media="showcase" loading="lazy" decoding="async" src="/assets/screen.png" alt="Интерфейс Docara" />',
            $html,
        );
        self::assertStringContainsString('data-docara-showcase-action', $html);
    }

    #[Test]
    public function promo_renders_one_action_and_explicit_decorative_media(): void
    {
        $html = (new PortableMarkdownRenderer)->render(<<<'MD'
:::promo
## Соберите первый сайт

Создайте проект и получите статический результат.

[Начать](/start/)

![](/assets/promo.png)
:::
MD);

        self::assertStringContainsString(
            '<section data-docara-block="promo" data-docara-width="full" class="bg-surface-container',
            $html,
        );
        self::assertStringContainsString(
            '<img data-docara-media="promo" loading="lazy" decoding="async" aria-hidden="true" src="/assets/promo.png" alt="" />',
            $html,
        );
        self::assertStringContainsString('data-docara-promo-action', $html);
    }

    #[Test]
    public function showcase_and_promo_fail_closed_for_unsafe_or_incomplete_content(): void
    {
        $cases = [
            [":::showcase\n## Proof\n\nText.\n:::\n", 'MARKDOWN_SHOWCASE_IMAGE_REQUIRED'],
            [":::showcase\n## Proof\n\nText.\n\n![](/screen.png)\n:::\n", 'MARKDOWN_SHOWCASE_IMAGE_ALT_REQUIRED'],
            [":::showcase\n## Proof\n\nText.\n\n![UI](javascript:alert(1))\n:::\n", 'MARKDOWN_SHOWCASE_IMAGE_UNSAFE'],
            [":::promo\n## Start\n\nText.\n:::\n", 'MARKDOWN_PROMO_LINK_REQUIRED'],
            [":::promo\n## Start\n\nText.\n\n[Go](data:text/html,unsafe)\n:::\n", 'MARKDOWN_PROMO_LINK_UNSAFE'],
        ];

        foreach ($cases as [$markdown, $expected]) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("Invalid media block unexpectedly rendered for [$expected].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function logos_fail_closed_for_unbounded_or_unsafe_content(): void
    {
        $thirteen = implode("\n", array_fill(0, 13, '- Logo'));
        $cases = [
            [":::logos\n1. One\n2. Two\n:::\n", 'MARKDOWN_LOGOS_UNORDERED_LIST_REQUIRED'],
            [":::logos\n- One\n:::\n", 'MARKDOWN_LOGOS_ITEM_COUNT_INVALID'],
            [":::logos\n{$thirteen}\n:::\n", 'MARKDOWN_LOGOS_ITEM_COUNT_INVALID'],
            [":::logos\n- One\n  - Nested\n- Two\n:::\n", 'MARKDOWN_LOGOS_ITEM_CONTENT_INVALID'],
            [":::logos\n- [Unsafe](javascript:alert(1))\n- Two\n:::\n", 'MARKDOWN_LINK_URL_UNSAFE'],
            [":::logos\n- ![Unsafe](data:image/png;base64,AA)\n- Two\n:::\n", 'MARKDOWN_IMAGE_URL_UNSAFE'],
            [":::logos\n- ![](/empty.svg)\n- Two\n:::\n", 'MARKDOWN_LOGOS_ITEM_CONTENT_INVALID'],
        ];

        foreach ($cases as [$markdown, $expected]) {
            try {
                (new PortableMarkdownRenderer)->render($markdown);
                self::fail("Invalid logos block unexpectedly rendered for [$expected].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
        }
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
                . '" class="' . $classes . ' m-bottom-1">',
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
            // league/commonmark before 2.10.7 reads [ ] here as a checkbox the
            // block cannot hold; 2.10.7 reads an empty link. Both are refused.
            [":::features\n- [ ](/empty/)\n- Text\n:::\n", ['MARKDOWN_FEATURES_ITEM_CONTENT_INVALID', 'MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED']],
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
                self::assertContains($exception->errorCode, (array) $expected);
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
    public function raw_html_cannot_hide_later_directives_inside_an_opaque_commonmark_block(): void
    {
        $this->expectExceptionObject(new PortableConfigurationException(
            'MARKDOWN_RAW_HTML_FORBIDDEN',
            'Raw HTML is forbidden at [@markdown:5:1]. Use Markdown or the sandboxed html/example directive.',
        ));

        (new PortableMarkdownRenderer)->render(
            ":::card\nFirst\n\n:::\n<span>\n:::card\nSecond\n\n:::\n</span>\n",
        );
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
        foreach (['card', 'steps', 'cta', 'features', 'hero', 'logos'] as $outer) {
            $body = $outer === 'steps' ? "1. First step\n" : "Card body\n";
            if ($outer === 'cta') {
                $body = "[Start](/start/)\n";
            } elseif ($outer === 'features') {
                $body = "- First\n- Second\n";
            } elseif ($outer === 'hero') {
                $body = "# Heading\n\nDescription.\n";
            } elseif ($outer === 'logos') {
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
        foreach (['card', 'steps', 'cta', 'features', 'hero', 'logos'] as $inner) {
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
        $markdown = str_repeat(":::card\nBody\n:::\n", 129);

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
                str_repeat($portable, 64) . str_repeat($framework, 65),
                'FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED',
            ],
            [
                str_repeat($framework, 64) . str_repeat($portable, 65),
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
    public function checklists_definitions_and_marks_are_plain_markdown(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            "- [x] Done\n- [ ] Open\n\nRecipe\n: The build plan of one page.\n\nA ==found== word.\n",
        );

        self::assertStringContainsString('<input checked="" disabled="" type="checkbox"> Done', $html);
        self::assertStringContainsString('<input disabled="" type="checkbox"> Open', $html);
        self::assertStringContainsString('<dt>Recipe</dt>', $html);
        self::assertStringContainsString('<dd>The build plan of one page.</dd>', $html);
        self::assertStringContainsString('<mark>found</mark>', $html);
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

    #[Test]
    public function an_example_has_no_log_unless_it_asks_for_one(): void
    {
        $html = (new PortableMarkdownRenderer)->render(":::example\n```html\n<button type=\"button\">Go</button>\n```\n:::\n");

        self::assertStringNotContainsString('data-docara-example-log', $html);
        self::assertStringNotContainsString('docara:example-log', $html);
    }

    #[Test]
    public function an_inline_example_log_lists_its_events_tabs_and_controls_under_the_panels(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            ":::example {log=\"click sf-button-press\" log_console=true log_open=false}\n```html\n<button type=\"button\">Go</button>\n```\n:::\n",
        );

        self::assertStringContainsString('data-docara-example-preview-resolved="inline"', $html);
        self::assertMatchesRegularExpression(
            '~<div class="docara-example-preview__panels">.*</div><section data-docara-example-log data-docara-example-log-events="click sf-button-press"'
            . ' data-docara-example-log-console="true" data-docara-example-log-state="collapsed" aria-label="Example log" class="docara-example-log">~s',
            $html,
        );
        self::assertStringContainsString('role="tablist" aria-label="Example log" class="docara-example-preview__tabs docara-example-log__tabs"', $html);
        self::assertStringContainsString('aria-selected="true" tabindex="0" data-docara-example-log-tab="events" class="docara-example-preview__tab docara-example-log__tab"', $html);
        self::assertStringContainsString('aria-selected="false" tabindex="-1" data-docara-example-log-tab="console"', $html);
        self::assertSame(2, substr_count($html, 'data-docara-example-log-count class="docara-example-log__count">0</span>'));
        self::assertStringContainsString('<sf-icon icon="delete" aria-hidden="true"></sf-icon>', $html);
        self::assertStringContainsString('aria-expanded="false" aria-label="Expand the log"', $html);
        self::assertStringContainsString('<sf-icon icon="expand_more" aria-hidden="true"></sf-icon>', $html);
        self::assertMatchesRegularExpression('~<div id="[^"]+-log-panels" hidden class="docara-example-log__panels">~', $html);
        self::assertSame(2, substr_count($html, '<div role="log" class="docara-example-log__rows"></div>'));
        self::assertStringContainsString('<span class="docara-example-log__indicator" aria-hidden="true"></span>', $html);
        self::assertStringNotContainsString('<svg', $html);
        // The device-width viewer shows the inline example in a frame of its
        // own: that document records the same events and console.
        preg_match('~<iframe data-docara-example-responsive-frame [^>]*data-docara-srcdoc="([^"]*)"~', $html, $frame);
        $document = html_entity_decode($frame[1] ?? '', ENT_QUOTES | ENT_HTML5);
        self::assertStringContainsString('<script data-docara-example-log-runtime>(function(){var config={"events":["click","sf-button-press"],"console":true};', $document);
    }

    #[Test]
    public function a_framed_example_log_runtime_runs_before_the_example_script(): void
    {
        $html = (new PortableMarkdownRenderer)->render(
            ":::example {log=sf-button-press}\n```html\n<button type=\"button\">Go</button>\n```\n```javascript\nconsole.log('ready');\n```\n:::\n",
        );

        self::assertStringContainsString('data-docara-example-preview-resolved="sandbox"', $html);
        self::assertStringContainsString('data-docara-example-log-console="false" data-docara-example-log-state="expanded"', $html);
        self::assertStringNotContainsString('data-docara-example-log-tab="console"', $html);
        preg_match('~data-docara-srcdoc="([^"]*)"~', $html, $frame);
        $document = html_entity_decode($frame[1] ?? '', ENT_QUOTES | ENT_HTML5);
        $runtime = strpos($document, '<script data-docara-example-log-runtime>');
        self::assertIsInt($runtime);
        self::assertLessThan(strpos($document, '</head>'), $runtime);
        self::assertLessThan(strpos($document, "console.log('ready');"), $runtime);
        self::assertStringContainsString('var config={"events":["sf-button-press"],"console":false};', $document);
    }

    #[Test]
    public function a_console_log_alone_turns_the_log_on_with_both_tabs(): void
    {
        $html = (new PortableMarkdownRenderer)->render(":::example {log_console=true}\n```markdown\nText\n```\n:::\n");

        self::assertStringContainsString('data-docara-example-log-events="" data-docara-example-log-console="true"', $html);
        self::assertStringContainsString('data-docara-example-log-tab="events"', $html);
        self::assertStringContainsString('data-docara-example-log-tab="console"', $html);
    }

    #[Test]
    public function invalid_example_log_parameters_stop_the_build_with_a_diagnostic(): void
    {
        $cases = [
            '{log="1click"}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            '{log="click click"}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            '{log="on<click>"}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            '{log=" "}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            '{log="' . implode(' ', array_map(static fn (int $index): string => 'event' . $index, range(1, 17))) . '"}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            '{log=click log_console=yes}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            '{log=click log_open=1}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            '{log_open=false}' => 'MARKDOWN_EXAMPLE_LOG_REQUIRED',
            '{log_console=false}' => 'MARKDOWN_EXAMPLE_LOG_REQUIRED',
            '{logs=click}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN',
        ];
        $renderer = new PortableMarkdownRenderer;
        foreach ($cases as $attributes => $code) {
            $markdown = ":::example $attributes\n```html\n<p>Text</p>\n```\n:::\n";
            foreach (['render', 'examplePreviews'] as $method) {
                try {
                    $renderer->{$method}($markdown);
                    self::fail("[$attributes] was accepted by $method.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame($code, $exception->errorCode, "$attributes via $method");
                }
            }
        }

        try {
            $renderer->render("::::example {src=\"/demo/\" log=click}\n### A\n\ntext\n::::\n");
            self::fail('A page example accepted a log.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_EXAMPLE_SRC_CONFLICT', $exception->errorCode);
        }
    }

    #[Test]
    public function every_example_frame_may_write_to_the_clipboard(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $framed = $renderer->render(":::example\n```html\n<sf-button-copy text=\"x\"></sf-button-copy>\n```\n```javascript\n1;\n```\n:::\n");
        $responsive = $renderer->render(":::example {fullscreen=true}\n```html\n<p>Text</p>\n```\n:::\n");
        $page = $renderer->render("::::example {src=\"/demo/\"}\n### A\n\ntext\n::::\n");

        self::assertStringContainsString('data-docara-example-preview-resolved="sandbox"', $framed);
        self::assertMatchesRegularExpression('~<iframe [^>]*data-docara-example-frame [^>]*sandbox="allow-scripts" allow="clipboard-write" ~', $framed);
        self::assertStringContainsString('data-docara-example-preview-resolved="inline"', $responsive);
        self::assertMatchesRegularExpression('~<iframe data-docara-example-responsive-frame [^>]*allow="clipboard-write"~', $responsive);
        self::assertMatchesRegularExpression('~<iframe src="/demo/"[^>]*data-docara-example-page[^>]*allow="clipboard-write"~', $page);
        // The permission widens no sandbox: the frame stays an opaque origin.
        self::assertStringNotContainsString('allow-same-origin', $framed);
        self::assertStringNotContainsString('allow-same-origin', $responsive);
        // An embed of another site keeps its own policy.
        $embed = $renderer->render(":::embed {provider=video}\n[Open](https://www.youtube-nocookie.com/embed/example)\n:::\n");
        self::assertStringNotContainsString('clipboard-write', $embed);
    }

    #[Test]
    public function an_inline_html_example_keeps_its_own_code_blocks(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $html = $renderer->render(
            ":::example\n```html\n<pre><code class=\"language-go\">fmt.Println(1)</code></pre>\n<pre><code>plain</code></pre>\n```\n:::\n\n```js\nconsole.log(1)\n```\n",
        );

        self::assertStringContainsString('data-docara-example-preview-resolved="inline"', $html);
        // The example shows the author's code blocks as they are: a Framework
        // highlight inside it draws its own chrome.
        self::assertStringContainsString('<pre><code class="language-go">fmt.Println(1)</code></pre>', $html);
        self::assertStringContainsString('<pre><code>plain</code></pre>', $html);
        $preview = substr($html, (int) strpos($html, 'data-docara-example-inline-preview="html"'));
        $preview = substr($preview, 0, (int) strpos($preview, '<pre><code>plain</code></pre>'));
        self::assertStringNotContainsString('data-docara-code-block', $preview);
        // A code block of the page around it is still Docara's.
        self::assertSame(1, substr_count($html, 'data-docara-code-language="javascript"') + substr_count($html, 'data-docara-code-language="js"'));
        self::assertStringNotContainsString('DOCARA_INLINE_EXAMPLE_', $html);
    }

    #[Test]
    public function a_same_document_link_stays_inside_its_example_frame(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $html = $renderer->render(":::example\n```html\n<a href=\"#part\">Part</a><p id=\"part\">Part</p>\n```\n```javascript\n1;\n```\n:::\n");
        preg_match('~data-docara-srcdoc="([^"]*)"~', $html, $frame);
        $document = html_entity_decode($frame[1] ?? '', ENT_QUOTES | ENT_HTML5);

        self::assertStringContainsString('data-docara-example-preview-resolved="sandbox"', $html);
        self::assertStringContainsString("href.charAt(0)!=='#'", $document);
        self::assertStringContainsString('event.preventDefault();', $document);
        self::assertStringContainsString('target.scrollIntoView', $document);
        self::assertMatchesRegularExpression('~<iframe [^>]*data-docara-example-frame [^>]*class="w-full border-0 bg-surface-page"~', $html);
    }

    #[Test]
    public function an_example_keeps_the_page_theme_unless_it_switches_its_own(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $default = $renderer->render(":::example\n```html\n<p>Text</p>\n```\n:::\n");
        $off = $renderer->render(":::example {theme_switch=false}\n```html\n<p>Text</p>\n```\n:::\n");

        foreach ([$default, $off] as $html) {
            self::assertStringContainsString('data-docara-example-preview-resolved="inline"', $html);
            self::assertStringNotContainsString('<html data-docara-example-own-theme>', html_entity_decode($html, ENT_QUOTES | ENT_HTML5));
            self::assertStringNotContainsString('data-docara-example-own-theme-runtime', html_entity_decode($html, ENT_QUOTES | ENT_HTML5));
        }
        self::assertSame(
            [['id' => '', 'mode' => 'inline', 'requested_preview' => 'auto', 'resolved_preview' => 'inline', 'reason' => 'admitted_html']],
            $renderer->examplePreviews(":::example {theme_switch=false}\n```html\n<p>Text</p>\n```\n:::\n"),
        );
    }

    #[Test]
    public function an_example_with_its_own_theme_gets_a_frame_with_the_loader_theme_and_a_memory_cookie_jar(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $markdown = ":::example {theme_switch=true}\n```html\n<sf-button-group appearance=\"segmented\" theme-switch></sf-button-group>\n```\n:::\n";
        $html = $renderer->render($markdown);

        self::assertStringContainsString('data-docara-example-preview-resolved="sandbox"', $html);
        self::assertStringContainsString('data-docara-example-preview-reason="theme_switch"', $html);
        self::assertSame(
            [['id' => '', 'mode' => 'inline', 'requested_preview' => 'auto', 'resolved_preview' => 'sandbox', 'reason' => 'theme_switch']],
            $renderer->examplePreviews($markdown),
        );
        preg_match('~data-docara-srcdoc="([^"]*)"~', $html, $frame);
        $document = html_entity_decode($frame[1] ?? '', ENT_QUOTES | ENT_HTML5);
        self::assertStringStartsWith('<!doctype html><html data-docara-example-own-theme><head>', $document);
        // The cookie jar is in place before any boot state or Framework code.
        $jar = strpos($document, '<script data-docara-example-own-theme-runtime>');
        self::assertIsInt($jar);
        self::assertLessThan(strpos($document, '</head>'), $jar);
        self::assertStringContainsString("Object.defineProperty(document,'cookie'", $document);
        // The frame runtime turns the Loader's theme back on only for its own theme.
        self::assertStringContainsString('if(ownTheme&&window.SF_BOOT_CONFIG&&window.SF_BOOT_CONFIG.theme===false){delete window.SF_BOOT_CONFIG.theme}', $document);
        self::assertStringContainsString("var ownTheme=document.documentElement.hasAttribute('data-docara-example-own-theme')", $document);

        // Sandbox stays the author's explicit choice too.
        self::assertStringContainsString(
            'data-docara-example-preview-reason="forced_sandbox"',
            $renderer->render(":::example {theme_switch=true preview=sandbox}\n```markdown\nText\n```\n:::\n"),
        );
    }

    #[Test]
    public function a_desktop_example_gets_a_frame_that_scrolls_sideways_under_a_chip(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $markdown = ":::example {viewport=desktop label=\"Site row\"}\n```markdown\nText\n```\n:::\n";
        $html = $renderer->render($markdown);

        // Owner decision C111: only a frame of its own can be 1280 wide.
        self::assertStringContainsString('data-docara-example-preview-reason="desktop_viewport"', $html);
        self::assertSame(
            [['id' => '', 'mode' => 'inline', 'requested_preview' => 'auto', 'resolved_preview' => 'sandbox', 'reason' => 'desktop_viewport']],
            $renderer->examplePreviews($markdown),
        );
        self::assertMatchesRegularExpression('~<section [^>]* data-docara-example-desktop[ >]~', $html);
        self::assertMatchesRegularExpression(
            '~<div data-docara-example-scroll class="docara-example-scroll"><div data-docara-example-scroll-body data-docara-example-scrolling="false" class="docara-example-scroll__body"><iframe [^>]*data-docara-example-frame~',
            $html,
        );
        self::assertStringContainsString('<span data-docara-example-desktop-chip hidden class="docara-example-preview__chip">1280 · 100 % · scroll</span>', $html);

        // A column example has none of it.
        $column = $renderer->render(":::example {viewport=column}\n```markdown\nText\n```\n:::\n");
        self::assertStringNotContainsString('data-docara-example-desktop', $column);
        self::assertStringNotContainsString('data-docara-example-scroll', $column);

        // The shell scrolls the body only from a 512 column, fades the side
        // that still hides content and sets the frame back in the viewer.
        $shell = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.js');
        self::assertStringContainsString('var scrolls=!viewer&&width>=512;', $shell);
        self::assertStringContainsString("previewFrame.style.inlineSize=exampleViewportWidths.desktop+'px'", $shell);
        self::assertStringContainsString("desktopBody.parentElement.toggleAttribute('data-more-end',hidden>1&&left<hidden-1);", $shell);
        self::assertStringContainsString("fitPageFrame();\n      fitDesktopFrame();", $shell);
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/portable/declarative-shell.css');
        self::assertStringContainsString('.docara-example-scroll__body[data-docara-example-scrolling="true"]{overflow-x:auto;overscroll-behavior-x:contain}', $css);
        // In the viewer the frame is the panel's own child again, sized by the device.
        self::assertStringContainsString('[data-docara-example-viewer-active="true"] :is(.docara-example-scroll,.docara-example-scroll__body){display:contents}', $css);

        $cases = [
            ':::example {viewport=desktop preview=inline}' => 'MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED',
            ':::example {viewport=phone}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
        ];
        foreach ($cases as $opening => $code) {
            foreach (['render', 'examplePreviews'] as $method) {
                try {
                    $renderer->{$method}("$opening\n```html\n<p>Text</p>\n```\n:::\n");
                    self::fail("[$opening] was accepted by $method.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame($code, $exception->errorCode, "$opening via $method");
                }
            }
        }
        try {
            $renderer->render("::::example {src=\"/demo/\" viewport=desktop}\n### A\n\ntext\n::::\n");
            self::fail('A page example accepted viewport.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_EXAMPLE_SRC_CONFLICT', $exception->errorCode);
        }
    }

    #[Test]
    public function an_example_theme_switch_fails_closed_where_it_would_reach_the_page(): void
    {
        $cases = [
            ':::example {theme_switch=local}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            ':::example {theme_switch=yes}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
            ':::example {theme_switch=true preview=inline}' => 'MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED',
            ':::example {theme=local}' => 'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN',
        ];
        $renderer = new PortableMarkdownRenderer;
        foreach ($cases as $opening => $code) {
            $markdown = "$opening\n```html\n<p>Text</p>\n```\n:::\n";
            foreach (['render', 'examplePreviews'] as $method) {
                try {
                    $renderer->{$method}($markdown);
                    self::fail("[$opening] was accepted by $method.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame($code, $exception->errorCode, "$opening via $method");
                }
            }
        }

        foreach (['render', 'examplePreviews'] as $method) {
            try {
                $renderer->{$method}("::::example {src=\"/demo/\" theme_switch=true}\n### A\n\ntext\n::::\n");
                self::fail("A page example accepted theme_switch via $method.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_EXAMPLE_SRC_CONFLICT', $exception->errorCode);
            }
        }
    }
}
