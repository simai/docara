# Active workflow: Docara unified architecture

Date: 2026-08-06
Status: Goal S1-C1 correction in progress after independent audit
Workflow ID: `2026-08-06-docara-goal-s1-c1-pipeline-container-correction`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active workflow: `source/workflow/2026-08-06-docara-goal-s1-c1-pipeline-container-correction.md`;
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

- state: `goal_s1_correction_in_progress`;
- goal: `docara.goal.unified`;
- stage: `docara.stage.s1.surface_runtime`;
- batch: `docara.batch.s1.pipeline_container_correction`;
- candidate: `45276f63422e8b8465b33e415d3fc302dfeac570`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: `execute_goal_s1_c1_pipeline_container_correction`;
- Goal 1-3 and Goals A-C remain independently accepted. The separately
  authorized Surface & Hero Media track is active only through completed Goal
  S1 correction; the first S1 candidate is rejected and S2 is unstarted.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
`content/<locale>/lang.json` contains shared interface messages, and generated
IR/HTML remain disposable.

## Current result

The first S1 candidate added one typed, registry-owned `docara.surface` and fixed landing
direct-child full-bleed contract and keeps documentation full width bounded to
its `main` region. Closed tokens and project-local media admission are
fail-closed. Existing Hero HTML is unchanged. Full/full/single, preview,
static, browser, package and fresh-consumer evidence pass on exact candidate
`45276f6…`, but independent audit proved nested Smart reparse/artifact loss,
container-contract drift, missing source locations and stale evidence. S1-C1
must correct those outcomes before a new candidate can return to audit.

## Boundary

No S2, Hero background mode, homepage art-direction change, mass rewrite,
default-branch merge, tag, release or deploy is authorized. The installed stale
Docara skill is not a source of truth for this track.
