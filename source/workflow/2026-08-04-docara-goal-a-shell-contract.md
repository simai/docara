# Goal A — Shell Contract & Safe Configuration

Date: 2026-08-04
Status: `in_progress`
Track: `docara.track.content_design_settings`
Goal: `docara.goal.a.shell_contract`
Baseline HEAD: `d748eca04cd09e79ed6e2079a56b077265bcf905`
Accepted Goal 3 product candidate: `1e571b6e16ebc4520121aff0ae868de3b986dff3`
Branch: `codex/docara-unified-architecture`
Evidence: `source/workflow/evidence/2026-08-04-docara-content-design-settings-goal-a/INDEX.md`

## Goal

Replace the closed Section binding list with one typed, provider-owned
`BindingRegistry`; expose a versioned shell-capability model; route the existing
`docara.navigation` through `header`, `tree` and `compact` presentations; and
prove a project-owned shell contribution without changing engine `src` for the
fixture.

The accepted production chain remains the only path:

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

## Done When

1. Every built-in binding has a typed descriptor with owner, provider,
   revision, capabilities, output schema, owned props and provenance.
2. Compiler/runtime selection contains no closed component-ID or namespace
   switch; config selects only registered binding IDs and presentation IDs.
3. Default output is byte-identical to the baseline.
4. `docara.navigation` renders `header`, `tree` and `compact` through one
   Gateway/composer path.
5. A project-local shell fixture is installed from project artifacts without
   an engine-source edit.
6. Executable paths/callbacks/classes/templates, binding-owned prop spoofing,
   duplicate ownership, capability mismatch, traversal, symlink and invalid
   final props fail closed with stable diagnostics.
7. Inspect/preview expose binding provenance and dependency trace.
8. Full/single equality, two-build determinism, preview/production parity,
   static verification and browser/accessibility checks pass.
9. Specification, graph, generated context, workflow and handoff bind one exact
   candidate and stop at `goal_a_ready_for_independent_audit`.

## Contract freeze

- Binding IDs: `docara.branding`, `docara.navigation`, `docara.outline`.
- Shell capabilities v1:
  `shell.brand`, `shell.primary-navigation`,
  `shell.secondary-navigation`, `shell.outline`, `shell.content-before`,
  `shell.content-after`, `shell.footer`.
- Navigation presentations: `header`, `tree`, `compact`; `default` remains a
  compatibility presentation inside the same Smart artifact.
- Merge order: validated static props, then binding-owned props, then final
  Smart manifest validation. A collision with a binding-owned prop is an error.
- Project JSON can select registered IDs and data only. It cannot register or
  name PHP classes, callbacks, methods, templates or filesystem paths.
- Outer page and `<head>` remain application-owned.
- Goal B names `docara.search`, `docara.breadcrumbs`, `docara.pager` are frozen
  as future names only; Goal A does not implement them.

## Ownership

- coordination/specification: repository graph and handoff;
- implementation: `dev`;
- verification gate: independent `tester` audit after executor handoff;
- installed stale Docara skill: disabled and forbidden;
- execution mode: single agent; no external repository or site writes.

Federation routing selected the disabled legacy owner and its process resolver
could not parse an older launch YAML. Raw repository SOT is therefore the
authorized fallback. The repository action-gate preflight passed env, hygiene
and source-policy checks with no blocker.

## Batches

| Batch | Outcome | Verification | Status |
| --- | --- | --- | --- |
| A0 | baseline, inventory, naming/decision freeze, graph activation | baseline full/single hashes, source map, context/graph checks | PASS |
| A1 | typed providers/descriptors/BindingRegistry and migrated built-ins | focused registry/schema/security and default parity | PASS |
| A2 | navigation header/tree/compact vertical slice | production/preview/browser matrix | in progress |
| A3 | project shell fixture through admitted capability | no-src fixture proof and negative security matrix | pending |
| A4 | full integration, determinism and legacy-reference audit | full tests/build/static/browser/zero-reference | pending |
| A5 | exact evidence and independent-ready handoff | graph/context/docs/hygiene and clean commits | pending |

## Allowed surfaces

- `src/Declarative/` binding/registry contracts and existing compiler/schema
  integration;
- existing DesignRegistry, discovery/inspect and PreviewKernel projections only
  where required to expose the accepted registry;
- registered layouts, Views, Sections, Blocks, Smart artifacts and project
  fixtures;
- tests, specification, graph, workflow, evidence and handoff.

## Forbidden surfaces/actions

- second parser, renderer, Gateway, DesignRegistry, LayoutComposer, PageBuilder
  or preview engine;
- project executable adapter/code/path/callback registration;
- Goal B/C implementation, mass publisher-chrome migration or docs IA move;
- external Framework repositories, `docara.test`, `docara-new.test`;
- merge, push, tag, release or deploy;
- legacy deletion without parity, zero-reference evidence and rollback.

## A0 baseline

- source baseline: `d748eca04cd09e79ed6e2079a56b077265bcf905`;
- accepted upstream/runtime product baseline: `1e571b6e16ebc4520121aff0ae868de3b986dff3`;
- full build: 104 routes, 307 files, 208 HTML;
- default tree ledger: `89576ca2f272f044be688d636cb19b2f88de39f3ac909426c64d577e112df7db`;
- Alert HTML: `e1803412dc2ed849afc2f74711831ab2309df9f39f90c2034d2db0c43a281131`;
- selected Alert rebuild preserves the complete ledger.

## Stop conditions

Stop only if the registry requires a second runtime path, project executable
code/arbitrary paths, an unapproved default-output change, an external owner
decision/write, overlapping user changes or evidence that cannot be made
independently reproducible. Ordinary implementation/test defects are corrected
inside Goal A.

## Progress

### A0

- Done: accepted Goal 3 audit recorded; proposal read and reconciled; exact
  branch/HEAD/status and action gates checked; closed binding match/schema and
  current shell call sites inventoried; baseline full/single output frozen;
  canonical graph/router activation and freshness checks passed.
- Next: implement A1 typed binding registry.

### A1

- Done: one deterministic provider-owned BindingRegistry; canonical built-in
  descriptors; internal storage aliases; descriptor output schemas; collision,
  target and presentation fail-closed checks; compiler ID match removed.
- Default parity: all 306 non-receipt files byte-identical; receipt delta is
  limited to the required engine source/tree digest. Full/single candidate
  receipt equality and Alert HTML parity pass.
- Next: A2 explicit navigation presentation selection through the same
  descriptor, Gateway and composer path.

## Rollback

The Goal A rollback boundary is `d748eca04cd09e79ed6e2079a56b077265bcf905`.
Each bounded batch receives a separate commit. No rollback requires a live site,
release or external repository mutation.
