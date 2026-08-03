# Active workflow: Docara unified architecture

Date: 2026-08-03
Status: Goal 2 ready for independent audit
Workflow ID: `2026-08-03-docara-goal2c-preview-validation-correction`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active recovery: `source/workflow/2026-08-03-docara-goal2c-preview-validation-correction.md`;
- fresh evidence: `source/workflow/evidence/2026-08-03-docara-goal2c-preview-validation-correction/INDEX.md`;
- project-context freshness correction:
  `source/workflow/2026-08-03-docara-goal1d-project-context-correction.md`;
- rejected audit candidate: `c5ea85f8d25deff99b671486fdc4d1e820a86491`;
- corrected implementation candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`;
- rejected Goal 2 candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`;
- corrected Goal 2 candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`;
- branch: `codex/docara-unified-architecture`.

## Current state, stage and batch

- state: `goal2_ready_for_independent_audit`;
- goal: `docara.goal.unified`;
- stage: `docara.stage.g2.design_registry_preview`;
- batch: `docara.batch.g2.design_registry_preview`;
- candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: `independent_goal2_reverse_outcome_audit`;
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
runtime were independently accepted. Goal 2-C corrects the rejected preview
trust boundary, standalone assets, selected dependency closure and registry-time
View Tree validation while preserving one production path. Executor verification
is complete; only an independent Goal 2 reverse-outcome audit is current. Goal 3
stays unstarted.

## Boundary

No mass rewrite, legacy deletion, default-branch merge, tag, release, public
deploy or readiness claim. The installed stale Docara skill is not a source of
truth for this track.
