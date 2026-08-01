# Active workflow: Docara unified architecture

Date: 2026-08-01
Status: architecture contract ready; implementation mapping not started
Workflow ID: `2026-08-01-docara-unified-architecture`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- exact baseline: `a3ba9a4d04429f1f2046b8415764fe7bc89962c7`;
- branch: `codex/docara-unified-architecture`.

## Current stage and batch

- stage: `docara.stage.m0.contract`;
- batch: `docara.batch.m0.mapping`;
- purpose: map the current implementation to the accepted architecture,
  reproduce the baseline and prepare one bounded vertical slice;
- runtime behavior must not change in this batch.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
language packs contain interface messages, and generated IR/HTML remain
disposable.

## Predecessor

The 2026-07-30 single-pipeline workflow and its evidence remain historical
input. Their status is not silently promoted into this new graph. M0 must
verify which parts are actually present at the exact baseline before any
implementation claim is made.

## Boundary

No mass rewrite, legacy deletion, default-branch merge, tag, release, public
deploy or readiness claim. The installed stale Docara skill is not a source of
truth for this track.
