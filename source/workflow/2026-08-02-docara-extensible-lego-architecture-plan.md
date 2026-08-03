# Docara Extensible LEGO Architecture — planning simulation and execution roadmap

Date: 2026-08-02
Status: `goal3_ready_for_independent_audit`
Project mode: `productization`
Current stage: `docara.stage.g3.developer_sdk`
Current batch: `docara.batch.g3.developer_sdk`
Current next action: `independent_goal3_reverse_outcome_audit`
Next roadmap goal: `docara.goal.3.independent_audit` (`ready_for_independent_audit`, authorized=`true`)
Accepted by: explicit user Goal 1 instruction on 2026-08-02
Repository: `/Users/rim/Documents/GitHub/docara-unified`
Planning branch: `codex/docara-unified-architecture`
Planning HEAD before this artifact: `313afa1`
Historical pre-LEGO product baseline: `be0ba2db5254e468c7c014016ade02e8b4f3f16c`
Historical validation surface: `https://docara-new.test` (not a current action)

This document is the accepted roadmap and executor contract. Goal 1 and Goal 2
are independently accepted; Goal 3 implementation is complete and its
independent reverse-outcome audit is the only current action. This does not
authorize Goal 3 self-acceptance and does not replace the current release
dossier, and is not a release/deploy approval. The existing
Docara skill is disabled as stale; the repository, current specification, graph,
code, tests, the tracked SF5 Smart contract, and exact release evidence are the
sources used for this planning iteration.

## 1. Purpose

Turn Docara from a deterministic but package-maintainer-oriented builder into a
simple extensible LEGO system in which:

1. content remains one Markdown source per public page;
2. author-facing design selection remains JSON configuration;
3. layouts, regions, sections and blocks are reusable declarative artifacts;
4. Framework, Docara and project-local Smart components use one portable Smart
   artifact contract and one gateway;
5. a developer can add a local component or layout without editing Docara's
   central renderer, resolver or allowlists;
6. a component, region, layout or page can be previewed through the production
   rendering path without rebuilding the whole site;
7. CLI and optional MCP surfaces expose stable, machine-readable operations for
   Codex and other agents;
8. no second parser, renderer, gateway or PageBuilder is introduced.

## 2. Done When for the complete roadmap

The roadmap is complete only when all of the following are proven on one exact
candidate revision:

- a project-local portable Smart artifact can be added under `smart/<id>/`
  and used by Markdown, a region or another Smart component without modifying
  `src/`;
- the same portable Smart fixture can be rendered by Docara and the tracked SF5
  `Smart::render()` artifact-root runtime, with an explicit compatibility report;
- `ui.*`, `docara.*` and an allowed project namespace all resolve through one
  `SmartComponentGateway`, one normalized invocation and one render artifact;
- central component-name switches and component-specific allowlists are removed
  from the runtime path after parity evidence;
- project-local layouts, view trees, sections and blocks are discovered through
  deterministic registries and can be selected through existing config
  inheritance without changing engine code;
- component, region and layout previews use the same registry, Smart gateway,
  renderer, asset collector and layout composer as a production page;
- a preview-to-production parity test proves that the inner rendered artifact is
  identical for the same invocation and context;
- `doctor`, `inspect`, `list`, `validate`, `new`, `preview` and `test` operations
  have stable `--json` output and diagnostic codes;
- an optional local MCP adapter delegates to the same application services and
  cannot edit generated, lock, engine-owned or external-repository files;
- normal consumer build remains PHP-only and Node-free;
- full and single-page builds retain one `PageBuilder` and pass deterministic,
  static, security and browser regression gates;
- the specification, graph mappings, public documentation, starter and examples
  describe the implemented result without readiness overclaims.

## 3. Explicit non-goals

- No rewrite of Markdown parsing or Document IR.
- No second public build pipeline or preview-only renderer.
- No Laravel runtime dependency in standalone Docara.
- No database, CMS editing UI or visual drag-and-drop editor in this roadmap.
- No arbitrary PHP/template/callback path in Markdown, `docara.json`,
  `section.json` or `.page.json`.
- No implicit override of `ui.*` or `docara.*` by a project component.
- No manual editing of generated Framework projections, `build_*`, `_docara`,
  `.docara` or `simai-framework.lock.json`.
- No mutation of `bx-simai.main`, `ui`, `ui-smart`, `ui-control` or Larena in
  this repository workflow. Cross-repository changes require their own accepted
  owner workflow.
- No merge, tag, public release or cutover of `docara.test` as an implicit side
  effect of architecture work.

## 4. Source inventory

| Source | Current fact | Why it matters |
| --- | --- | --- |
| `docs/specification/architecture/UNIFIED-ARCHITECTURE.md` | one IR, renderer registry, gateway and PageBuilder are accepted | new work must extend, not replace, this pipeline |
| `src/Declarative/Smart/SmartComponentGateway.php` | one facade exists, but dispatch is namespace-specific | preserve entry point, replace hard-coded provider choice |
| `src/Declarative/Rendering/SmartRenderer.php` | component ID `match` selects six ViewModel methods | a new Smart still requires central code |
| `src/Declarative/Rendering/ViewModelFactory.php` | one method per supported component | runtime is not artifact-only |
| `src/Declarative/Smart/CompositeSmartPlanResolver.php` | owner, renderer and semantic props are Docara-specific | manifests are not yet neutral SF5 artifacts |
| `src/Smart/DocaraSmartContribution.php` | components, views, templates and assets are listed in PHP | adding a folder is insufficient |
| `src/Smart/FrameworkSmartContribution.php` | only Alert and Button are admitted | Framework portability is a bounded subset |
| `src/Declarative/Definition/DefinitionRepository.php` | layouts/sections/blocks/views are a constant map | local design definitions are impossible without engine edits |
| `src/Declarative/Composition/RegionCompositionResolver.php` | one layout, five regions, section matrix and two Smart IDs are hard-coded | region composition is configurable only inside a fixed envelope |
| `resources/views/layout.docara.docs.json` | safe HTML-like JSON View Tree already works | retain and generalize this model |
| `docs/site/content/ru/development/composition-extensions.md` | documents 8–10 maintainer steps per extension | public docs correctly expose current complexity |
| current CLI | `init`, `update`, `build`, `serve`, `verify-static` | no isolated preview, inspect, scaffold or component test surface |
| tracked SF5 Smart contract | `schemaVersion=1.0`, `kind=smart`, artifact roots, manifest/view/preset/template | recommended compatibility target |
| current Docara manifest schema | `larena.ui.smart_manifest.v1`, Docara-only key/owner/renderer constraints | not directly interchangeable with current SF5 artifacts |

## 5. Facts, assumptions and decisions requiring acceptance

### 5.1 Verified facts

1. The current rc.3 site is independently reproducible and deployed only to
   `docara-new.test`; this roadmap is not required to keep that validation site
   working.
2. Full and single-page builds already use the same PageBuilder.
3. Markdown already becomes typed in-memory Document IR.
4. One Smart gateway facade exists.
5. JSON View Trees, layouts, regions, sections and blocks already exist.
6. Current product Smart components are not drop-in SF5 artifacts despite
   conceptual similarity.
7. Current specification overstates one extension property: the acceptance
   statement “new component does not require a central parser or renderer
   branch” is true for the Markdown parser but false for a new rendered Smart.

### 5.2 Recommended architectural decisions

These decisions were accepted for Goal 1 by the user's explicit implementation
instruction on 2026-08-02. Decisions belonging only to Goal 2 or Goal 3 remain
roadmap decisions and do not authorize their implementation.

| ID | Decision | Rationale |
| --- | --- | --- |
| LEGO-D01 | Keep PHP 8.2+ as the engine language and Node-free normal consumer build | preserves current portable product contract |
| LEGO-D02 | Adopt the tracked SF5 Smart artifact contract `schemaVersion: 1.0` as the portable Smart core | makes Framework compatibility real instead of name-level |
| LEGO-D03 | Keep `SmartComponentGateway` as the only invocation facade | prevents a second runtime |
| LEGO-D04 | Resolve Smart through deterministic artifact providers, not namespace `if` branches | enables Framework, package and project roots uniformly |
| LEGO-D05 | Treat project-local Smart templates as trusted developer source, never author content | allows extensibility without exposing executable paths to Markdown/config |
| LEGO-D06 | Components receive prepared props/context and do not fetch Markdown, filesystem, DB or global config | maximizes portability between Docara, Framework and Larena |
| LEGO-D07 | Allow host-specific input adapters only by registered adapter ID; no class/path from authored JSON | permits navigation/ToC preparation without arbitrary execution |
| LEGO-D08 | Add deterministic project design roots for layouts/views/sections/blocks | turns existing JSON composition into a project extension API |
| LEGO-D09 | Forbid implicit replacement of package IDs; customization uses a new ID, view, preset or explicit version-bound `extends` | protects updates and provenance |
| LEGO-D10 | Preview uses the production services and changes only the outer PreviewShell/output target | makes preview evidence trustworthy |
| LEGO-D11 | CLI application services are the source for both human CLI and optional MCP | avoids two automation semantics |
| LEGO-D12 | Existing authoring syntax/config remains backward compatible during migration | component SDK must not force content migration |

### 5.3 Non-blocking assumptions

- Project component namespace is declared once in `docara.json`, for example
  `project.namespace: "acme"`; local artifacts may own only that namespace.
- Built-in `docara.*` remains package-owned; `ui.*` remains Framework-owned.
- A project may add local layouts/sections/blocks, but cannot silently shadow
  package definitions.
- Normal component templates should be expressible with the portable template
  context. Registered host adapters are an exception, not the default.
- Visual regression tooling may be optional development tooling; it must not
  become a dependency of `docara build` for a consumer.

## 6. New debt register

The closed `2026-08-02-docara-architecture-documentation-debt-register.md`
covered localization, public documentation and release semantics. It does not
cover this newly discovered product extensibility scope.

| Debt | Priority | Current contradiction | Completion meaning |
| --- | --- | --- | --- |
| DOCARA-LEGO-001 | P1 | Docara and SF5 use different Smart manifest dialects | one portable v1 contract or proven lossless adapter |
| DOCARA-LEGO-002 | P1 | gateway dispatch accepts only `ui.*` and `docara.*` | provider registry supports an allowed project namespace |
| DOCARA-LEGO-003 | P1 | `SmartRenderer` has a component-name switch | strategy/factory registry has no built-in component ID branches |
| DOCARA-LEGO-004 | P1 | `ViewModelFactory` has one method per Smart | standard template context; exceptional adapter IDs are registered by provider |
| DOCARA-LEGO-005 | P1 | semantic props are hard-coded per `docara.*` | schema + optional named adapter validates/prepares input |
| DOCARA-LEGO-006 | P1 | contribution PHP lists every artifact | deterministic artifact discovery/compiled registry |
| DOCARA-LEGO-007 | P1 | only `ui.alert` and `ui.button` are admitted | exact-lock provider can expose the compatible Framework set without engine edits |
| DOCARA-LEGO-008 | P1 | no project Smart root | trusted project root with namespace/ownership/security policy |
| DOCARA-LEGO-009 | P1 | `DefinitionRepository::DEFINITIONS` is fixed | provider-backed DesignRegistry |
| DOCARA-LEGO-010 | P1 | layout/region/section/Smart allowlists are in resolver code | contracts drive composition; runtime enforces schema/capabilities |
| DOCARA-LEGO-011 | P2 | no isolated production-faithful preview | Smart/region/layout preview commands and parity tests |
| DOCARA-LEGO-012 | P2 | no scaffold/inspect/validate component SDK | stable CLI + JSON diagnostics |
| DOCARA-LEGO-013 | P2 | no bounded AI/MCP edit surface | optional adapter over the same application services |
| DOCARA-LEGO-014 | P1 | acceptance checks overclaim central-branch-free Smart extension | current acceptance is corrected; new extension gate starts unchecked |
| DOCARA-LEGO-015 | P1 | no cross-host portability proof | one artifact fixture passes Docara and SF5 runtime checks |

## 7. Target architecture

### 7.1 One directed pipeline

```text
Markdown + inherited config
  -> PageSource / ResolvedConfiguration
  -> typed Document IR in memory
  -> DocumentRendererRegistry
  -> SmartComponentGateway
       -> SmartRegistryCompiler
            -> ProjectSmartProvider
            -> PackageSmartProvider
            -> DocaraSmartProvider
            -> FrameworkLockSmartProvider
       -> SmartInvocationNormalizer
       -> SmartRendererStrategyRegistry
       -> SmartRenderArtifact
  -> DesignRegistry / LayoutComposer
  -> PageBuilderResult
  -> HTML + exact assets + indexes + diagnostics + receipt
```

There is no alternate path for preview. Preview creates a normal invocation or
layout plan, calls the same services, and places the result in a minimal outer
shell.

### 7.2 Portable project structure

```text
docara.json
redirects.json
simai-framework.lock.json

content/
  ru/
    lang.json
    section.json
    index.md
    guide.md
    guide.page.json

design/
  layouts/
    acme.docs.json
  views/
    layout.acme.docs.json
  sections/
    acme.hero.json
  blocks/
    acme.notice.json

smart/
  acme.notice/
    manifest.json
    view/
      default.json
      compact.json
    preset/
      info.json
      warning.json
    template/
      default.php
    assets/
      notice.css
      notice.js
    fixture/
      default.json
      empty.json
      long-text.json
    tests/
      contract.json

assets/
```

The singular artifact directories `view`, `preset`, `template` follow the
tracked SF5 artifact-root contract. Existing Docara plural paths are migrated
behind an internal compatibility adapter and removed after parity.

### 7.3 Smart invocation contract

One immutable invocation value object:

```text
SmartInvocation
  id: string
  instanceId: string
  view: string
  preset: ?string
  props: map
  slots: map<string, list<RenderArtifact|SmartInvocation|text>>
  context: RenderContext
  source: SourceLocation
```

Normalization order:

```text
manifest defaults -> preset props -> view props -> call props -> registered input adapter
```

The input adapter may only return normalized props/slots/diagnostics. It cannot
write files, fetch remote data, mutate global state or render HTML.

One result value object:

```text
SmartRenderArtifact
  html
  assets
  hydration
  diagnostics
  cacheMetadata
  provenance
```

### 7.4 Portable Smart manifest

The portable core follows the tracked SF5 schema, not the current Docara-only
schema:

```json
{
  "schemaVersion": "1.0",
  "kind": "smart",
  "code": "acme.notice",
  "title": "Notice",
  "render": {
    "mode": "server-first",
    "strategy": "server-static",
    "template": "default",
    "hydration": "none",
    "domStrategy": "none",
    "updateStrategy": "none",
    "initialHtml": "complete",
    "frontendOwnership": "none"
  },
  "props": {
    "title": {"type": "string", "required": true},
    "text": {"type": "string", "required": true}
  },
  "assets": {
    "css": ["assets/notice.css"],
    "js": [],
    "depends": ["simai.ui"]
  },
  "ai": {
    "useWhen": "Show persistent inline information.",
    "avoidWhen": "Do not use for transient toast messages.",
    "composeWith": ["acme.hero"]
  },
  "meta": {
    "ownerPackage": "acme/docs",
    "version": "1.0.0"
  }
}
```

Provider policy, not an authored absolute path, supplies trust root, namespace
owner, package identity and exact source revision.

### 7.5 Smart providers and precedence

Provider interface:

```text
SmartArtifactProvider
  id(): string
  priority(): int
  namespaces(): list<string>
  descriptors(): iterable<SmartArtifactDescriptor>
  fingerprint(): string
```

Recommended precedence:

1. project Smart provider — only the configured project namespace;
2. explicitly installed package providers — only their declared namespaces;
3. Docara package provider — `docara.*`;
4. exact Framework-lock provider — external ID `ui.<code>`, storage code
   `<code>` from the pinned Framework artifact projection.

Rules:

- deterministic path sorting before registry compilation;
- duplicate ID or alias is a hard error;
- project `ui.*` and `docara.*` are rejected by default;
- symlinks, traversal, absolute paths and paths outside an admitted root are
  rejected;
- no moving `main`/`latest` input;
- each descriptor records provider, root-relative paths, hashes and revision;
- the compiled registry primarily lives in memory; optional `_docara` catalog
  is derived debug/runtime output, not source of truth.

### 7.6 Rendering strategies instead of component switches

`SmartRendererStrategyRegistry` selects a renderer by manifest strategy, not by
component ID:

| Strategy | Result |
| --- | --- |
| `server-static` | complete server HTML, no hydration required |
| `server-first-hydratable` | complete initial HTML plus hydration metadata |
| `client-owned` | safe host/fallback, frontend owns interactive DOM |
| `shadow-dom-owned` | safe host and explicit shadow-DOM ownership |

Standard template context:

```text
id, smart, manifest, view, preset, props, slotsHtml, childrenHtml,
locale, direction, route, assetUrl(), escape(), renderChild()
```

Component templates never read `docara.json`, Markdown or global services.
Navigation, outline and brand data are prepared by PageBuilder/application
services and passed as props. Recursive rendering uses a generic child/partial
mechanism; it is not a `docara.navigation` branch in the central renderer.

### 7.7 Portable vs host-bound components

| Class | Rule | Portability claim |
| --- | --- | --- |
| portable | manifest + view/preset/template/assets; props fully prepared | Docara and SF5 compatible |
| host-assisted | declares a registered input adapter capability | compatible only on hosts implementing that adapter |
| client-owned | host/skeleton plus exact frontend runtime | compatible when required frontend assets are admitted |

No component is called portable merely because both systems accept its ID.
Compatibility evidence must include schema, props, template, asset and runtime
behavior.

### 7.8 DesignRegistry

Replace the constant definition table with provider-backed immutable catalogs:

```text
DesignRegistry
  LayoutRegistry
  ViewTreeRegistry
  SectionRegistry
  BlockRegistry
```

Provider roots:

1. `design/` project definitions in the configured namespace;
2. explicit package design roots;
3. built-in Docara definitions under package resources.

Each definition has:

- canonical ID and owner namespace;
- schema version;
- compatible regions/slots/blocks;
- referenced View Tree or renderer strategy;
- optional `extends` with exact base ID/version/hash;
- asset requirements;
- provenance and source hash.

No implicit shadowing. A local design either uses a new ID or an explicit,
version-bound extension relation.

### 7.9 Layout and region composition

`RegionCompositionResolver` becomes a generic validator/resolver:

```text
selected layout ID
  -> LayoutRegistry descriptor
  -> declared regions and required flags
  -> ordered Section invocations
  -> SectionRegistry compatible-regions/slots
  -> BlockRegistry compatible slots and payload schema
  -> Smart invocation through the one gateway
```

The current `main` ownership rule remains explicit in `docara.docs`: Markdown
owns its required `main` region. Another layout may define another region set,
but cannot bypass PageBuilder or inject arbitrary executable content.

View Trees continue to allow only validated element, region and slot nodes,
safe tags/attributes and exact Framework utilities. They remain JSON structure,
not a second programming language.

### 7.10 Preview architecture

One `PreviewKernel` accepts a target and fixture context:

```text
PreviewTarget
  smart(id, view, preset, fixture)
  region(layout, region, route|fixture)
  layout(layout, route|fixture)
  page(route, optional example id)
```

It calls production services:

```text
ProjectLoader -> registries -> SmartComponentGateway -> renderer
              -> asset collector -> LayoutComposer -> PreviewShell
```

Only `PreviewShell` and destination differ. Output is disposable under
`build_preview/` and never becomes a source or accepted full-build receipt.

Target CLI:

```bash
docara preview:smart acme.notice --view=default --fixture=default --watch
docara preview:region header --layout=docara.docs --route=/ru/start/ --watch
docara preview:layout docara.docs --route=/ru/start/ --watch
docara preview:page /ru/components/alert/ --isolate=example:closable --watch
```

Required preview states include default, empty, long text, invalid fixture,
light/dark, LTR/RTL, desktop/mobile, keyboard and reduced motion. Watch mode may
use a small PHP polling watcher; Node must not become a consumer requirement.

### 7.11 AI and SDK architecture

Application services are independent of Symfony Console formatting:

```text
DoctorService
InspectService
CatalogService
ScaffoldService
ValidationService
PreviewService
TestService
PatchPlanService
```

Human CLI and optional MCP both call these services.

Target commands:

```bash
docara doctor --json
docara list smart --json
docara list layouts --json
docara inspect page /ru/start/ --json
docara inspect smart acme.notice --json
docara inspect layout docara.docs --json
docara schema smart --json

docara component:new acme.notice --dry-run --json
docara component:new acme.notice --apply --json
docara component:validate acme.notice --json
docara component:test acme.notice --json
docara design:new layout acme.docs --dry-run --json
docara design:validate acme.docs --json
```

All write operations follow `dry-run -> hash-bound plan -> explicit apply`.
Allowed write roots are only project-owned `content`, `design`, `smart`,
`assets` and approved project config. Generated output, engine state, lock files
and external repositories are denied.

Component metadata for agents includes props, defaults, enums, slots, events,
states, accessibility, examples, `useWhen`, `avoidWhen`, `composeWith`, runtime
requirements, owner, provenance and readiness.

## 8. Migration strategy: one engine, no flag-day rewrite

### 8.1 Baseline freeze

Before each implementation goal:

1. record branch, HEAD, worktree and exact product baseline;
2. build a disposable full site and representative single pages;
3. store route/HTML/asset/metadata hashes and test command versions;
4. distinguish public parity from internal receipt/catalog changes;
5. do not touch `docara-new.test` or `docara.test`.

### 8.2 Internal replacement sequence

```text
existing public API
  -> add new provider/registry implementation behind the same gateway
  -> adapt one existing component/definition
  -> prove parity
  -> migrate remaining built-ins
  -> prove zero references to old switch/list
  -> delete the old internal path
```

Temporary adapters are allowed only inside the same pipeline and must have an
owner, removal gate and zero-reference evidence. A feature flag that leaves two
public engines indefinitely is forbidden.

### 8.3 Specification handling

The executor must not silently mark later goals implemented. At Goal 1 start:

1. add a graph decision proposal for the portable Smart contract;
2. correct the current central-branch-free Smart acceptance overclaim;
3. add a separate unchecked extensibility acceptance matrix;
4. update architecture/specification only after each contract is demonstrated;
5. bind every checked row to exact code, test and evidence paths.

## 9. Work breakdown: three executor goals

The budgets below are autonomous executor budgets, not a commercial human-hours
estimate and not elapsed calendar promises. Each goal is intentionally sized for
approximately 30–40 focused agent-hours and ends with an independent audit
checkpoint before the next goal is issued.

## Goal 1 — Portable Smart Runtime and project-local components

Budget: `36–40 agent-hours`
Dependencies: accepted architecture decisions LEGO-D02–D07
Primary outcome: one Framework-compatible Smart artifact runtime behind the
existing gateway, with no component-name switch and a proven local component.

### Goal 1 batches

| Batch | Work | Budget | Output/evidence |
| --- | --- | ---: | --- |
| G1.0 | freeze exact baseline; map every current Smart path and false acceptance claim | 3–4 h | baseline, implementation map, corrected acceptance proposal |
| G1.1 | add versioned SF5 Smart contract snapshot/adapter and positive/negative fixtures | 4–5 h | schemas, compatibility decision, contract tests |
| G1.2 | implement artifact descriptors, providers, deterministic registry compiler, namespace/duplicate/path policy | 6 h | provider tests and registry provenance |
| G1.3 | introduce normalized invocation, standard template context and strategy registry behind current Gateway | 6–7 h | no public API/pipeline split, renderer tests |
| G1.4 | migrate `ui.alert`, `ui.button` and all `docara.*` artifacts to provider-backed resolution | 7 h | per-component parity and asset/hydration evidence |
| G1.5 | add project `smart/` root and portable `fixture.notice`; prove no `src/` edit is needed | 4–5 h | local component example, security negatives |
| G1.6 | run Docara/SF5 cross-host fixture proof, full/single parity, docs/graph/evidence synchronization | 5 h | exact compatibility report and independent-ready handoff |

Expected total: `35–40 h`; do not pad work merely to consume budget.

### Goal 1 expected source surfaces

Likely new or substantially changed areas:

```text
src/Smart/Artifact/*
src/Smart/Provider/*
src/Smart/Runtime/*
src/Declarative/Smart/SmartComponentGateway.php
src/Declarative/Rendering/SmartRenderer.php
src/Declarative/Rendering/TrustedTemplateRegistry.php
resources/contracts/sf5/smart/v1/*
resources/smart/**
resources/schemas/**
tests/Unit/Smart*Test.php
tests/Fixtures/smart/**
docs/specification/**
docs/site/content/ru/development/**
graph/specs/**
source/workflow/evidence/<goal-1>/**
```

Exact class names may change after the first bounded design batch, but the
responsibility split and one-gateway constraint may not.

### Goal 1 Done When

- `SmartComponentGateway` has no namespace `if` deciding the renderer; provider
  ownership decides resolution.
- `SmartRenderer` has no match/list of component IDs.
- `ViewModelFactory` is removed or contains only generic registered adapters,
  not built-in ID methods.
- `CompositeSmartPlanResolver::assertSemanticProps()` component switch is gone.
- `DocaraSmartContribution` and `FrameworkSmartContribution` no longer list
  every artifact manually, or are reduced to provider-root declarations.
- project namespace and roots are validated deterministically.
- adding `fixture.notice` changes no engine PHP file.
- props, views, presets, templates, assets and hydration are validated before
  rendering.
- cross-host fixture evidence is explicit about compatible and host-bound parts.
- existing Markdown/config continues to build with public output parity.
- old registry/switch paths have zero runtime references before deletion.

### Goal 1 required checks

```text
focused Smart contract/provider/gateway/renderer tests
Framework lock and native surface tests
path traversal, symlink, duplicate, namespace collision and unsafe-template negatives
full test suite and formatter/static checks
full production build + representative --page builds
static verifier
two clean deterministic builds
Docara and SF5 cross-host portable fixture proof
browser smoke for Framework Alert/Button and all docara.* shell components
git diff --check and exact evidence hashes
```

### Goal 1 stop conditions

- Stop before inventing another manifest dialect if the tracked SF5 contract
  cannot represent a required Docara capability; record the exact missing field
  and request a cross-repository contract decision.
- Stop before treating uncommitted Framework work as canonical.
- Stop if project-local PHP would become reachable from Markdown/config paths;
  restore the trusted-developer boundary.
- Stop before deleting existing renderers until exact parity and rollback are
  proven.
- Do not start Goal 2 until an independent reverse-outcome audit accepts Goal 1.

## Goal 2 — Project Design Registry and production-faithful preview

Budget: `36–40 agent-hours`
Dependencies: accepted Goal 1 exact candidate
Primary outcome: local layouts/sections/blocks and fast isolated preview through
the production composition path.

### Goal 2 batches

| Batch | Work | Budget | Output/evidence |
| --- | --- | ---: | --- |
| G2.0 | freeze Goal 1 candidate; map definitions, schema enums, region and View Tree constraints | 3 h | exact baseline and design responsibility map |
| G2.1 | implement DesignArtifact descriptors/providers and deterministic DesignRegistry | 6 h | layout/view/section/block registry tests |
| G2.2 | migrate built-in `docara.docs` definitions from constant registration | 5–6 h | byte/semantic parity and zero-reference evidence |
| G2.3 | make region/section/block compatibility data-driven; add project `design/` root | 6 h | local layout/section/block fixture without `src/` edits |
| G2.4 | implement PreviewKernel/PreviewShell using production services and disposable outputs | 6 h | preview-to-production artifact parity tests |
| G2.5 | add Smart, region, layout and isolated-page preview commands with PHP watch mode | 6 h | CLI JSON contracts and live preview fixtures |
| G2.6 | add theme/direction/viewport fixture matrix, docs, examples, graph mappings and full regression | 5–6 h | exact QA/evidence handoff |

Expected total: `37–39 h`.

### Goal 2 expected source surfaces

```text
src/Design/Artifact/*
src/Design/Provider/*
src/Design/Registry/*
src/Declarative/Definition/DefinitionRepository.php
src/Declarative/Composition/RegionCompositionResolver.php
src/Declarative/Rendering/ViewTreeRenderer.php
src/Preview/*
src/Console/Preview*Command.php
resources/layouts/**
resources/views/**
resources/sections/**
resources/blocks/**
resources/schemas/**
stubs/design/**
tests/Unit/Design*Test.php
tests/Unit/Preview*Test.php
tests/Fixtures/design/**
docs/specification/**
docs/site/content/ru/authoring/**
docs/site/content/ru/development/**
graph/specs/**
source/workflow/evidence/<goal-2>/**
```

### Goal 2 Done When

- `DefinitionRepository::DEFINITIONS` is replaced by a deterministic registry.
- `RegionCompositionResolver` no longer hard-codes one layout, five regions,
  section IDs or `ui.alert`/`ui.button`.
- layout descriptors define their regions and required flags.
- sections define compatible regions/slots/blocks; blocks define their payload
  schema and Smart capability.
- a project-local layout, section and block are added without `src/` changes.
- existing `docara.docs` pages retain full/single build parity.
- Smart, region and layout preview call production registries, gateway,
  renderer, asset collector and composer.
- the same invocation/context produces identical inner HTML/assets/provenance in
  preview and production.
- preview does not create an accepted full-build receipt or overwrite a normal
  build.
- `--watch` works without Node and rebuilds only the affected target.

### Goal 2 required checks

```text
definition/provider duplicate, namespace, path and schema negatives
View Tree unknown kind/tag/attribute/utility/region/slot negatives
layout/section/block compatibility tests
preview-to-production artifact hash parity
watch dependency invalidation tests
full test suite
full + representative single-page build parity
static verifier
desktop/mobile, light/dark, LTR/RTL browser preview matrix
keyboard, reduced motion, console error and horizontal overflow checks
git diff --check and exact evidence hashes
```

### Goal 2 stop conditions

- Stop if preview needs a separate renderer, gateway or layout composer.
- Stop if View Tree begins to accept arbitrary HTML, CSS, PHP, callback or
  template paths from project config.
- Stop before allowing implicit override of package definitions.
- Stop before removing constant registrations until all built-ins pass parity.
- Do not start Goal 3 until an independent reverse-outcome audit accepts Goal 2.

## Goal 3 — Developer/AI SDK, structured QA and optional MCP

Budget: `36–40 agent-hours`
Dependencies: accepted Goal 2 exact candidate
Primary outcome: a stable application-service and CLI surface through which a
developer or agent can inspect, create, validate, preview and test Docara
artifacts safely.

### Goal 3 batches

| Batch | Work | Budget | Output/evidence |
| --- | --- | ---: | --- |
| G3.0 | define service contracts, JSON envelope, stable diagnostic taxonomy and edit boundaries | 4 h | SDK decision + golden JSON fixtures |
| G3.1 | implement `doctor`, `list`, `inspect`, `schema` services/commands | 5–6 h | human and JSON CLI acceptance |
| G3.2 | implement hash-bound Smart/design scaffolding with dry-run/apply | 6 h | safe new-component/layout flow |
| G3.3 | implement component/design validate and test commands over existing validators/preview | 5 h | fixture/state reports and exit codes |
| G3.4 | add screenshot, accessibility and visual-diff orchestration as optional development QA | 5–6 h | multi-state visual evidence; no consumer Node dependency |
| G3.5 | implement optional local MCP adapter over the same read/plan/apply services | 6 h | capability and write-boundary tests |
| G3.6 | finish AI metadata, tutorials, acceptance, graph mappings, deterministic package and security audit | 5 h | executor-ready release review dossier |

Expected total: `36–38 h`.

### Goal 3 expected source surfaces

```text
src/Application/*
src/Console/DoctorCommand.php
src/Console/List*Command.php
src/Console/Inspect*Command.php
src/Console/Schema*Command.php
src/Console/Component*Command.php
src/Console/Design*Command.php
src/Scaffold/*
src/Diagnostics/*
stubs/smart/**
stubs/design/**
tools/mcp-docara/ or a separately packaged optional adapter
tests/Unit/Application*Test.php
tests/Unit/Console*Test.php
tests/Unit/Scaffold*Test.php
tests/Integration/Mcp*Test.php
docs/specification/**
docs/site/content/ru/reference/**
docs/site/content/ru/development/**
graph/specs/**
source/workflow/evidence/<goal-3>/**
```

The exact MCP packaging location must be decided in G3.0. It must remain
optional and must not enter static-site runtime or normal consumer dependencies.

### Goal 3 Done When

- human and `--json` outputs are generated from the same result objects;
- stable diagnostic codes include source path/pointer/location, owner,
  provenance and actionable suggestion;
- an agent can list and inspect every Smart/design definition and its schema;
- scaffold dry-run returns a hash-bound plan and diff; apply fails if inputs
  changed;
- scaffold output is immediately valid and previewable;
- validation covers manifest, view, preset, template, assets, fixtures,
  namespaces, dependencies, accessibility metadata and AI guidance;
- visual QA covers declared fixtures/states/themes/directions/viewports;
- optional MCP delegates to application services and cannot bypass dry-run,
  root restrictions or diagnostics;
- no command writes generated output as source or edits lock/engine/external
  repositories;
- public developer documentation presents a short end-to-end LEGO workflow.

### Goal 3 required checks

```text
golden human/JSON command fixtures and stable exit codes
dry-run/apply stale-plan, traversal, symlink and forbidden-root negatives
scaffold -> validate -> preview -> test end-to-end fixture
MCP capability/read/write-boundary tests
screenshot/a11y/visual matrix for representative Smart/region/layout
full test suite and deterministic package build
fresh consumer init/build/verify without Node
README/CLI/docs contract tests
security and secret/path leakage scan
independent reverse-outcome audit
```

### Goal 3 stop conditions

- Stop if MCP duplicates validation/rendering instead of delegating to services.
- Stop if write commands can mutate generated, lock, engine-owned or external
  files.
- Stop if visual tooling becomes required by normal consumer build.
- Stop before release/tag/deploy; produce a separate release-review handoff.

## 10. Cross-goal acceptance matrix

| Scenario | Goal proving it | Evidence required |
| --- | --- | --- |
| Add local Smart without engine code | G1 | before/after source diff + rendered fixture |
| Use the same portable Smart in SF5 and Docara | G1 | two-runtime compatibility report |
| Use exact Framework Smart through lock | G1 | provider/lock/provenance + browser smoke |
| Add local layout/section/block without engine code | G2 | project fixture + full page |
| Preview one Smart without full-site build | G2 | dependency trace + timings + artifact parity |
| Preview header/sidebar/footer through production composition | G2 | region fixture/browser matrix |
| Preview complete layout | G2 | layout route/fixture matrix |
| Agent discovers valid props/views/presets | G3 | inspect/schema JSON golden files |
| Agent scaffolds safely | G3 | dry-run/apply/stale-plan/security tests |
| Agent runs visual/accessibility checks | G3 | screenshot/a11y reports |
| MCP cannot bypass policy | G3 | negative capability/write tests |
| Existing public site does not regress | every goal | full/single/static/browser/determinism evidence |

## 11. Risks and mitigations

| Risk | Impact | Mitigation |
| --- | --- | --- |
| tracked SF5 Smart contract is still `partial` | cross-host drift | pin schema/source revision, use adapter, prove fixture in both runtimes |
| project templates execute PHP | supply-chain/code execution risk | trusted developer root, explicit namespace, no content/config paths, package review |
| generic runtime becomes over-abstract | complexity replaces switches | start with existing six components and one local fixture; reject unused abstractions |
| migration creates two engines | parity ambiguity | new internals behind existing Gateway; remove old path in same goal |
| JSON View Trees become a programming language | hard maintenance | keep structural nodes only; logic stays in Smart/application services |
| preview drifts from production | false confidence | one service graph and artifact hash parity gate |
| local overrides break updates | hidden drift | no implicit shadowing; explicit new ID or version-bound `extends` |
| CLI/MCP semantics diverge | agent errors | shared application services and golden result objects |
| AI edits too broadly | repository damage | root allowlist, dry-run, hash-bound apply, stable denial diagnostics |
| release work mixes with productization | unstable site | separate branches/workflows and explicit release/deploy gates |

## 12. Owner and gate map

| Area | Owner | Reviewer | Gatekeeper |
| --- | --- | --- | --- |
| roadmap, scope, sequencing | teamlead fallback | dev | user review / reverse-outcome audit |
| Docara implementation | dev with repository contracts | sf5 for Smart boundary | tester |
| SF5 Smart compatibility | sf5 | dev | cross-host contract tests |
| author/developer documentation | documentation owner in repository | teamlead | documentation contract tests |
| preview UX/accessibility | dev + UX consultation | tester | browser/a11y matrix |
| CLI/MCP security | dev | security review | tester |
| release/runtime/deploy | ops in separate workflow | tester | explicit user approval |

The disabled installed Docara skill is not an owner source for these goals.
Repository-local specification/graph and actual code remain authoritative.

## 13. Executor operating contract

For each goal:

1. re-read this file, current `ACTIVE.md`, handoff status, accepted exact
   candidate, specification and graph before changes;
2. create a goal-specific workflow and launch/evidence record;
3. preserve user changes and stop on overlapping dirty files;
4. implement goal batches autonomously up to Done When or a real blocker;
5. keep one public PageBuilder and one Smart gateway at all times;
6. run focused checks after each batch and full checks at integration gates;
7. record exact commands, revisions, hashes and failures in fresh evidence;
8. do not claim compatibility from schema similarity alone;
9. do not claim completion from code/tests without local component and
   cross-host/product preview outcomes;
10. stop before merge, tag, release, deploy or external-repository mutation;
11. end with a clean worktree or explicitly inventory every remaining change;
12. request an independent audit before receiving the next 30–40 hour goal.

## 14. First executor goal handoff

The first executor should receive Goal 1 only, while retaining the complete
horizon in this file. Goal 2 and Goal 3 must not be started in parallel because
their contracts depend on the accepted result of the preceding goal.

Goal 1 runtime objective:

```text
Implement the Portable Smart Runtime and project-local Smart components for
Docara according to
source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md.
Preserve the existing Markdown -> typed Document IR -> renderer registry ->
SmartComponentGateway -> LayoutComposer -> PageBuilder pipeline. Adopt the
tracked SF5 Smart artifact v1 contract through a versioned, source-pinned
contract/adapter; add deterministic project/package/Docara/Framework Smart
providers and namespace ownership; replace component-ID branches in the Smart
renderer, ViewModel factory, semantic props and contribution lists with generic
strategy/context/registered-adapter resolution; migrate current ui.alert,
ui.button and all docara.* components behind the same Gateway; add one
project-local portable fixture component without changing engine src; and prove
Docara/SF5 cross-host compatibility, public output parity, security negatives,
determinism, full/single build parity, docs/graph accuracy and browser behavior.
Do not add a second renderer/gateway/PageBuilder, do not mutate external
Framework repositories, do not touch docara-new.test or docara.test, and stop
before merge/tag/release/deploy. Work autonomously through the Goal 1 batches
for approximately 36-40 agent-hours, record fresh exact evidence, and stop for
an independent audit only when Goal 1 Done When is satisfied or a stated stop
condition is reached.
```

## 15. User review point

Decision recorded: Goal 1 is authorized as a separate productization workflow.
Goal 2, Goal 3, release and deployment remain outside the current execution
boundary.

Alternatives:

1. Accept the plan as written and later issue Goal 1 to the executor.
2. Keep Docara package-maintainer-only and implement preview/AI tools without
   local extension roots; this is smaller but does not deliver the requested
   LEGO model.
3. First standardize the Smart contract in the Framework owner repository;
   this reduces adapter risk but delays Docara and requires a separate
   cross-repository owner workflow.

Recommended path is option 1 with a strict G1 cross-host gate. It advances
Docara now without silently changing Framework and still prevents an
incompatible Docara-only Smart species.

## 16. Planning simulation verdict

`GOAL_1_ACCEPTED_IN_PROGRESS`.

There are no blocking architecture questions for Goal 1. Its implementation is
tracked by the goal-specific workflow and evidence contour. The current rc.3
runtime and `docara-new.test` validation result remain intact and are not
superseded by this productization work.
