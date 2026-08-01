# Docara M1/M2 bounded implementation plan

Date: 2026-08-01

Status: M1A, M1B and the bounded M2 badge slice passed with evidence

Parent goal: `docara.goal.unified`

Evidence: `source/workflow/evidence/2026-08-01-docara-unified-architecture/`

Execution boundary:

- action gate: preflight and pre-commit checks passed for this local,
  reversible, non-release batch;
- evidence path: `source/workflow/evidence/2026-08-01-docara-unified-architecture/`;
- stop conditions: any public URL, HTML, asset manifest or route-count drift;
  any source ambiguity not rejected before rendering; any need to delete legacy;
- rollback owner: executor, by reverting only the M1A commit.

## Required decision before coding

The graph-order divergence identified by M0 is resolved by `DOC-ADR-015`:

- M1 closes source ownership for the badge slice and prohibits new generated
  public routes/prose;
- a scoped `docara.gate.badge_source_ready` gate promotes to M2;
- the global `source_ownership` gate remains open until M3 has migrated all
  44 generated routes.

Do not silently reinterpret the current global gate in implementation code.

## Batch M1A: source locator and fail-closed route map

Goal: make physical Markdown discovery an explicit typed boundary without
changing rendered output.

Allowed production files:

- new `src/Content/PageSource.php`;
- new `src/Content/PageSourceLocator.php`;
- new `src/Content/RouteMapper.php`;
- the minimum integration seam in `PortableSiteBuilder` needed to replace
  `markdownFiles()` for authored routes;
- focused unit tests and graph/evidence updates.

Acceptance:

- every authored route resolves to exactly one physical source;
- duplicate/ambiguous routes, unknown locale and paths outside the locale
  content root fail closed with typed error codes;
- route order is stable;
- the 103-route baseline, full-build manifest and badge HTML remain unchanged;
- the 59-vs-58 stale test expectation is corrected to source-derived evidence,
  not another hard-coded count.

Rollback: revert the locator integration seam and new classes/tests; no
content, config or generated output migration is involved.

## Batch M1B: new-source boundary guards

Goal: stop creation of new prose-bearing config/language-pack/catalog records
without deleting the existing 44-route legacy baseline.

Allowed production files:

- source-boundary validator and versioned schema/profile needed for new target
  pages;
- focused negative fixtures/tests;
- graph decision and gate update accepted before code.

Acceptance:

- new page/section/site composition rejects Markdown prose, HTML and CSS;
- `content/<locale>/lang.json` is the only target store for shared visible
  locale strings and rejects page/component prose;
- public `resources/i18n` and `site.json` target inputs are rejected;
- package-owned CLI/build localization is outside PageBuilder inputs;
- new component manifests reject public page prose;
- the existing legacy inventory is an explicit finite allowlist with owner,
  deletion gate and zero-growth assertion;
- badge has no runtime dependency on a language-pack component record.

Rollback: remove the new guard/profile and restore the previous schemas; the
legacy data remains untouched in this batch.

## Batch M2: badge vertical slice

Goal: route `content/ru/components/badge.md` through the target pipeline while
preserving exact public HTML/assets.

Allowed production surface:

- typed native in-memory Document IR nodes and source locations needed by badge;
- generic `component` node for inline/block authoring calls;
- shared alias registry entry `badge -> docara.badge`;
- one Smart gateway and renderer contract returning HTML, assets,
  diagnostics and provenance;
- a single-page `PageBuilderResult` and the minimum full/single integration;
- badge-focused tests and snapshots.

Forbidden:

- other component-page migration;
- global renderer rewrite beyond nodes exercised by badge;
- legacy deletion outside the inactive badge path;
- template/assets/content redesign;
- release, deploy or readiness claim.

Acceptance:

- badge IR snapshot is produced through diagnostic/test serialization only and
  contains typed headings, paragraphs, tables, code blocks,
  example blocks and component nodes with file/line/column;
- all 16 badge preview calls resolve through the alias registry and the single
  Smart gateway;
- `InlineComponentRenderer::badge` is not active for the badge route;
- full and isolated builds produce the baseline HTML SHA-256
  `faeb6c6a8e075bff9ad5602bcea4b1e019c700aeae74f696c0289e32fbb83f79`
  and an identical asset manifest;
- full and isolated modes call the same PageBuilder pipeline and differ only
  in the selected route set;
- no mandatory intermediate page JSON/JSONL is written;
- unknown alias/prop/slot/node errors include the physical Markdown source
  location;
- focused unit/integration tests, full PHPUnit, formatter, deterministic build,
  static verification and browser light/dark desktop/mobile evidence pass;
- LTR is verified for this Russian slice; RTL remains a later locale-wide gate
  unless an RTL badge fixture is explicitly included without expanding public
  content.

Rollback: keep the M0 manifest and badge hash, revert the badge PageBuilder
switch and target classes as one commit, and return the route to the frozen
legacy path. Do not delete the legacy implementation until the slice gate has
independent PASS evidence.

M2 verdict: PASS. Exact evidence is recorded in `M2-EVIDENCE.md`. This closes
the bounded M1/M2 assignment and promotes M3 planning only; it does not claim
global source ownership, release or production readiness.
