# Goal 2 — Project Design Registry and production-faithful preview

Date: 2026-08-03
Status: `superseded_by_goal2c_correction`
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
| G2.1 | typed artifacts/providers and one deterministic DesignRegistry | provider/ownership/path/schema tests | pass |
| G2.2 | built-ins migrated from constant registration | byte parity, zero-reference scan | pass |
| G2.3 | data-driven composition and project `design/` fixture | no engine ID lists, fixture without `src/` edit | pass |
| G2.4 | PreviewKernel and PreviewShell over production services | HTML/assets/provenance parity, receipt isolation | pass |
| G2.5 | preview commands and PHP watch | human/JSON/exit-code fixtures, dependency invalidation | pass |
| G2.6 | integrated docs/graph/build/browser acceptance | full matrix and reverse-outcome evidence | pass |

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

### G2.1

- Added typed artifact kind/descriptor, provider interface, deterministic
  filesystem/built-in/project providers and one immutable registry.
- Namespace ownership is exclusive; priority orders discovery but cannot
  shadow an existing namespace or definition.
- Fixed roots, direct JSON artifacts, schema validation, source hashes and
  provider provenance are enforced. Missing optional project root is empty;
  symlink, outside-root, invalid ID/schema and reserved namespace fail closed.
- Focused registry suite: 6 tests / 18 assertions, PASS.
- Next: make DefinitionRepository consume the registry and remove its constant
  table only after built-in parity.

### G2.2

- `DefinitionRepository` is now a compatibility facade over the single
  DesignRegistry; the fixed `DEFINITIONS` table and literal artifact paths are
  removed.
- All 15 built-in definitions retain their paths/hashes while adding provider
  provenance.
- Exact input-versus-candidate full builds are recursively byte-identical:
  103 routes, 305 files, 206 HTML; static 21,430 references, broken=0.
- Focused compiler/Smart/registry tests pass and a permanent structural
  assertion rejects restoration of the constant table.
- Next: remove composition ID lists and wire trusted project `design/` into the
  production compiler.

### G2.3

- Layout artifacts now own region defaults and document placement; resolver
  and compiler contain no concrete layout, region, section, block or Smart ID.
- Block admission follows registered kind data and Smart identity is resolved
  by the accepted SmartRegistry/Gateway.
- Loader and builder share a project-aware registry rooted at confined
  `design/`; the tracked `acme.*` fixture compiles through production services.
- Public parity is preserved at 103 routes / 305 files / 206 HTML, broken=0;
  only internal resolved-plan provenance changes.
- Next: bounded preview targets over these same production services.

### G2.4

- PreviewKernel invokes the normal PortableSiteBuilder inside an explicitly
  isolated cache and extracts Smart/region/layout/page from exact production
  HTML and diagnostics. The original adjacent non-acceptance marker was not a
  trust boundary: the independent audit proved that normal `verify-static`
  accepted its receipt. Goal 2-C replaces this with typed receipt purpose and
  a verifier failure code.

### G2.5

- `docara preview smart|region|layout|page --page=...` emits human output or a
  stable JSON artifact contract with deterministic hashes and exit codes.
- `--watch` is PHP-only, monitors the route input chain plus locale UI copy and
  confined project design/Smart/assets, then runs only the selected route.
  Independent audit proved that this original closure included unrelated
  project artifacts and omitted package dependencies. Goal 2-C replaces it
  with selected project/package artifact trees and permanent edit/create/delete
  regressions.
- Watch interval/cycles are bounded and invalid selectors fail closed before
  any extraction or authored executable path can be accepted.
- Focused command/kernel/watch suite: 3 tests / 33 assertions, PASS.
- Next: documentation, graph/handoff and full build/browser acceptance.

### G2.6

- Public authoring and architecture documentation now describes the one
  package/project DesignRegistry and isolated production-path preview contract.
- Exact implementation candidate `33a377758f12d02a34e50c2f4f6d2aa760cf678b`
  passes 394 tests / 7,612 assertions, the focused Goal 1/Goal 2 matrix,
  formatter, Composer strict validation, lint and source-format checks.
- Two disposable full builds are byte-identical at 103 routes / 305 files /
  206 HTML. A single Alert rebuild preserves the complete ledger
  `a4045e033cea8ab7a8bb1ebde5d550fba42dc16482d41ad837c6a3fd7130e7eb`;
  static verification reports 21,421 references and broken=0.
- The Smart preview full-page shell is byte-identical to production Alert HTML
  (`a948b13465c5b2050107fd7ec3f44d7985c81219b5116bdcda6a0535f7b12703`)
  while its manifest remains explicitly non-accepted.
- Exact-build browser evidence covers 1920/1440/390, light/dark, reduced
  motion, search/settings/mobile focus and Esc, tabs/copy, LTR and an RTL
  logical-layout fixture with console warnings/errors=0 and overflow=0.
- Integrated commands, hashes, security/structural checks and rollback are in
  `G2.6-INTEGRATED-ACCEPTANCE.md`.

## Remaining

This candidate was rejected. Current recovery and evidence belong to
`source/workflow/2026-08-03-docara-goal2c-preview-validation-correction.md`.
Only a new independent reverse-outcome audit may accept Goal 2. Goal 3 is still
unstarted and unauthorized.
