# Goal S1-C2 — Relative Depth Contract Semantics

Date: 2026-08-06
Status: `goal_s1_correction_in_progress`
Track: `docara.track.surface-hero-media`
Parent goal: Goal S1 — Full-bleed Geometry & Shared Surface Runtime
Branch: `codex/docara-unified-architecture`
Rejected product candidate: `80b8102632c922ec44d16947456babeab6d15e25`
Entry governance HEAD: `5c3a181ff1f641bd239eedc3ef62d39c469015fd`

## Goal

Define and enforce `max_depth` relative to every container root: the container
itself is level 1. Make the canonical Surface -> Grid -> Card composition pass
the production PageBuilder while a true one-level overflow remains fail-closed.
Resolve Smart child capabilities exclusively from the current parent registry
contract and keep direct/production admission semantics aligned.

## Done When

- exact canonical Surface -> Grid -> Card source compiles to nested typed IR and
  renders Card through the production PageBuilder;
- Surface sees subtree depth 3/3 and Grid sees 2/2; child contracts never see a
  document-global depth;
- a registry fixture with max_depth one level lower rejects the exact offending
  child with stable code, safe source path, line and column;
- allowed Smart capabilities come from the parent descriptor and contain no
  component-ID, namespace or hard-coded capability dispatch;
- existing Surface project Smart, 1/64, empty/65, slot/order/fence/props and
  filesystem/security regressions remain green;
- full/build/package/consumer/browser evidence is fresh on one exact candidate;
- graph/spec/workflow/handoff stops at `goal_s1_ready_for_independent_audit`;
  S2 remains unstarted.

## Constraints

- one compiler, renderer registry, Smart Gateway, LayoutComposer and PageBuilder;
- no arbitrary CSS/class/PHP/callback/template/filesystem path;
- global directive/resource/security limits remain unchanged;
- no S2, Hero background mode, homepage art direction, merge, push, tag,
  release, deploy or external repository/site write.

## Batch plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| C2.0 | canonical production RED and registry overflow fixture | tests fail on `80b8102…` with exact reproduced code/location | in progress |
| C2.1 | relative subtree depth and descriptor capability resolution | focused compiler/validator/direct renderer tests | planned |
| C2.2 | proportional integrated retest | full, full/full/single, static, package/consumer/browser | planned |
| C2.3 | evidence/spec/graph/handoff | context/graph/diff/clean commit | planned |

## Routing and gate

The active federation route still selects the explicitly disabled stale Docara
skill and an unrelated publication process. Repository-local track, graph and
handoff remain authoritative. The low-risk repository preflight passed
environment, hygiene and source-policy gates; evidence is
`source/output/action-gates/action-gate-report-20260806145444.json`.

## Rollback

Revert the bounded S1-C2 product and governance commits in reverse order to
return to `5c3a181ff1f641bd239eedc3ef62d39c469015fd`. Disposable test, build,
package, consumer and browser roots are not source.

## Current next step

Commit a permanent canonical PageBuilder RED before changing validator logic.
