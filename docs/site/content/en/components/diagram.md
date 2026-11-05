---
description: A Mermaid diagram lives next to the article text and is drawn in the browser by the bundled Mermaid. Any Mermaid diagram type works, from a flowchart to charts.
---

# Diagram

A diagram keeps a Mermaid chart next to the article text, and the browser
draws it as an image. The diagram changes together with the documentation:
its source lives in the same page and is reviewed like ordinary text.

## Example

:::example {label="Flowchart"}
```markdown
:::diagram {title="Publishing a documentation page" label="A Markdown draft goes to the site build; if the checks pass, the page is published, otherwise the errors are fixed and the site is built again"}
flowchart LR
  draft[Markdown draft] --> build(Site build)
  build --> check{Checks passed?}
  check -->|yes| publish[Publication]
  check -->|no| fix[Fix errors]
  fix --> build
:::
```
:::

## Markup

```markdown
:::diagram {title="A clear diagram name"}
flowchart LR
  A[First step] --> B[Second step]
:::
```

- Inside is a diagram in Mermaid notation. The first line names its type:
  `flowchart`, `sequenceDiagram`, `gantt` and so on.
- Keep the labels short: long text makes the diagram wide.
- Docara does not restrict the diagram type: everything the bundled
  Mermaid 11.16 can do works.

## Variants

### Diagram type

We checked 22 types in the browser — they are all collected with live
examples in the [diagram gallery](../diagram-gallery/).

| Task | Mermaid types |
| --- | --- |
| Process and behaviour | `flowchart` — steps and branches, `sequenceDiagram` — an exchange of requests, `stateDiagram-v2` — the states of an object, `journey` — a user journey |
| Structure and data | `classDiagram` — code classes, `erDiagram` — a data model, `requirementDiagram` — requirements, `mindmap` — a mind map, `treemap-beta` — the shares of nested parts, `block-beta` — blocks on a grid, `packet-beta` — packet fields |
| Architecture | `C4Context` — the system context, `architecture-beta` — services and groups |
| Plans and history | `gantt` — tasks on a calendar, `timeline` — a chronology, `gitGraph` — Git branches, `kanban` — a task board |
| Charts and comparison | `pie` — shares of a whole, `xychart-beta` — bars and lines, `quadrantChart` — four groups on two axes, `sankey-beta` — flows, `radar-beta` — a comparison by scores |

Mermaid considers the types ending in `-beta` experimental: their notation
may change slightly in later Mermaid versions.

:::example {label="Sequence diagram"}
```markdown
:::diagram {title="An API request"}
sequenceDiagram
  participant app as Application
  participant api as API
  participant db as Database
  app->>api: GET /v1/pages
  api->>db: Select pages
  db-->>api: List of records
  api-->>app: 200 OK
:::
```
:::

:::example {label="State diagram"}
```markdown
:::diagram {title="Page lifecycle"}
stateDiagram-v2
  state "In review" as review
  [*] --> Draft
  Draft --> review: author submitted
  review --> Draft: changes requested
  review --> Published: approved
  Published --> [*]
:::
```
:::

### Labels in non-Latin scripts (checked with Cyrillic)

- **`sankey-beta` does not render with non-Latin labels** — with or without
  quotes. Write flow labels in Latin script.
- **`architecture-beta` and `xychart-beta` accept non-Latin labels only in
  double quotes:** `service web(server)["Веб-сервер"]`,
  `x-axis ["Главная", "Значки"]`.
- **In `erDiagram` a display name, including a non-Latin one, is set with an
  alias** in the entity block, and relationships use the Latin name:

:::example {label="Data model"}
```markdown
:::diagram {title="Site data model"}
erDiagram
  SECTION["Section"] {
    string title "name in the menu"
  }
  PAGE["Page"] {
    string slug PK "page address"
    string title "heading"
  }
  SECTION ||--o{ PAGE : "contains"
:::
```
:::

## States

### How the diagram is shown

The build puts the diagram source into the page as text and loads Mermaid
from the Docara bundle, without external CDNs. Pages without diagrams do not
load this file, and it gets into the build only if at least one page has a
diagram. Mermaid weighs about 3.6 MB and loads once per page, however many
diagrams it has.

### Without JavaScript and with an error

If scripts are turned off, the reader sees the diagram source. If Mermaid
cannot parse the diagram, the source stays in place, and the block gets the
`data-docara-render-error="diagram"` attribute.

### Limitations

- **Links and click handlers in diagrams do not work.** Mermaid runs in
  strict security mode, so `click` and `href` inside a diagram are ignored.
  Put the link in the text next to the diagram.
- **ZenUML is not in the bundle**, `zenuml` diagrams are not drawn.

## Accessibility

- A diagram is an image. A reader with a screen reader hears `label`, and
  without it the name from `title`. The `label` parameter is a text
  alternative: say briefly what the diagram shows, not how it is built.

```markdown
:::diagram {title="Build stages" label="The source becomes a recipe, the recipe a composition document, the document HTML"}
graph LR
  Source --> Recipe --> Document --> HTML
:::
```

- Without `title` the name is generic: "Diagram" on an English page,
  "Схема" on a Russian page. An empty `label` stops the build with
  `MARKDOWN_DIAGRAM_LABEL_EMPTY`.
- Retell a complex diagram in the text next to it: the sequence of steps,
  the main decision. An image does not replace an explanation.
- Do not convey meaning by colour alone: label the nodes and arrows.

## Parameters

<!-- docara:component-parameters:start -->

| Parameter | Values | Default | Sets |
| --- | --- | --- | --- |
| `engine` | `mermaid` | `mermaid` | What draws the diagram. Mermaid is supported now. |
| `title` | string | `Diagram` | The diagram name: the caption above it and its name for screen readers. |
| `label` | string | — | A text alternative for screen readers: what the diagram shows. Without the parameter the name from `title` is read. |

<!-- docara:component-parameters:end -->

## See also

- [Diagram gallery](../diagram-gallery/)
- [Interactive example](../example/)
- [Math formula](../math/)
- [Figure](../figure/)
