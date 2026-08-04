# Active workflow: Docara unified architecture

Date: 2026-08-04
Status: Goal A implementation in progress
Workflow ID: `2026-08-04-docara-goal-a-shell-contract`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active recovery: `source/workflow/2026-08-04-docara-goal-a-shell-contract.md`;
- active track: `source/workflow/2026-08-04-docara-content-design-settings-track.md`;
- parent Goal 3 recovery:
  `source/workflow/2026-08-03-docara-goal3-security-diagnostics-visual-correction.md`;
- fresh evidence: `source/workflow/evidence/2026-08-04-docara-content-design-settings-goal-a/INDEX.md`;
- project-context freshness correction:
  `source/workflow/2026-08-03-docara-goal1d-project-context-correction.md`;
- rejected audit candidate: `c5ea85f8d25deff99b671486fdc4d1e820a86491`;
- corrected implementation candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`;
- rejected Goal 2 candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`;
- corrected Goal 2 candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`;
- branch: `codex/docara-unified-architecture`.

## Current state, stage and batch

- state: `goal_a_in_progress`;
- goal: `docara.goal.unified`;
- stage: `docara.stage.a.shell_contract`;
- batch: `docara.batch.a.shell_contract`;
- candidate: `1fb4b5c6c1cb72c29d61b8f85959966438202474`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: `goal_a_binding_registry_implementation`;
- Goal 1-3 are independently accepted; Goal B and release review remain unauthorized.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
`content/<locale>/lang.json` contains shared interface messages, and generated
IR/HTML remain disposable.

## Current result

Goal 3 exact product candidate `1e571b6…` was independently accepted. Goal A
now replaces the closed shell binding list with one typed provider-owned
BindingRegistry, proves `docara.navigation` presentations and a safe project
shell contribution, and must preserve the accepted production path and default
output. No release-review action is authorized.

## Boundary

No mass rewrite, legacy deletion, default-branch merge, tag, release, public
deploy or readiness claim. The installed stale Docara skill is not a source of
truth for this track.
