# Active workflow: Docara unified architecture

Date: 2026-08-02
Status: Goal 1-C implementation complete; independent audit pending
Workflow ID: `2026-08-02-docara-goal1-portable-smart-runtime`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active recovery: `source/workflow/2026-08-02-docara-goal1c-portable-smart-abi-correction.md`;
- fresh evidence: `source/workflow/evidence/2026-08-02-docara-goal1c-portable-smart-abi-resume/INDEX.md`;
- implementation candidate: `94d2afd9cb71d6b02d8f4a63d4f807e127b1b190`;
- branch: `codex/docara-unified-architecture`.

## Current stage and batch

- stage: `docara.stage.g1.portable_smart_runtime`;
- batch: `docara.batch.g1.portable_smart_runtime`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: independent reverse-outcome audit of Goal 1;
- Goal 2 remains unstarted.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
language packs contain interface messages, and generated IR/HTML remain
disposable.

## Current result

The neutral `sf.smart_artifact_abi` v1 contract is source-pinned and renders an
unchanged fixture byte-identically under Docara and exact SF5. Generic provider
ownership, the single Gateway and PageBuilder, security negatives, public
full/single parity, determinism and browser behavior are recorded in fresh
evidence. This is implementation evidence, not independent acceptance.

## Boundary

No mass rewrite, legacy deletion, default-branch merge, tag, release, public
deploy or readiness claim. The installed stale Docara skill is not a source of
truth for this track.
