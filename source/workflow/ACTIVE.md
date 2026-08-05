# Active workflow: Docara unified architecture

Date: 2026-08-05
Status: Goal C C1 correction complete; independent audit pending
Workflow ID: `2026-08-05-docara-goal-c-c1-truthfulness-correction`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active recovery: `source/workflow/2026-08-05-docara-goal-c-c1-truthfulness-correction.md`;
- active track: `source/workflow/2026-08-04-docara-content-design-settings-track.md`;
- parent Goal 3 recovery:
  `source/workflow/2026-08-03-docara-goal3-security-diagnostics-visual-correction.md`;
- fresh evidence: `source/workflow/evidence/2026-08-05-docara-goal-c-c1-truthfulness-correction/INDEX.md`;
- project-context freshness correction:
  `source/workflow/2026-08-03-docara-goal1d-project-context-correction.md`;
- rejected audit candidate: `c5ea85f8d25deff99b671486fdc4d1e820a86491`;
- corrected implementation candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`;
- rejected Goal 2 candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`;
- corrected Goal 2 candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`;
- branch: `codex/docara-unified-architecture`.

## Current state, stage and batch

- state: `goal_c_ready_for_independent_audit`;
- goal: `docara.goal.unified`;
- stage: `docara.stage.c.public_documentation`;
- batch: `docara.batch.c.public_documentation`;
- candidate: `eb35f5c6f18e5eb9be69e91887b09486f5703136`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: `independent_goal_c_reverse_outcome_audit`;
- Goal 1-3, Goal A and Goal B are independently accepted. Goal C is implementation-complete and audit-pending; release review remains unauthorized.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
`content/<locale>/lang.json` contains shared interface messages, and generated
IR/HTML remain disposable.

## Current result

Goal A exact product/runtime candidate `8c04160…` and Goal B exact product
candidate `c3b91eee…` were independently accepted. Exact accepted
input/dropdown/checkbox and list-item owner packets are consumed through the
single Gateway path; the useful dropdown admits only text list-item children.
Install Builder controls update one safe copy-only command, and the configurator
dropdown plus checkboxes update one local total. Goal C C1 now publishes 132
Markdown-owned routes, six truthful admitted catalog entries, exhaustive
schema-derived settings and one executable safe agent journey. Atlas/schema
projections are hash-bound derived views; full/full/single, package/consumer,
browser and static evidence pass on candidate `eb35f5c6…` without adding a
publication path.

## Boundary

No mass rewrite, legacy deletion, default-branch merge, tag, release, public
deploy or readiness claim. The installed stale Docara skill is not a source of
truth for this track.
