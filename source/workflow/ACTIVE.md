# Active workflow: Docara unified architecture

Date: 2026-08-04
Status: Goal B ready for independent reverse-outcome audit
Workflow ID: `2026-08-04-docara-goal-b-interface-library`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active recovery: `source/workflow/2026-08-04-docara-goal-b-interface-library.md`;
- active track: `source/workflow/2026-08-04-docara-content-design-settings-track.md`;
- parent Goal 3 recovery:
  `source/workflow/2026-08-03-docara-goal3-security-diagnostics-visual-correction.md`;
- fresh evidence: `source/workflow/evidence/2026-08-04-docara-goal-b-interface-library/INDEX.md`;
- project-context freshness correction:
  `source/workflow/2026-08-03-docara-goal1d-project-context-correction.md`;
- rejected audit candidate: `c5ea85f8d25deff99b671486fdc4d1e820a86491`;
- corrected implementation candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`;
- rejected Goal 2 candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`;
- corrected Goal 2 candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`;
- branch: `codex/docara-unified-architecture`.

## Current state, stage and batch

- state: `goal_b_ready_for_independent_audit`;
- goal: `docara.goal.unified`;
- stage: `docara.stage.b.interface_library`;
- batch: `docara.batch.b.interface_library`;
- candidate: `c3b91eee71ab906cd79ae7a119c6961664f03528`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: `independent_goal_b_reverse_outcome_audit`;
- Goal 1-3 and Goal A are independently accepted; Goal C and release review remain unauthorized.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
`content/<locale>/lang.json` contains shared interface messages, and generated
IR/HTML remain disposable.

## Current result

Goal A exact product/runtime candidate `8c04160…` was independently accepted.
Goal B exact product candidate `c3b91eee…` completes B0-B6. Exact accepted
input/dropdown/checkbox and list-item owner packets are consumed through the
single Gateway path; the useful dropdown admits only text list-item children.
Install Builder controls now all update one safe copy-only command, and the
configurator dropdown plus checkboxes update one local total. Full/package/
two-consumer/browser/security evidence is fresh. Goal B is ready
for independent audit, not self-accepted, and Goal C remains unauthorized.

## Boundary

No mass rewrite, legacy deletion, default-branch merge, tag, release, public
deploy or readiness claim. The installed stale Docara skill is not a source of
truth for this track.
