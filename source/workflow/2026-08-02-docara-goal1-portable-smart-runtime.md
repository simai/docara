# Goal 1 — Portable Smart Runtime and project-local components

Date: 2026-08-02
Status: `in_progress`
Project mode: `productization`
Process model: `general_delivery`
Current state: `repository_prepared`
Repository: `/Users/rim/Documents/GitHub/docara-unified`
Branch: `codex/docara-unified-architecture`
Input revision: `313afa17e21df2299a6276d246cb4508c7ec00b5`
Rollback boundary: input revision plus the separately committed G1 checkpoints
Parent roadmap: `source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md`
Evidence index: `source/workflow/evidence/2026-08-02-docara-goal1-portable-smart-runtime/INDEX.md`

## Goal

Implement the accepted Portable Smart Runtime and project-local component
contract while preserving the only public pipeline:

```text
Markdown -> typed Document IR -> DocumentRendererRegistry
-> SmartComponentGateway -> LayoutComposer -> PageBuilder
```

The implementation must consume the tracked, source-pinned SF5 Smart artifact
v1 contract through a bounded adapter, resolve project/package/Docara/Framework
providers deterministically, eliminate component-ID dispatch branches, migrate
all existing Smart components through the same gateway, and prove a portable
project-local Smart without an engine source edit.

## Primary user outcome

A project developer can add a trusted local Smart artifact under the declared
project namespace and use it through the normal Docara authoring/build path,
without modifying Docara engine PHP or creating a parallel renderer.

## Done When

- the existing `SmartComponentGateway` resolves ownership through providers and
  contains no namespace renderer branch;
- `SmartRenderer`, semantic props and adapter selection contain no component-ID
  switch/list;
- contribution classes are removed or reduced to provider-root declarations;
- project namespace/root, duplicate IDs, aliases, paths, symlinks, templates,
  props, views, presets, assets and hydration fail closed;
- `ui.alert`, `ui.button`, every `docara.*` and `fixture.notice` use one
  invocation/artifact contract and the one gateway;
- adding `fixture.notice` requires no change under `src/`;
- the same portable fixture passes Docara and tracked SF5 compatibility proofs,
  with host-bound differences explicitly classified;
- existing public Markdown/config output retains full/single parity,
  deterministic clean builds, static integrity and browser behavior;
- specification, graph, public docs and handoff describe only the implemented
  state;
- the worktree is clean at an exact independently reviewable candidate.

## Scope and non-goals

Allowed: this repository's runtime, resources, schemas, starter/project fixture,
tests, documentation, graph, workflow and handoff required for Goal 1.

Forbidden: Goal 2 design-registry/preview work; Goal 3 SDK/MCP work; changes to
external Framework repositories; writes to `/Users/rim/Sites/docara-new.test`
or `/Users/rim/Sites/docara.test`; merge, push, tag, release or deploy; arbitrary
PHP/template/callback paths in authored Markdown or JSON.

## Ownership and gates

| Responsibility | Owner | Reviewer/gatekeeper |
| --- | --- | --- |
| goal, batches, recovery | teamlead | reverse-outcome audit |
| Docara runtime and repository | dev | tester |
| portable SF5 boundary | sf5 | cross-host contract tests |
| security/path/template policy | dev | tester security negatives |
| docs/graph/handoff | repository contract | semantic docs/graph validators |

Action-gate preflight on 2026-08-02: env policy PASS, repo hygiene PASS,
source policy PASS. Federation local route was unavailable and the central
resolver selected an unrelated historical workflow; this workflow and launch
record replace that false association for Goal 1. Execution mode is
`single_agent`; no subagents are authorized.

## Batches

| Batch | Result | Status |
| --- | --- | --- |
| G1.0 | exact baseline, Smart path map, accepted contract, launch/evidence contour | pass |
| G1.1 | versioned source-pinned SF5 Smart v1 snapshot/adapter and fixtures | pass |
| G1.2 | descriptors/providers/compiler and ownership/path policy | pass candidate |
| G1.3 | normalized invocation/context/strategy registry behind existing gateway | ready |
| G1.4 | migrate `ui.alert`, `ui.button` and every `docara.*` | pending |
| G1.5 | project Smart root and `fixture.notice` without engine source edit | pending |
| G1.6 | cross-host/full-single/determinism/static/browser/docs/graph handoff | pending |

## Human-centered simplicity contract

- simplest complete alternative: one provider compiler and one generic render
  strategy layer behind the existing gateway; no generic plugin framework beyond
  the accepted Smart artifact responsibilities;
- protected complexity: namespace ownership, source pinning, path/symlink safety,
  deterministic precedence and provenance cannot be removed as "boilerplate";
- removal review: old switches/contributions are deleted only after positive
  parity and zero-reference evidence;
- progressive disclosure: authors keep Markdown/config; trusted developers see
  the local `smart/<id>/` contract; host adapters remain registered internals;
- automation review: no Goal 2 preview/SDK/MCP automation is introduced here.

## Checks and evidence

Every batch records exact parent/candidate SHA, changed surfaces, commands,
results, decisions and rollback path in the evidence index. Integration requires
focused Smart tests, Framework lock/native tests, security negatives, full
PHPUnit/Pint/Composer/lint/JSON/YAML/graph/diff checks, two clean builds,
representative single-page parity, static verification, cross-host proof and
browser smoke.

## Stop conditions

- the tracked SF5 v1 contract cannot represent a required portable capability;
- only uncommitted/unpinned Framework behavior could satisfy the contract;
- authored Markdown/config could select executable PHP/template paths;
- exact parity cannot be established before removing an old runtime path;
- continuation requires an external repository, live site, release action or a
  materially new public product decision.

## Recovery state

Current remaining after G1.2: 4 batches. Do not complete until all Goal 1 Done
When rows are evidenced or a stated stop condition is recorded. Next safe
batch: commit G1.2 separately, then continue directly to G1.3.
