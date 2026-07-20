# Workflow: declarative primary publisher migration

Date: 2026-07-20
Status: completed
Workflow ID: `2026-07-20-declarative-primary-publisher-migration`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`
Parent track: `docara-consolidation`

## Goal

Replace the primary portable Docara page publisher with the accepted
declarative chain `Layout -> Region -> Section -> Block -> Smart` while
preserving current public behavior, URLs, deterministic portable builds and a
safe rollback to the byte-identical legacy renderer.

## Done When

- every current portable page class is published through the declarative
  renderer, including documentation pages, landing pages and generated
  component-catalogue pages;
- the primary output preserves URL/output paths, metadata, branding, navigation,
  search, breadcrumbs, outline, previous/next links, reader settings, assets,
  redirects and responsive shell behavior;
- `.docara/resolved-page-plans.json` records the selected publisher and enough
  generated-plan evidence to explain each page;
- unsupported or invalid declarative input fails closed before the destination
  is replaced;
- the accepted `PortableHtmlRenderer.php` remains byte-identical and available
  through an explicit bounded rollback mode until final acceptance;
- portable init/update/build and existing tests remain compatible;
- focused tests, the complete PHP 8.2 suite, deterministic double build,
  `verify-static`, URL/capability parity and desktop/mobile browser acceptance
  pass;
- the accepted candidate is staged, backed up and published only to
  `https://docara.test/`;
- documentation, changelog, workflow evidence and project memory describe the
  new primary path and rollback.

## Scope

Allowed:

- portable builder orchestration and declarative page/full-document rendering;
- registered layout/section/block/Smart definitions and trusted templates;
- generated diagnostics and compatibility tests;
- portable documentation, changelog, workflow/evidence and project memory;
- reversible local publication to `/Users/rim/Sites/docara.test`.

Forbidden:

- changing or deleting the accepted legacy renderer before acceptance;
- raw PHP, Blade, HTML, callbacks or filesystem paths in author JSON;
- writes to Larena, Simai Framework, Bitrix or other owner repositories;
- changing ServBay configuration;
- push, merge, tag, release or production deployment;
- secrets or destructive source/history operations.

## Capability Parity Matrix

| Capability | Legacy owner | Declarative target | Evidence | Status |
| --- | --- | --- | --- | --- |
| URL and output routing | `PortableSiteBuilder` | unchanged builder routing | route manifest and file inventory | pass |
| metadata and document shell | `PortableHtmlRenderer::render` | registered full-document publisher | HTML assertions | pass |
| docs and landing presets | legacy renderer methods | registered layout selection | page-class tests | pass |
| branding and favicon | builder + legacy renderer | composition/view model | HTML and asset checks | pass |
| nested navigation and active state | navigation builder + legacy renderer | registered navigation Smart | browser and DOM checks | pass |
| search trigger/dialog/runtime/index | builder + legacy renderer | registered search shell composition | static and browser checks | pass |
| breadcrumbs | legacy renderer | registered shell block/Smart | DOM checks | pass |
| outline desktop/mobile | legacy renderer | registered outline Smart | DOM/browser checks | pass |
| previous/next | legacy renderer | registered reading-navigation block | DOM checks | pass |
| reader settings and theme | legacy renderer | registered shell component/assets | browser persistence checks | pass |
| component catalogue | generated projection + legacy renderer | declarative page input and publisher | route/content tests | pass |
| Framework assets | builder | resolved asset plan + document publisher | asset/reference verification | pass |
| redirects and content assets | builder | unchanged publication services | manifest/static checks | pass |
| deterministic diagnostics | builder | publisher selection + render-plan receipt | double-build digest | pass |
| rollback | implicit legacy primary | explicit bounded legacy publisher mode | rollback build comparison | pass |

## Batches

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| 0 | Live baseline, routing, workflow and capability inventory | clean worktree, digests, current build inventory | completed |
| 1 | Publisher boundary and fail-closed selection | focused unit tests, legacy digest | completed |
| 2 | Declarative full-document shell and missing capabilities | page-class and negative tests | completed |
| 3 | Make declarative publisher primary and preserve explicit rollback | focused portable build tests | completed |
| 4 | Diagnostics, documentation and compatibility cleanup | docs contracts, changelog, diff check | completed |
| 5 | Full regression, deterministic builds and static parity | PHP 8.2 suite, two build digests, verifier | completed |
| 6 | Staged local deployment and browser acceptance | backup, HTTP, desktop/mobile scenarios, console | completed |
| 7 | Reverse completion audit, evidence, memory and commit | Done When matrix and clean commit | completed |

## Baseline Invariants

- candidate parent is `f123e5cbd5a5a3986e559512b3f43d4b3e054999`;
- legacy renderer digest must remain
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`;
- the current locally served build is the regression reference, not a
  production-readiness claim;
- build success and HTTP/browser success are separate gates;
- the PHP 8.4 inherited date snapshot drift is outside this migration; PHP 8.2
  is the supported acceptance runtime for the complete suite.

## Evidence

Root:
`source/workflow/evidence/2026-07-20-declarative-primary-publisher-migration/`

Required:

- `baseline.md`;
- `capability-parity.md`;
- `verification-summary.md`;
- `rollback.md`;
- `deployment.md`;
- `browser-acceptance.md`;
- `acceptance.md`.

## Progress

### Batches 0-6

- Status: completed.
- Publisher boundary, full-document template and shared shell assets added.
- Authored pages, landing and generated catalogue use the primary declarative
  path.
- `ui.button` admitted alongside `ui.alert`.
- Destination publication is candidate-first and transactional.
- Explicit legacy rollback and immutable digest are tested.
- Full suite, static, deterministic-build and browser gates passed.
- Exact candidate published only to `https://docara.test/` with backup.

### Batch 7

- Status: completed.
- Durable evidence recorded.
- Project memory updated; final hygiene and reverse completion audit passed.
- Local commit records the accepted repository state; no push or release was
  performed.

## Stop Conditions

- parity requires executable author configuration or unregistered templates;
- a current portable capability has no safe declarative representation;
- accepted legacy renderer bytes change;
- unrelated overlapping worktree changes appear;
- local publication lacks staging, backup, rollback and post-copy verification;
- a public release, production action, secret or destructive operation becomes
  necessary.
