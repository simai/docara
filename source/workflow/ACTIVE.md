# Active workflow: Docara unified architecture

Date: 2026-08-08
Status: local Framework runtime independently accepted; ready for main convergence
Workflow ID: `2026-08-08-docara-main-convergence`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active workflow: `source/workflow/2026-08-08-docara-main-convergence.md`;
- completed parent track: `source/workflow/2026-08-06-docara-surface-hero-track.md`;
- parent Goal 3 recovery:
  `source/workflow/2026-08-03-docara-goal3-security-diagnostics-visual-correction.md`;
- fresh evidence: `source/workflow/evidence/2026-08-08-docara-v2-independent-main-readiness/INDEX.md`;
- project-context freshness correction:
  `source/workflow/2026-08-03-docara-goal1d-project-context-correction.md`;
- rejected audit candidate: `c5ea85f8d25deff99b671486fdc4d1e820a86491`;
- corrected implementation candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`;
- rejected Goal 2 candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`;
- corrected Goal 2 candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`;
- branch: `codex/docara-unified-architecture`.

## Current state, stage and batch

- state: `ready_for_main_convergence`;
- goal: `docara.goal.unified`;
- stage: `docara.stage.lfr.local_framework_runtime`;
- batch: `docara.batch.lfr.integrated_retest`;
- candidate: `d5e9ecbb1b65904b4015c4a8b8db3aa66d7fe30f`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: `framework_main_convergence_then_docara_repin`;
- Goal 1-3 and Goals A-C remain independently accepted. The separately
  authorized Surface & Hero Media track is active only through completed Goal
  S1 and S2, which are independently accepted. Goal S3 remains complete. The
  SF5 5.4 typography integration remains the accepted parent candidate. The
  explicitly authorized local Framework runtime correction is independently
  accepted with `PASS_WITH_NOTES`. No S4 exists.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
`content/<locale>/lang.json` contains shared interface messages, and generated
IR/HTML remain disposable.

## Current result

Docara now publishes the exact required SIMAI Framework runtime, icons, fonts,
lazy theme utility and authored highlight chunks from a content-addressed local
projection. Exact candidate `d5e9ecb…` completes the local Outlined projection,
restores the Success Alert icon through the existing semantic success token and
reorders the Alert guide into the same compact table/example sequence as Badge.
Full/full/single and static outputs are deterministic. Browser checks emit no
external font requests.
The authorized local validation site `docara-new.test` was atomically switched
to the exact build with a preserved rollback backup; no production, release or
external owner write occurred. C1 binds the local Outlined font to direct icon
hosts; C2 adds exact source-pinned Rounded and Sharp variable fonts so every
family demonstrated on the public Icon page renders as a glyph rather than a
ligature string.

The accepted S1 candidate counts depth relative to every container root, so the
canonical Surface -> Grid -> Card chain passes both Surface 3/3 and Grid 2/2
contracts. Smart capabilities come from the current parent descriptor, not a
fixed capability branch. Nested IR, exactly-once Smart artifact aggregation,
source locations, Landing Hero geometry and HTML semantics remain intact. S1-C3
corrects only its build ledger and router: fresh full/full/single and a clean
clone all produce 393 files at canonical digest `650a678c…`; both full roots
pass 266 HTML / 35,581 references / broken=0.

Goal S2 adds closed Hero media modes while keeping the default Hero bytes and
delegating background presentation to the same Surface primitive. S2-C1 keeps
the unchanged fail-closed policy while relocating every Hero image failure to
the authored image. Exact candidate `7eeba4a…` passes 502/10,650 PHPUnit,
full/full/single digest `108cba01…`, verified ZIP `40d86ea6…`, fresh consumer
and clean proportional desktop/mobile browser regression.

Goal S3 routes Surface, Hero, Showcase and Promo outer presentation through one
`SurfacePresentation`, while their default renderer bytes and semantic content
remain unchanged. Fresh full/full/single trees contain 393 files at digest
`99ab56df…`; verified package ZIP is `4c5496b3…`; a fresh dist consumer and
30-scenario browser matrix pass.

## Boundary

No S4/Goal D, homepage art-direction change, mass rewrite, tag, release or
deploy is authorized. The user has separately authorized the bounded
Framework -> Docara -> ui-doc GitHub `main` convergence tracked by the external
coordination workflow. The installed stale Docara skill is not a source of
truth for this track.
