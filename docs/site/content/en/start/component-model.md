# How components work

A documentation page starts as plain Markdown. You need a component only when
a fragment has its own meaning, parameters, state or composition rules. Every
option goes the same way: Markdown and settings → Recipe → a finished
Composition Document → HTML. Docara creates the Recipe automatically, and
the shared SIMAI Framework builder checks the structure of the page.

Docara uses a typed `DocumentIr` to read Markdown. It does not replace the
Composition Document, which describes the finished page with its regions,
sections and blocks. [Architecture](/en/development/architecture/) explains the build stages.

## How to choose

| Task | Use | Where to look |
| --- | --- | --- |
| A heading, paragraph, link, image, list, quote, code or table | Plain Markdown | [Markdown](/en/authoring/markdown/) |
| A short label, link button, icon or key inside a line | An inline Docara component | [Component catalogue](/en/components/) |
| A self-contained fragment with its own meaning | A Docara block | [Component catalogue](/en/components/) |
| Several nested blocks with constraints | A Docara container | [Surface](/en/components/surface/) and [Grid](/en/components/grid/) |
| A ready, checked interface element from SIMAI Framework | A Framework Smart component | [Framework components](/en/development/smart-components/#a-new-simai-framework-component) |
| A component of one project | A project Smart component or a shell contribution | [Developer SDK](/en/development/developer-sdk/) |

The linked pages, except Markdown, are in Russian.

## Plain Markdown

This is the main and preferred format. It covers headings, text, links,
images, lists, quotes, inline code and code blocks, tables and footnotes.
Raw HTML on a page is not allowed: the build stops with
`MARKDOWN_RAW_HTML_FORBIDDEN`. Every supported construction is listed in the
[component catalogue](/en/components/) and described in the
[Markdown guide](/en/authoring/markdown/).

## Inline Docara components

An inline component does not break the paragraph:

```markdown
The build is :badge[ready]{variant=main scheme=success size=1/2}.
Press :kbd[⌘ K] or :button[open the catalogue]{href=/en/components/ variant=link}.
```

`badge`, `button`, `icon` and `kbd` are supported. An unknown name, parameter
or unsafe value is refused before any HTML is written.

## Docara blocks

A block takes its own place in the page document:

```markdown
:::alert {type=warning variant=outlined}
#### Note

Check the parameters before publishing.
:::
```

Docara looks up the name after `:::` in the registry of typed components. The
exact parameters and errors are on the page of each component.

## Containers and composition

A container accepts only the nested blocks and slots its description
declares. It checks `min_children`, `max_children`, order and nesting depth.
The number of colons in `::::` helps you write the nesting but does not
change the block type or skip the check.

```markdown
::::grid {columns=2 gap=2}
:::card
### First step

Check the configuration.
:::
:::card
### Second step

Build the page.
:::
::::
```

For a shared content band use [Surface](/en/components/surface/). Hero,
Showcase and Promo are already styled like Surface and need no extra wrapper.

## SIMAI Framework

Docara accepts only Smart components of the exact pinned release that their
owner admitted and that were checked in several host systems. The component
description, views, presets, slots, templates, files and browser behaviour
(hydration) are checked before any HTML is written. `supported` applies only
to an explicitly admitted component: a similar Framework component without
admission is not supported.

An example of container composition: a child `ui.list-item` fills the
`options` slot of `ui.dropdown`. Such a call builds only on a site whose
`simai-framework.lock.json` admits both components; this site's lock admits
`ui.button` and `ui.alert` now, so the example is only shown here.

```markdown
::::ui.dropdown {label="Plan"}
:::ui.list-item {slot=options type=text value=team label="Team"}
:::
::::
```

## Project components

A project can keep its own Smart components for content, and contributions
to the site shell, in the allowed project folders. Settings choose admitted
identifiers and accept no callback, PHP class or arbitrary template or file
path. A dry run (`dry-run`) records the changes and the hashes of the input
files; the plan is applied only if the plan and the files have not changed
since.

The practical path is [inspect → scaffold → validate → preview → test](/en/development/developer-sdk/).

## One name, one meaning

Parameters with the same meaning have the same name in every component:

| Parameter | What it sets |
| --- | --- |
| `type` | Meaning: info, success, warning, danger |
| `variant` | Style: filled, outlined, borderless |
| `scheme` | A colour from the SIMAI Framework palette |
| `layout` | How the parts of a block are arranged |
| `size` | A size from the shared SIMAI Framework scale |
| `ratio` | Proportions as `width/height`, for example `16/9` |
| `label` | A name for screen readers; it may not show on the page |
| `title` | A visible heading |
| `caption` | A caption |
| `href`, `src` | A link address and a file address |
| `open` | Whether a block starts open |
| `sync` | A group of blocks that switch together |
| `tone` | The background tone |

`caption` and `sync` are reserved for new components.

## Former parameter names

Parameter names follow one vocabulary. The former names and values still
work, and the page looks the same. The build does not stop; it prints one
line with how many former names are left. `docara validate page <address>`
shows each place: the line, the former name and its replacement (code
`MARKDOWN_PARAMETER_DEPRECATED`). The former names go away no earlier than
the next minor version.

| Component | Was | Now |
| --- | --- | --- |
| [Badge](/en/components/badge/), [link button](/en/components/button/) | `type` | `variant` |
| [Details](/en/components/details/), [steps](/en/components/steps/) | `view` | `layout` |
| [Hero](/en/components/hero/) | `variant` | `layout` |
| [Download](/en/components/download/) | `name`, `size` | `title`, `file_size` |
| [Embed](/en/components/embed/) | `title` | `label` |
| [Attributed quote](/en/authoring/markdown/) | `url` | `href` |
| Image, [figure](/en/components/figure/), [media](/en/components/media/), embed | `ratio=16x9` | `ratio=16/9` |
| [Logos](/en/components/logos/) | `tone=normal` | `tone=default` |

Figure, media and embed now accept one set of proportions: `1/1`, `4/3`,
`3/2`, `16/9` and `21/9`.

## Where the build stops

The build stops on an unknown component or parameter; a wrong nested block,
slot, their number, order or depth; an unclosed `:::`; clashing namespaces;
a hash mismatch; leaving the project folder through `..` or a link; and an
attempt to pass an executable or unadmitted path. These checks run before
any template and are not relaxed in preview mode.
