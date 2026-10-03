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

`preview=auto` is the default. Typed Markdown and admitted HTML-only examples
render inline; examples containing CSS, JavaScript or non-admitted HTML render
in a sandboxed iframe. Reusable project examples follow the same rule as
examples written in the page. `preview=sandbox` forces isolation.
`preview=inline` requests direct rendering but still fails closed when the
inline policy requires isolation. The build receipt and page inspection expose
the requested mode, resolved mode and decision reason.

The inline policy admits a fixed set of non-executable elements and
attributes. Beyond the basic list it admits:

- any `data-*` attribute except `data-docara-*`, which belongs to the shell;
  no attribute value may start with a `javascript:`, `vbscript:` or `data:`
  scheme;
- `id`, written in kebab case with at least one hyphen (`save-tooltip`) and
  not starting with `docara-`, so it cannot shadow a global script name;
- `slot` with a plain kebab-case token (`content`, `footer`); it only names
  the part of a Smart component the element fills;
- `form` without `action`, `method`, `target`, `enctype` or `name`; the shell
  cancels every `submit` that starts inside an inline example, so a demo form
  never navigates or reloads the documentation page;
- on a Smart element (`sf-*`), the attributes the project's Framework lock
  declares for that tag in `runtime.components["sf-…"].attributes`. A tag
  with an empty or missing list admits only the basic attributes.

`on*`, `href`, `src`, `srcdoc`, `srcset`, `style`, `action`, `formaction` and
the other `form*` overrides, the `form` attribute, `autofocus` and
`contenteditable` are never admitted, even when a lock declares them. `script`,
`style`, `link`, `iframe`, `object`, `embed`, `a`, `img` and every other
element outside the list keep the example in the sandbox.

Ids in an inline example share the page's id space. Heading anchors already
avoid ids that the page uses; every other collision fails the build with
`MARKDOWN_EXAMPLE_INLINE_ID_DUPLICATE`, naming the id, the example and the
page. The check runs on the finished page, so it sees the shell, other
inline examples and repeated uses of the same reusable example.

An inline HTML example sits inside a `.docara-example-inline` root. Shell
prose rules stop at that root, so the markup gets the same Framework styles it
gets in a frame, and its floating surfaces (lists, menus, tooltips) open over
the page instead of inside the frame's box. Framework components in
the inline markup load with the page: the asset planner reads the finished
page HTML, so the same set the frame would receive is planned at build time.
Author CSS and JavaScript never run in the documentation page.
Sandboxed results expand to their measured content height without nested
scrolling or a second shell height animation; the documentation page owns
vertical scrolling.

An isolated result can expose one responsive-preview action. It opens the whole
Example surface and then reveals Desktop (1280 px), Tablet (768 px) and Mobile
(390 px) viewport choices. The choices change the real iframe width, so media
queries are evaluated against the selected viewport. Inline results do not
expose this action because narrowing their wrapper would not change the browser
viewport and would produce misleading responsive behaviour. On narrow reader
screens the entry action is hidden because the example already uses the
available mobile width. Source tabs can expose CSS-only soft wrapping without
changing source or copied bytes. Both controls default to enabled. The
`examples.fullscreen` and `examples.wrap` booleans inherit through site,
section and page configuration; `fullscreen=true|false` and `wrap=true|false`
on an individual `example` block are the final override. The `fullscreen` name
is retained for configuration compatibility; it now controls responsive
preview availability. Standalone fenced and
external code blocks expose the same soft-wrap action when inherited
`code.wrap` is enabled. The reader preference is shared between standalone code
and Example source tabs, while the settings independently control where the
action is available.

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

## Reusable project examples

An inline `:::example` remains the smallest option for a unique demonstration.
A reusable demonstration lives under `examples/<id>/` with required
`index.html`, optional `index.css`, `index.js`, and `assets/`, and is referenced
as `:::example {id="<id>" label="Result"}`. One block uses either an ID or
inline sources, never both. The build records source hashes, consumers, and
published assets in `.docara/examples.json`; changing a referenced example
requires a full build.
