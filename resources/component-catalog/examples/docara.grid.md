<!-- docara-variant:base -->
<!-- docara-variant:state.stacked -->
<!-- docara-variant:state.responsive -->
::::grid {columns=3 gap=2}
:::card
#### Markdown

Readable source content.
:::
:::card
#### JSON

Validated settings.
:::
:::card
#### PHP

Reproducible builds.
:::
::::

## Grid + Card + Icon recipe

::::grid {columns=4 gap=2}
:::card
:icon[edit_note]{size=1 container=square variant=tonal scheme=primary}
#### Readable source

Markdown remains the primary content format.
:::
:::card
:icon[schema]{size=1 container=circle variant=tonal scheme=secondary}
#### Strict schema

Configuration errors are visible before publishing.
:::
:::card
:icon[devices]{size=1 container=square variant=outline scheme=info}
#### Responsive

One interface works on every screen.
:::
:::card
:icon[bolt]{size=1 container=circle variant=main scheme=success}
#### Fast output

Only static files remain on the server.
:::
::::

## Links and actions in cards

::::grid {columns=2 gap=2}
:::card
#### Documentation as code

Write content in Markdown and keep changes beside the project.

:button[Learn more]{href="../button/" type=link icon=arrow_forward}
:::
:::card
#### Quick start

Build the first page and inspect it in a browser.

:button[Get started]{href="../button/" type=outline}
:::
::::

## Plain cards

::::grid {columns=3 gap=2}
:::card {variant=plain}
#### Content

The component owns meaning.
:::
:::card {variant=plain}
#### Composition

Grid owns width and spacing.
:::
:::card {variant=plain}
#### Adaptability

Columns stack on narrow screens.
:::
::::

## Plain cards with figures

:::::grid {columns=3 gap=2}
::::card {variant=plain}
:::figure {ratio=16/9 fit=cover}
![Content](../../_docara/component-catalog/feature-markdown.png)
:::
##### Content stays content

Markdown is easy to read and edit.
::::
::::card {variant=plain}
:::figure {ratio=16/9 fit=contain}
![Composition](../../_docara/component-catalog/feature-json.png)
:::
##### Declarative layout

JSON connects regions and components.
::::
::::card {variant=plain}
:::figure {ratio=16/9 fit=contain}
![Build](../../_docara/component-catalog/feature-build.png)
:::
##### Verifiable output

The build produces a ready static site.
::::
:::::
