# Goal S2-C1 — Hero media diagnostic-location correction

Date: 2026-08-06
Status: `goal_s2_ready_for_independent_audit`
Parent goal: `docara.goal.s2`
Entry product candidate: `794fac076be86ed4d03167120800ab0e91715aff`
Entry governance HEAD: `ed6aab88e09388e113ec0db4f7e760e70371d40b`
Independent verdict: `CORRECTION_REQUIRED`
Branch: `codex/docara-unified-architecture`

## Goal

Preserve the accepted Hero/Surface behavior and security policy while making
every Hero image URL/admission failure emitted through production PageBuilder
point to the exact authored Markdown image path, line and column.

## Done When

- data, javascript, protocol-relative, remote, missing, traversal, case,
  symlink and hardlink image failures have stable codes and exact image
  locations through real PageBuilder;
- missing/extra image, alt/mode/variant and unknown-prop diagnostics retain
  their truthful locations;
- one compiler/typed IR/renderer/Gateway/PageBuilder path remains;
- focused/full/build/static/package/consumer/browser proportional regression
  is green on one exact replacement candidate;
- graph, generated context, specification, workflow and handoff stop at
  `goal_s2_ready_for_independent_audit`; S3 remains unstarted.

## Constraints

- do not weaken generic unsafe-URL or local-only asset admission;
- do not change Hero modes, Surface construction, DOM/accessibility, asset
  receipts, homepage/default output or the accepted S1 contract;
- no second parse/render path, component-ID dispatch, external write, release
  action or S3 work.

## Batch plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| C1.0 | Freeze boundary and reproduce lost location | exact data/javascript PageBuilder RED | complete |
| C1.1 | Propagate the image SourceLocation across early URL validation | production-path location matrix | complete |
| C1.2 | Focused/full and deterministic integration retest | tests/build/static/package/consumer/browser | complete |
| C1.3 | Exact evidence and router/handoff synchronization | graph/context/diff/clean worktree | complete |

## Process record

The federation route used its central legacy-project fallback and selected the
general goal protocol at low confidence. The explicit independent correction,
existing repository workflow and exact Done When resolve that review without a
new product decision. Execution is single-agent; no subagents are used.

## Rollback

Revert the bounded S2-C1 implementation and handoff commits to governance HEAD
`ed6aab8…`; the rejected audit candidate `794fac0…` remains immutable history.

## C1.1 result

The exact PageBuilder RED returned line 1 for a line-5 `data:` Hero image.
`PortableMarkdownRenderer` now relocates the unchanged generic unsafe-URL
exception to `/document/hero/image`; related image alt/none/structure failures
use the same existing image location. The permanent matrix covers all required
URL/filesystem classes through PageBuilder. Focused Hero tests pass 9/152 and
the Hero/Surface/Atlas/PageBuilder/build contour passes 74/1,485.

## C1.2-C1.3 result

Exact replacement product candidate:
`7eeba4ad7b5acd00f833bf2022e45775444fb69c`.

Full PHPUnit passes 502/10,650. Fresh full/full/single outputs contain 393
files and share canonical digest `108cba01…`; static checks are 266 HTML /
35,583 references / broken=0. Two clean clones reproduce verified ZIP
`40d86ea6…`; the fresh dist consumer passes init/doctor/full/single/static and
shares 198-file digest `8fed7a14…`. Desktop/mobile real-browser smoke is clean.
Exact commands and hashes are in
`source/workflow/evidence/2026-08-06-docara-surface-hero/S2-C1-DIAGNOSTIC-LOCATION-CORRECTION.md`.
