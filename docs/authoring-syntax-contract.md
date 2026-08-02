# Authoring Syntax Contract

Status: implemented in the current Docara development candidate

Docara exposes one authoring language. Authors use Markdown and semantic
Docara components; internal PHP renderers and SIMAI Framework implementation
identifiers are not part of the public content format.

## Component kinds

| Kind | Public syntax | Use |
| --- | --- | --- |
| Native Markdown | CommonMark | headings, paragraphs, links, lists, quotes, code and tables |
| Inline | `:name[text]{parameters}` | a small semantic element inside a paragraph |
| Block | fenced `:::name {parameters}` | a standalone semantic section |
| Container | a longer outer fence, for example `::::grid` | controlled composition of admitted child components |

A registry entry fixes the kind. Parameters cannot silently turn an inline
component into a block or change a block into an unrestricted container.

## Inline syntax

```markdown
Для установки нужен :badge[PHP 8.2]{type=tonal scheme=info size=1/2}.

Нажмите :kbd[⌘ K], затем :button[Откройте справочник]{href=/ru/components/ type=link}.
```

The initial inline surface is `badge`, `button`, `icon` and `kbd`. Square
brackets contain visible text; braces contain typed named parameters. Inline
components are ignored inside code spans and fenced code blocks.

## Blocks and containers

```markdown
:::details {open=true}
## Дополнительное объяснение

Содержимое остаётся обычным Markdown.
:::
```

Controlled nesting uses a longer fence for the parent:

```markdown
::::grid {columns=3 gap=2}
:::card
Первая карточка
:::
:::card
Вторая карточка
:::
:::card
Третья карточка
:::
::::
```

The parent owns layout (`columns`, `gap`, responsive stacking); the child owns
meaning. Names such as `card-1/4` are forbidden. An unsupported child, an
unclosed fence or an invalid parameter fails with a stable diagnostic.

## Executable examples

The `example` block presents a live result and its source in one tabbed
surface. A Markdown example contains one `markdown` fence. A browser example
contains an `html` fence and may additionally contain one `css` and one
`javascript` fence. The rendered result and every source tab are derived from
the same authored block.

````markdown
:::example {label=Example}
```html
<button id="hello">Hello</button>
```
```css
#hello { color: var(--sf-primary); }
```
```javascript
document.querySelector('#hello').dataset.ready = 'true';
```
:::
````

Markdown cannot be mixed with HTML/CSS/JavaScript in one example. Browser
examples require HTML. Unknown source types, duplicate sources and additional
free text fail closed.

## Parameters and naming

All components use the same attribute grammar:

```markdown
{name=value enabled=true ratio=16/9}
```

- names are stable lower-case semantic identifiers;
- presentation choices are parameters, not new component names;
- `button` replaces the old CTA primitive;
- `grid + card + icon` replaces the old Features primitive;
- `hero` introduces a page; `promo` closes a landing page;
- raw `ui.*`, `<sf-*>` and renderer names are provenance, not author syntax.

## Component reference

Every public component route has one physical Markdown owner in
`content/<locale>/components/<slug>.md`. The effective machine catalogue
validates the callable surface but never generates page prose. A supported
page contains:

- a short purpose statement;
- one primary executable example with copy-ready source;
- readable parameter definitions;
- compact executable variation examples;
- only limitations that materially help the author.

Unavailable requirements stay machine-only until an author has a useful
public page and the runtime contract is admitted.

The build consumes `resources/component-catalog/source-metadata.json`, so the
same facts remain available in a portable archive without `.git`. Before
packaging, refresh it with:

```bash
php scripts/capture-component-source-metadata.php
```

## Safety and acceptance

- raw HTML is not a default escape hatch;
- links, images and embeds are validated by their owning contract;
- nested fences pair deterministically;
- invalid combinations fail closed;
- every supported entry has renderer, tests, docs and an executable fixture;
- every declared state or variant has an exact marker in that fixture;
- physical component pages and receipts are verified against the same route set;
- Framework implementation details remain replaceable without changing
  authored Markdown.
