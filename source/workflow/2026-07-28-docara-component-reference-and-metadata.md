# Docara component reference and automatic metadata

Date: 2026-07-28
Status: completed
Track: docara-consolidation
Goal mode: enabled

## Final Outcome

Docara has one public authoring language and one generated component reference.
Every supported native Markdown capability, Docara component and admitted
SIMAI Framework component has its own localized page. A page explains purpose,
syntax, parameters, constraints, all declared variants and states, and renders
copy-ready live examples. Package, version, owner, capabilities, source and
history are derived from immutable inputs instead of duplicated prose.

## Current Goal

Implement the accepted component-system contract from
`docs/authoring-syntax-contract.md` and the reviewed prototype, then rebuild the
local documentation site.

## Done When

- one fail-closed registry describes every callable public component;
- every supported registry entry produces exactly one detail route and menu item;
- every declared variant is covered by a named executable example;
- detail pages show purpose, kind, syntax, parameters, defaults, constraints,
  variants, states, examples and source-backed metadata;
- portable builds do not require a Git checkout; optional Git enrichment is
  captured as deterministic package data before publication;
- public documentation no longer advertises obsolete `cta`, `features` or raw
  `ui.*` authoring where the accepted contract replaced them;
- the local `docara.test` site is rebuilt and reviewed in light/dark themes on
  desktop and mobile;
- PHPUnit, deterministic builds, static verification, broken-link checks and
  `git diff --check` pass;
- no merge, tag, release, production deployment or production-readiness claim
  is made by this workflow.

## Baseline

- The existing projector already creates one route per `supported` catalog
  entry and places those routes in the left menu.
- The current effective catalog contains native Markdown, nine legacy typed
  components, two admitted Framework Smart components and non-callable
  requirement records.
- The accepted target language is recorded but explicitly marked
  `implementation pending`.
- The visual prototype is
  `source/workflow/prototypes/docara-component-system-preview.html`.
- The worktree contains ongoing Docara consolidation changes. They are treated
  as owner work and must not be discarded or rewritten wholesale.

## Architecture Decisions

1. Extend `EffectiveComponentCatalogBuilder` and
   `PortableComponentCatalogProjector`; do not create a second catalog.
2. Variant coverage is explicit data. A component declares named examples and
   the projector renders every example; one ad-hoc fixture cannot stand for all
   variants.
3. Derived metadata has precedence over prose:
   - Framework package/revision/manifest from the immutable Framework lock;
   - component owner and source from typed definitions or admitted manifests;
   - capabilities from the component contract;
   - Git author/date/history from a deterministic source-metadata snapshot,
     with a portable fallback when `.git` is absent.
4. Localized labels live in language packs; technical facts stay
   language-independent.
5. Native Markdown remains native. New directives are added only when semantic
   structure or reusable behavior requires them.
6. Public authoring exposes `Markdown` and `Docara`; Framework implementation
   identifiers remain provenance, not author syntax.

## Batches

### M1 — Baseline and schema

Status: completed

- inventory current entries, examples, generated routes and tests;
- define component kind, example matrix and derived-metadata schema;
- add fail-closed validation and fixtures;
- preserve deterministic portable builds.

### M2 — Reference projector

Status: completed

- render all declared examples and variants;
- add metadata summary and expandable provenance/history;
- keep one page and one menu item per supported component;
- add machine-readable receipt coverage.

### M3 — Accepted authoring language

Status: completed

- implement inline syntax and controlled block/container parameters;
- replace legacy `cta` with inline `button`;
- replace `features` with `grid + card + icon` recipe;
- implement the accepted documentation primitives and safe HTML/embed policy;
- add migration diagnostics for obsolete forms.

### M4 — Full catalog content

Status: completed

- populate every supported component with complete parameters, variants and
  examples;
- add separate localized pages through the common generator;
- update overview and authoring documentation.

### M5 — Build and acceptance

Status: completed

- build twice and compare output;
- run PHPUnit and static verification;
- rebuild `https://docara.test/`;
- review light/dark and desktop/mobile;
- record evidence and remaining nonclaims.

## Allowed Surfaces

- `src/ComponentCatalog/**`
- `src/PortableSite/**`
- component parser/renderer code required by the accepted contract
- `resources/component-catalog/**`
- `resources/schemas/**`
- `resources/language-packs/**`
- Docara documentation, fixtures and tests
- generated local `output/` and the local `docara.test` target
- this workflow and its evidence directory

## Forbidden Actions

- discarding unrelated dirty-worktree changes;
- editing generated Framework distributions as source;
- inventing unsupported Framework capabilities in Docara;
- merge to default branches, tags, releases or production deployment;
- production/readiness claims outside verified bounded evidence.

## Checks

- targeted unit tests after every contract change;
- complete PHPUnit suite before acceptance;
- two byte-identical production builds;
- `verify-static` with zero broken links;
- exact page/menu/example coverage assertions;
- browser review at 1440/1920 and 390 px in both themes;
- `git diff --check` on the bounded change set.

## Evidence

Use `source/workflow/evidence/2026-07-28-docara-component-reference-and-metadata/`.

## Current Next Step

The bounded goal is complete. The generated catalog contains 33 records:
28 supported component pages and 5 explicit gap pages. The production build
contains 107 canonical pages and 232 HTML documents. Static verification
checked 20,646 local references with zero broken references. Two clean
production builds produced 297 byte-identical files. PHPUnit passed with
333 tests and 6,531 assertions.

The verified build is served locally at
`https://docara.test/ru/components/catalog/`. Browser acceptance covered the
catalog and a representative multi-variant component page in light and dark
themes, desktop and 390 px mobile layouts. No horizontal page overflow remains.
The exact local deployment backup is
`/Users/rim/Sites/docara.test/.docara-backups/component-reference-20260728-175407`.

No merge, tag, package publication, production deployment or readiness claim
was made.
