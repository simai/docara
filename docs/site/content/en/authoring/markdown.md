---
description: How to write a Docara page in Markdown — metadata at the top of the page, headings and emphasis, lists and quotes, links and images, tables, code and footnotes.
tags: [markdown, markup, front-matter, table, links, footnotes]
---

# Markdown

A Docara page is an ordinary Markdown file. Start it with one H1 heading, then
use H2–H6, paragraphs, lists, links, images, quotes, code, tables and
footnotes. You need a component only where Markdown is not enough.

```markdown
# Installation

Briefly explain what the page gives the reader.

## Steps

1. Install the package.
2. Initialize the site.

| Command | Result |
| --- | --- |
| `docara build production` | A static production folder |
```

## Page metadata

Before the H1 you can add a metadata block (front matter) between `---`
lines:

```markdown
---
title: Installation
description: Prepare your first Docara site.
tags: [start, install]
draft: false
translation_key: guide.install
---

# Installation
```

The block accepts only these keys; any other one stops the build with
[`FRONT_MATTER_KEY_UNKNOWN`](/en/reference/errors/content/#page-metadata-front-matter):

- `title` and `description` — the page title and description;
- `tags` — a one-line list, for example `tags: [deploy, publishing]`;
  [search](/en/settings/search/#synonyms-page-tags) finds the page by its
  tags;
- `draft` — `true` or `false`; a draft is not published and cannot be built
  as a public `--page`;
- `noindex` — `true` or `false`; `true` publishes the page but adds
  `<meta name="robots" content="noindex">` and removes it from `sitemap.xml`
  and `llms.txt` (see [Site address](/en/settings/site-address/)). Site
  search is set separately: `"search": {"indexed": false}` in
  `<route>.page.json`;
- `translation_key` — a stable key that links the translations of one page;
- `profile` — a built-in [check profile](/en/authoring/page-profiles/):
  `landing`, `article`, `tutorial`, `how_to`, `reference` or `explanation`;
- `template` — a [page template](/en/authoring/page-templates/) from
  `docara.authoring.json`: which sections, in which order;
- `template_omit` — template sections this page leaves out on purpose, as a
  one-line list such as `template_omit: [classes]`;
- `composition_recipe` — the path to a Composition Recipe JSON descriptor
  relative to the project; see the example page
  [Composition Recipe: expanded header](/en/demonstrator-recipes/expanded/).

Layout, HTML and arbitrary fields in the metadata are not allowed. An error
in the block names the source file, line and column. Other page settings go
into the neighbouring `<page>.page.json` file — see
[Site, section and page](/en/settings/levels-and-inheritance/).

## Headings and text

Headings divide an article into clear sections, and ordinary Markdown
emphasizes important words without hand-written HTML.

:::example {label="Headings and emphasis"}
```markdown
## Project setup

First set the **required parameters**, then add *helpful
explanations*. An outdated recommendation can be marked as
~~no longer valid~~ for a while, and the line the reader looks for as
==highlighted==.
```
:::

| Syntax | Purpose |
| --- | --- |
| `#` | The page title; used once |
| `##` | A main section |
| `###` | A subsection inside a main section |
| `####`–`######` | Rare extra detail |

Do not skip levels for the sake of font size: the theme sets the look, and
the level describes the structure of the document.

```markdown
**important text**
*an accent in meaning*
~~outdated wording~~
==what the reader looks for==
`parameter_name`
```

Bold suits the key result, italics a short accent, strikethrough a clearly
outdated wording, and inline code commands, files and values. Two equals
signs highlight a word as a marker pen would: that is how you mark what the
reader looks for, a match inside an example, say. Do not use arbitrary HTML
instead of Markdown.

Separate the short parts of a line, such as a caption under a section
heading, with a bullet `•` with a space on each side. Such a bullet becomes a
separator: a little smaller and lighter than the text, centred on the line,
and skipped by screen readers.

:::example {label="A separator in a line"}
```markdown
`appearance` • default `default` • [reference](#headings-and-text)
```
:::

A bullet without spaces around it and a bullet inside code stay plain.

Headings get deterministic anchors you can link to; how an anchor is built is
described in [Reading context](/en/settings/reading/#how-anchors-are-built).
Renaming a heading changes the fragment address, so run
`verify-static` again after the edit.

## Lists and quotes

Lists show a set or a sequence of actions, and a quote separates exact
outside wording from the author's text.

:::example {label="A list and a quote"}
```markdown
1. Check the configuration.
2. Build the documentation.
3. Open the finished page.

> Good documentation helps you get the task done.
```
:::

Use a bulleted list when the order does not matter:

- a short purpose;
- the allowed parameters;
- useful limits.

A numbered list suits a sequence:

1. Prepare the Markdown.
2. Run the build.
3. Check the links and the look.

Nested items separate details but should not turn the page into a complex
tree.

A quote can name its source:

> First define the reader's task, then explain how the system works.
{author="Author of the piece" source="Source title" href="https://example.com/source"}

```markdown
> First define the reader's task, then explain how the system works.
{author="Author of the piece" source="Source title" href="https://example.com/source"}
```

`author` names the author, `source` the publication, and `href` adds a safe
link. Do not fill unknown details with placeholders: a plain quote without
attribution is more useful than a made-up source.

## Checklists and definitions

A task list shows what is already done, and a definition list explains the
terms of a page without turning them into an ordinary paragraph.

:::example {label="Checklist and definitions"}
```markdown
- [x] Describe the reader's task
- [ ] Check the example in a browser

Recipe
: The build plan of one page, which Docara writes itself.

Composition Document
: The finished page with its regions, sections and blocks.
```
:::

An item with `[x]` is checked, one with `[ ]` is not. The boxes on the built
page do not click: the list shows a state, it does not collect the reader's
answer. Such a list suits a release instruction or a work plan; an item is
checked by editing the page.

A definition is written like this: the term on its own line, and the next
line starting with a colon and a space. Any number of terms may follow one
another, and one term may have several definitions.

## Links and images

Links lead the reader to the next useful step, and images support the text
with a clear visual explanation and an accessible description.

:::example {label="A link and an image"}
```markdown
[Open the component catalogue](/en/components/)

![Docara mark](../../assets/docara-mark.svg)
```
:::

Use a clear label that shows where the link goes:

- [read the quick start](/en/start/quick-start/);
- [open the backlinks page](/en/components/backlinks/);
- [see the Docara repository](https://github.com/simai/docara).

Do not replace the meaning of the label with "click here". Use paths from
the site root for site pages and relative paths to project files for images.
The generator adds `base_url` itself where it applies. Unsafe protocols are
refused.

![Docara mark in a 16:9 frame](../../assets/docara-icon.svg){ratio=16/9 fit=contain}

| Parameter | Values | Purpose |
| --- | --- | --- |
| `ratio` | `auto`, `1/1`, `4/3`, `3/2`, `16/9`, `21/9` | The shape of the image area |
| `fit` | `cover`, `contain` | Crop to fill or show the whole image |

```markdown
![A page with an illustration](../../assets/markdown-scene.webp){ratio=16/9 fit=contain}
```

Alternative text must convey the purpose of the image. Do not use a
decorative picture instead of a heading or an instruction. An image with a
caption and set proportions comes from the [Figure](/en/components/figure/)
component.

## Tables

A table compares several objects by the same features compactly and scrolls
inside its own area on a narrow screen.

:::example {label="A table"}
```markdown
| File | Role |
| --- | --- |
| `docara.json` | Site settings |
| `section.json` | Section settings |
| `.page.json` | Settings of one page |
```
:::

A table is useful for parameters, compatibility and short comparisons. If
cells fill with long paragraphs or nested lists, ordinary sections will be
clearer.

| Feature | Full build | Single-page build | Result |
| --- | --- | --- | --- |
| Markdown | All selected pages | One selected route | HTML |
| Link check | The whole site | The saved full context | 0 broken |
| Navigation | The shared order | The same order | The same links |

The first row sets the column headings, the second the separators. This is an
ordinary Markdown table, not a contract for a complex interactive data view.

## Code

A code block in triple backticks stays a readable static `<pre><code>`, and
the pinned SIMAI Framework adds a header with the language, an accessible
copy button, syntax highlighting and scrolling for long lines. Only the code
is copied, without the interface. If the language dictionary is not
available, the code stays safe readable text. After the language, in curly
braces, you can give a file name, line numbers and highlighting, focus or a
terminal frame:
`` ```php {title="app.php" highlight="2-3" numbers} ``. All options and
including a fragment from a project file are described on
[Code](/en/components/code/) and [Code from a file](/en/components/code-from-file/).

Inside an ordinary code block directive lines stay text and are shown safely
to the reader — this is how you show a component call:

````markdown
```markdown
:::ui.<component>
{"<prop>":"<value>"}
:::
```
````

## Footnotes and sources

A footnote adds a checkable explanation or source without breaking the main
text. The reader jumps to the note and can return to where the link was.

:::example {label="A footnote"}
```markdown
Docara builds documentation from Markdown.[^source]

[^source]: The project guide, the current version and the publication address.
```
:::

The label between `[^` and `]` should be short and stable: `source`, `api` or
a number. One label links one reference in the text with one definition
below.

| Part | Purpose |
| --- | --- |
| `[^source]` | The reference in the main text |
| `[^source]: ...` | The note text or the source description |
| Publication address | Lets the reader check the statement |

A footnote does not replace a clear main text. Give the author, title,
version or date and an available address if a statement relies on an outside
source.

## When you need a component

Raw HTML is removed. If ordinary Markdown is not enough:

1. open the [component catalogue](/en/components/);
2. pick an entry with the `supported` status;
3. copy the exact call from the component's page;
4. rebuild and check the site.

For a styled band use [Surface](/en/components/surface/), and for a first
screen [Hero](/en/components/hero/) (both in Russian). Hero, Showcase and
Promo already use the shared Surface styling: do not nest them in a Surface
and do not add CSS, a template path or a separate background.

Do not copy component parameters from an old page or another project: the
exact contract belongs to the component catalogue of the current build, and
the explanation to the component's page. The overall model is described in
[How components work](/en/start/component-model/).

## Checking

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

`verify-static` finds a link to a missing page or anchor, a repeated HTML
`id` and a wrongly encoded fragment.

## See also

- [Menu and sections](/en/authoring/navigation/) — how a page gets into the menu
- [Component catalogue](/en/components/)
- [Page error codes](/en/reference/errors/content/)
