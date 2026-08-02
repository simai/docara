# Active workflow: Docara unified architecture

Date: 2026-08-03
Status: Goal 2 in progress
Workflow ID: `2026-08-03-docara-goal2-design-registry-preview`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active recovery: `source/workflow/2026-08-03-docara-goal2-design-registry-preview.md`;
- fresh evidence: `source/workflow/evidence/2026-08-03-docara-goal2-design-registry-preview/INDEX.md`;
- project-context freshness correction:
  `source/workflow/2026-08-03-docara-goal1d-project-context-correction.md`;
- rejected audit candidate: `c5ea85f8d25deff99b671486fdc4d1e820a86491`;
- corrected implementation candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`;
- branch: `codex/docara-unified-architecture`.

## Current state, stage and batch

- state: `goal2_in_progress`;
- goal: `docara.goal.unified`;
- stage: `docara.stage.g2.design_registry_preview`;
- batch: `docara.batch.g2.design_registry_preview`;
- candidate: `40f5c5d14dce74383a57d408fd593507addd43d0`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: `execute_g2_1_design_registry`;
- Goal 1 is independently accepted; Goal 3 remains unstarted and unauthorized.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
language packs contain interface messages, and generated IR/HTML remain
disposable.

## Current result

The neutral `sf.smart_artifact_abi` v1 contract and the single Gateway/provider
runtime were independently accepted. Goal 2 now introduces a deterministic
Design Registry and isolated preview over the same production services without
a parallel engine.

## Boundary

No mass rewrite, legacy deletion, default-branch merge, tag, release, public
deploy or readiness claim. The installed stale Docara skill is not a source of
truth for this track.
