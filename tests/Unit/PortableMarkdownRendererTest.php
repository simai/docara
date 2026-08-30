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

:::example {label=Source}
```html
<strong>Safe source</strong>
```
:::
MD);
        self::assertStringContainsString('sandbox srcdoc=', $sandboxed);
        self::assertStringContainsString('sandbox="allow-scripts"', $sandboxed);
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
        self::assertStringContainsString('class="docara-example-preview__tab">Пример</button>', $markdown);
        self::assertStringContainsString('data-docara-example-tab="markdown"', $markdown);
        self::assertStringContainsString(
            'data-docara-example-panel="markdown" aria-hidden="true" class="docara-example-preview__panel docara-example-preview__panel--source"',
            $markdown,
        );
        self::assertStringContainsString('data-docara-example-tab="example" class="docara-example-preview__tab"', $markdown);
        self::assertStringContainsString('data-docara-example-tab="markdown" class="docara-example-preview__tab"', $markdown);
        self::assertStringContainsString('data-docara-example-copy', $markdown);
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
        self::assertStringNotContainsString('style=', $markdown);
        self::assertStringContainsString('sf-badge--success', $markdown);

        $table = $renderer->render(<<<'MD'
:::example {label=Таблица}
```markdown
| Имя | Значение |
| --- | --- |
| theme | system |
```
:::
MD);
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
        self::assertSame(2, substr_count($nestedCode, 'data-docara-code-block'));

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
        self::assertStringNotContainsString('body.scrollHeight,root.scrollHeight', $web);
        self::assertStringContainsString('function measureSettled()', $web);
        self::assertStringContainsString('link.setAttribute(&apos;data-docara-example-framework-style&apos;,&apos;&apos;)', $web);
        self::assertStringContainsString('document.documentElement.classList.add(&apos;theme-&apos;+theme)', $web);
        self::assertStringContainsString('data-docara-example-tab="html"', $web);
        self::assertStringContainsString('data-docara-example-tab="css"', $web);
        self::assertStringContainsString('data-docara-example-tab="javascript"', $web);
        self::assertStringContainsString('&lt;style&gt;#hello { color: red; }&lt;/style&gt;', $web);
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
        self::assertStringContainsString('class="aspect-21x9 ', $cinemaEmbed);
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
    public function interactive_tree_branches_are_keyboard_operable_and_static_trees_have_no_controls(): void
    {
        $renderer = new PortableMarkdownRenderer;
        $interactive = $renderer->render(<<<'MD'
:::tree {interactive=true}
- content
  - ru
    - index.md
- docara.json
:::
MD);
        self::assertStringContainsString('data-docara-block="tree" data-interactive="true"', $interactive);
        self::assertSame(2, substr_count($interactive, 'data-docara-tree-toggle'));
        self::assertSame(2, substr_count($interactive, 'aria-expanded="true"'));
        self::assertStringNotContainsString('<span class="min-w-0">index.md</span>', $interactive);

        $static = $renderer->render(<<<'MD'
:::tree {interactive=false}
- content
  - index.md
- docara.json
:::
MD);
        self::assertStringContainsString('data-interactive="false"', $static);
        self::assertStringNotContainsString('data-docara-tree-toggle', $static);
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
            '<section data-docara-block="card" data-docara-card-variant="default" class="bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1 m-bottom-1">',
            $html,
        );
        self::assertStringContainsString(
            '<ol class="m-0 p-0">',
            $html,
        );
        self::assertStringContainsString('class="docara-step grid items-cross-start gap-1 list-none p-block-end-2"', $html);
        self::assertStringContainsString('class="docara-step-marker inline-flex items-cross-center content-main-center"', $html);
        self::assertStringContainsString('<div class="docara-step-content">Установите PHP-зависимости.</div>', $html);
        self::assertStringNotContainsString('absolute inset-inline-start-0', $html);
        self::assertStringContainsString(
            '<div data-docara-table-scroll class="overflow-auto m-bottom-1"><table class="table table-border table-stripe">',
            $html,
        );
        self::assertStringContainsString(
            '<div data-docara-code-block data-sf-highlight-chrome="static" data-docara-code-language="php" class="source init docara-code-block min-w-0 overflow-hidden bg-surface-container border border-outline-variant radius-2 m-bottom-1">',
            $html,
        );
        self::assertStringContainsString('data-docara-code-fallback', $html);
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
        self::assertStringContainsString('class="source init docara-code-block', $html);
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
            '<li class="bg-surface-0 border border-outline-variant radius-2 p-3 flex min-w-0 max-w-none flex-col gap-1">',
        ));
        self::assertStringContainsString(
            '<img data-docara-media="feature-icon" loading="lazy" decoding="async" src="/assets/markdown.png" alt="" />',
            $html,
        );
        self::assertStringNotContainsString('<sf-button', $html);
        self::assertStringNotContainsString('docara-feature-card', $html);
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
        self::assertStringContainsString(' p-4', $html);
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
- Larena
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
            '<section data-docara-block="showcase" data-docara-width="full" class="bg-surface-0',
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
