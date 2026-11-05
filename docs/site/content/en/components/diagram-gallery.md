---
description: The Mermaid diagram gallery — all 22 diagram types Docara draws, with a live result, the source and examples from real documentation work, from a publishing process to a release plan.
tags: [mermaid, diagrams, charts, flowchart, gantt]
---

# Diagram gallery

This page collects all the Mermaid diagram types that the Mermaid 11.16
bundled with Docara draws. Each type has a short note on when you need it, a
live diagram and its source on the "Markdown" tab — you can copy it into your
own page and change the labels.

How to add a diagram, what parameters it has and which labels can cause
trouble is described on the [Diagram](/en/components/diagram/) page.

## Processes and behaviour

### Flowchart

`flowchart` shows the steps and branches of a process: what follows what and
where each decision leads.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Publishing a documentation page"}
flowchart LR
  draft[Markdown draft] --> build(Site build)
  build --> check{Checks passed?}
  check -->|yes| publish[Publication]
  check -->|no| fix[Fix errors]
  fix --> build
  publish --> reader([The reader sees the page])
:::
```
:::

### Sequence diagram

`sequenceDiagram` shows who sends requests and responses to whom and in what
order — handy for describing an API.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Requesting a list of pages through the API"}
sequenceDiagram
  autonumber
  participant app as Application
  participant api as API
  participant db as Database
  app->>api: GET /v1/pages?section=components
  api->>api: Check the token
  alt token is valid
    api->>db: Select the section's pages
    db-->>api: 30 records
    api-->>app: 200 OK and the list of pages
  else token has expired
    api-->>app: 401 Unauthorized
  end
:::
```
:::

### State diagram

`stateDiagram-v2` shows the states an object can be in and what moves it
from one to another.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Page lifecycle"}
stateDiagram-v2
  state "In review" as review
  state "Outdated" as outdated
  [*] --> Draft
  Draft --> review: author submitted
  review --> Draft: changes requested
  review --> Published: approved
  Published --> outdated: a new version came out
  outdated --> Draft: the page is being updated
  outdated --> [*]: the page was deleted
:::
```
:::

### User journey

`journey` shows a person's steps and how they feel at each step, as a score
from 1 to 5.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="The reader's path to an answer"}
journey
  title The reader's path to an answer
  section Search
    Searches the web: 3: Reader
    Opens the page: 4: Reader
  section Reading
    Finds an example: 5: Reader
    Copies the source: 5: Reader
  section Use
    Pastes it into their site: 4: Reader, Author
    Writes to support: 2: Reader, Support
:::
```
:::

## Structure and data

### Class diagram

`classDiagram` shows code objects, their fields, methods and the relations
between them.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Model of pages and components"}
classDiagram
  class Section["Section"] {
    +string title
    +pages() list~Page~
  }
  class Page["Page"] {
    +string slug
    +string title
    +render() string
  }
  class Component["Component"] {
    <<interface>>
    +render(attributes) string
  }
  class Diagram["Diagram"]
  class Example["Example"]
  Section "1" o-- "*" Page : contains
  Page ..> Component : uses
  Component <|.. Diagram
  Component <|.. Example
:::
```
:::

### Data model

`erDiagram` shows entities, their fields and how many records of one entity
relate to another.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Data model of a documentation site"}
erDiagram
  SITE["Site"] {
    string base_url "site address"
    string locale "default language"
  }
  SECTION["Section"] {
    string title "name in the menu"
  }
  PAGE["Page"] {
    string slug PK "page address"
    string title "heading"
    int order "place in the menu"
  }
  TAG["Tag"]
  EXAMPLE["Example"]
  SITE ||--|{ SECTION : "is divided into"
  SECTION ||--o{ PAGE : "contains"
  PAGE }o--o{ TAG : "is tagged"
  PAGE ||--o{ EXAMPLE : "shows"
:::
```
:::

### Requirements

`requirementDiagram` links requirements with what satisfies and verifies
them.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Build requirements"}
requirementDiagram
  requirement offline_build {
    id: R1
    text: "The build works without a network"
    risk: medium
    verifymethod: test
  }
  performanceRequirement page_weight {
    id: R2
    text: "A page is not heavier than the budget"
    risk: low
    verifymethod: analysis
  }
  element build_command {
    type: "command"
    docref: "docara build"
  }
  element weight_check {
    type: "script"
    docref: "verify-page-weight.php"
  }
  build_command - satisfies -> offline_build
  weight_check - verifies -> page_weight
:::
```
:::

### Mind map

`mindmap` spreads a topic into branches from the centre — suits the
structure of a section or a documentation plan.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Documentation structure"}
mindmap
  root((Documentation))
    Get started
      Installation
      First site
    Authoring
      Markdown
      Components
      Diagrams
    Settings
      docara.json
      Navigation
    Build
      Checks
      Publishing
:::
```
:::

### Treemap

`treemap-beta` shows the shares of nested parts as rectangles: the bigger
the part, the bigger its area.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Documentation sections by number of pages"}
treemap-beta
"Documentation"
    "Components": 31
    "Reference": 15
    "Guides"
        "Get started": 6
        "Authoring": 8
        "Build": 8
        "Settings": 8
        "Development": 8
    "Examples": 17
    "Demonstration results": 14
:::
```
:::

### Blocks

`block-beta` lays blocks out on a grid when their relative position matters,
not the flow.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Documentation page layout"}
block-beta
  columns 3
  header["Header and search"]:3
  nav["Section menu"] article["Article"] toc["Outline"]
  footer["Footer"]:3
:::
```
:::

### Packet

`packet-beta` shows which fields and bits a packet or a binary record
consists of.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="TCP packet header"}
packet-beta
  title TCP header
  0-15: "Source port"
  16-31: "Destination port"
  32-63: "Sequence number"
  64-95: "Acknowledgment number"
  96-99: "Length"
  100-103: "Reserved"
  104-111: "Flags"
  112-127: "Window"
  128-143: "Checksum"
  144-159: "Urgent pointer"
:::
```
:::

## Architecture

### System context

`C4Context` shows a system, its users and external systems — the first
level of the C4 model.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Documentation system context"}
C4Context
  title Documentation system context
  Person(author, "Author", "Writes pages in Markdown")
  Person(reader, "Reader", "Looks for an answer on the site")
  System(docara, "Docara", "Builds a static site")
  System_Ext(git, "GitHub", "Stores the sources")
  System_Ext(hosting, "Hosting", "Serves the finished pages")
  Rel(author, git, "Pushes changes")
  Rel(git, docara, "Starts the build")
  Rel(docara, hosting, "Publishes HTML")
  Rel(reader, hosting, "Opens pages")
  UpdateLayoutConfig($c4ShapeInRow="3", $c4BoundaryInRow="1")
:::
```
:::

### Service architecture

`architecture-beta` draws services and groups with cloud, server, disk and
database icons. Put labels in non-Latin scripts in double quotes.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Documentation site architecture"}
architecture-beta
  group site(cloud)["Documentation site"]
  service cdn(internet)["CDN"] in site
  service web(server)["Web server"] in site
  service search(disk)["Search index"] in site
  service builds(database)["Build storage"] in site
  cdn:R --> L:web
  web:B --> T:search
  web:R --> L:builds
:::
```
:::

## Plans and history

### Gantt chart

`gantt` shows tasks on a calendar: deadlines, dependencies and milestones.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Release plan"}
gantt
  title Release plan
  dateFormat YYYY-MM-DD
  axisFormat %d.%m
  section Preparation
    Page audit       :done, audit, 2026-10-19, 3d
    Diagram gallery  :active, gallery, after audit, 4d
  section Checks
    Tests and build  :tests, after gallery, 2d
    Acceptance       :crit, accept, after tests, 1d
  section Release
    Publication      :milestone, release, after accept, 0d
:::
```
:::

### Timeline

`timeline` lists events in order — handy for a release history.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Docara 3 releases"}
timeline
  title Docara 3 releases
  section 14–18 October
    3.0 : Framework included
    3.2 : Lighter sites
  section 19–21 October
    3.4 : Portal build
    3.5 : Background effects
    3.6 : Page weight check
  section 21–23 October
    3.7 : 404 page
    3.8 : Error code reference
:::
```
:::

### Git branches

`gitGraph` shows branches, commits and merges — for example, the order of
work on a change.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Working on a change in Git"}
gitGraph
  commit id: "3.8.0" tag: "v3.8.0"
  branch feature/gallery
  checkout feature/gallery
  commit id: "Diagram gallery"
  commit id: "Mermaid check"
  checkout main
  commit id: "Search fix"
  merge feature/gallery id: "Branch merge"
  commit id: "3.9.0" tag: "v3.9.0"
:::
```
:::

### Kanban

`kanban` lays tasks out in status columns.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Documentation task board"}
kanban
  todo[To do]
    t1[Descriptions of settings pages]
    t2[English home page]
  doing[In progress]
    t3[Diagram gallery]@{ assigned: 'Author', priority: 'High' }
  review[In review]
    t4[New logo]
  done[Done]
    t5[Page weight check]
:::
```
:::

## Charts and comparison

### Pie chart

`pie` shows the shares of a whole — when there are few parts and the
proportion matters.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Documentation pages by type"}
pie showData
  title Pages by type
  "Guide" : 38
  "Component" : 31
  "Example" : 31
  "Reference" : 15
:::
```
:::

### Chart

`xychart-beta` draws bars and lines on axes — for example, the weight of
this site's pages next to the overall budget of 4.5 MB.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Page weight and the overall budget"}
xychart-beta
  title "Page weight, MB"
  x-axis ["Site median", "Icons", "Diagram", "Gallery"]
  y-axis "Megabytes" 0 --> 8
  bar [2.75, 4.51, 6.35, 6.46]
  line [4.5, 4.5, 4.5, 4.5]
:::
```
:::

### Quadrant chart

`quadrantChart` sorts items into four groups on two axes — handy for
priorities.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Documentation task priorities"}
quadrantChart
  title Task priorities
  x-axis Low effort --> High effort
  y-axis Low value --> High value
  quadrant-1 Plan
  quadrant-2 Do now
  quadrant-3 In passing
  quadrant-4 Do not do
  Page descriptions: [0.2, 0.8]
  Diagram gallery: [0.45, 0.7]
  English version: [0.85, 0.9]
  New icons: [0.3, 0.25]
  Own diagram engine: [0.9, 0.15]
:::
```
:::

### Flow

`sankey-beta` shows how a volume flows between stages: the width of a band
equals the amount. Write labels in Latin script — with non-Latin labels
(checked with Cyrillic) the diagram is not drawn.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Flow of files through the build"}
sankey-beta
Markdown pages,Build,118
Page settings,Build,118
Images,Build,40
Build,HTML pages,118
Build,Search index,118
Build,Assets,40
:::
```
:::

### Radar

`radar-beta` compares several options on a shared set of scores.

:::example {label="Diagram"}
```markdown
:::diagram {engine=mermaid title="Quality of documentation sections"}
radar-beta
  title Section quality
  axis complete["Completeness"], accuracy["Accuracy"], examples["Examples"], navigation["Navigation"], speed["Speed"]
  curve components["Components"]{5, 4, 5, 4, 3}
  curve settings["Settings"]{3, 5, 2, 4, 5}
  max 5
  min 0
:::
```
:::

## See also

- [Diagram](/en/components/diagram/)
- [Interactive example](/en/components/example/)
- [Math formula](/en/components/math/)
