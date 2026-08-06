# Active workflow: Docara unified architecture

Date: 2026-08-06
Status: Goal S1-C2 complete and ready for independent audit
Workflow ID: `2026-08-06-docara-goal-s1-c2-depth-contract-correction`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active workflow: `source/workflow/2026-08-06-docara-goal-s1-c2-depth-contract-correction.md`;
- active track: `source/workflow/2026-08-06-docara-surface-hero-track.md`;
- parent Goal 3 recovery:
  `source/workflow/2026-08-03-docara-goal3-security-diagnostics-visual-correction.md`;
- fresh evidence: `source/workflow/evidence/2026-08-06-docara-surface-hero/INDEX.md`;
- project-context freshness correction:
  `source/workflow/2026-08-03-docara-goal1d-project-context-correction.md`;
- rejected audit candidate: `c5ea85f8d25deff99b671486fdc4d1e820a86491`;
- corrected implementation candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`;
- rejected Goal 2 candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`;
- corrected Goal 2 candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`;
- branch: `codex/docara-unified-architecture`.

## Current state, stage and batch

- state: `goal_s1_ready_for_independent_audit`;
- goal: `docara.goal.unified`;
- stage: `docara.stage.s1.surface_runtime`;
- batch: `docara.batch.s1.depth_contract_correction`;
- candidate: `ac53ea4d372a47dc8278b595accca9e7b85c66a3`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: `independent_goal_s1_reverse_outcome_audit`;
- Goal 1-3 and Goals A-C remain independently accepted. The separately
  authorized Surface & Hero Media track is active only through completed Goal
  S1 correction; the first S1 candidate is rejected, the correction awaits
  independent audit, and S2 is unstarted.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
`content/<locale>/lang.json` contains shared interface messages, and generated
IR/HTML remain disposable.

## Current result

The correction candidate counts depth relative to every container root, so the
canonical Surface -> Grid -> Card chain passes both Surface 3/3 and Grid 2/2
contracts. Smart capabilities come from the current parent descriptor, not a
fixed capability branch. Nested IR, exactly-once Smart artifact aggregation,
source locations, Landing Hero geometry and HTML semantics remain intact.
Fresh full/full/single, static, browser, package, consumer and cross-host
evidence is bound to exact candidate `ac53ea4…`.

## Boundary

No S2, Hero background mode, homepage art-direction change, mass rewrite,
default-branch merge, tag, release or deploy is authorized. The installed stale
Docara skill is not a source of truth for this track.
