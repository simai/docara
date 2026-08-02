# Goal 2 — Project Design Registry and production-faithful preview

Date: 2026-08-03
Status: `in_progress`
Track: Docara extensible LEGO architecture
Current goal: Goal 2
Input repository revision: `40f5c5d14dce74383a57d408fd593507addd43d0`
Accepted Goal 1 runtime: `44acc1ff91233fa78140222fcb0589bf55b65ca0`
Accepted SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`
Independent audit marker: `019fc474-d670-77a2-a221-bc6061107c29`
Parent plan: `source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md`
Evidence: `source/workflow/evidence/2026-08-03-docara-goal2-design-registry-preview/INDEX.md`

## Goal

Turn the existing declarative JSON composition into one deterministic LEGO
extension contract. Project-local layouts, views, sections and blocks must be
discovered from trusted project roots without engine edits. Smart, region,
layout and isolated-page preview must reuse the production parser, registries,
Gateway, renderer, asset collector, LayoutComposer and PageBuilder.

## Done When

- `DefinitionRepository::DEFINITIONS` is gone and one `DesignRegistry` owns
  layouts, View Trees, sections and blocks;
- provider ownership, namespace, precedence, path and duplicate policy are
  deterministic and fail-closed;
- built-in `docara.docs` definitions use registry artifacts with public parity;
- `RegionCompositionResolver` has no layout/region/section/block/Smart ID list;
- a project-local layout, section and block work by project artifacts only;
- preview targets use production services and generate only disposable output;
- Smart/region/layout/page preview have stable human and JSON CLI contracts;
- PHP-only watch invalidates only the target dependency closure;
- full/single, determinism, static, browser, security, Goal 1 and source
  ownership regressions pass;
- graph, specification, docs and handoff describe exact implementation state;
- Goal 2 ends `ready_for_independent_audit`; Goal 3 stays unstarted.

## Launch Record And Safe-Write Boundary

Allowed:

- repository-local runtime, resources, schemas, stubs, tests, docs,
  specification, graph, workflow, handoff and evidence required by Goal 2;
- disposable build/preview directories outside accepted build output;
- small reversible commits after green checkpoints.

Forbidden:

- external Framework repositories, `docara-new.test`, `docara.test`;
- Goal 3 SDK/MCP/scaffolding platform;
- arbitrary executable/template paths from Markdown or project config;
- second parser, renderer, Gateway, LayoutComposer or PageBuilder;
- implicit package definition override;
- merge, push, tag, release or deploy.

Rollback: revert Goal 2 commits in reverse order. Built-in definitions remain
available as package-owned artifacts throughout migration; old paths are not
removed until parity and zero-reference evidence exist.

Stop conditions are the exact conditions in the assignment and parent plan.
Ordinary implementation/test/browser defects are corrected inside this goal.

## Batch Plan

| Batch | Outcome | Primary verification | Status |
| --- | --- | --- | --- |
| G2.0 | acceptance freeze, state-driven router, responsibility map | context positive/negative, graph/handoff consistency | pass |
| G2.1 | typed artifacts/providers and one deterministic DesignRegistry | provider/ownership/path/schema tests | in progress |
| G2.2 | built-ins migrated from constant registration | byte parity, zero-reference scan | pending |
| G2.3 | data-driven composition and project `design/` fixture | no engine ID lists, fixture without `src/` edit | pending |
| G2.4 | PreviewKernel and PreviewShell over production services | HTML/assets/provenance parity, receipt isolation | pending |
| G2.5 | preview commands and PHP watch | human/JSON/exit-code fixtures, dependency invalidation | pending |
| G2.6 | integrated docs/graph/build/browser acceptance | full matrix and reverse-outcome evidence | pending |

## Current Progress

### G2.0

- Accepted Goal 1 inputs and independent audit marker frozen above.
- Initial worktree was clean on the exact requested branch and HEAD.
- Federation route selected `dev` with `teamlead` and `graph`; stale Docara
  skill remained disabled. Local reversible gate passed after classifying the
  phrase-triggered false production warning from `production-faithful`.
- Responsibility inventory is recorded in the evidence contour.
- The canonical state now selects Goal 2 and Goal 3 is explicitly unstarted.
- `project-context.php` validates a reusable table of canonical values instead
  of Goal-number-specific prose. Nine positive/negative tests pass with 154
  assertions, including stale handoff after a legitimate regeneration.
- Next: implement typed design artifacts/providers and one registry.

## Remaining

All G2.1-G2.6 implementation and integrated acceptance remain. Goal 3 is not
authorized by this workflow.
