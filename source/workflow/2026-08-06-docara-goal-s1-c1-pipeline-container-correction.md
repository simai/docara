# Goal S1-C1 — Pipeline and Container Contract Correction

Date: 2026-08-06
Status: `goal_s1_ready_for_independent_audit`
Track: `docara.track.surface-hero-media`
Parent goal: Goal S1 — Full-bleed Geometry & Shared Surface Runtime
Branch: `codex/docara-unified-architecture`
Rejected product candidate: `45276f63422e8b8465b33e415d3fc302dfeac570`
Rejected governance HEAD: `f99ce6a653aeca9fc2ccf5434fc094b7cb8ca66e`

## Goal

Make nested Surface content a first-class typed IR contour, aggregate nested
Smart artifacts without reparse or loss, execute the registry-owned container
contract exactly, preserve real source locations and replace stale acceptance
evidence on one new exact candidate.

## Done When

- variable-length fences and admitted nested typed/Smart children compile once
  in `MarkdownCompiler` and render once through the existing runtime;
- a real docs/site Surface -> `project.product-configurator` page carries HTML,
  CSS/JS, hydration, provenance and Framework receipts exactly once;
- one generic container validator enforces slot/min/max/order/depth/capability,
  including accepted 1 and 64 children and rejected empty/65;
- invalid props/children/count/depth/order/fences expose stable codes and exact
  safe source path/line/column;
- Hero HTML remains byte-identical, all regression/package/consumer/browser
  matrices are fresh, and canonical tree digests use the repository algorithm;
- graph/spec/workflow/handoff stop at `goal_s1_ready_for_independent_audit`;
  S2 remains unstarted.

## Review triage

| Remark | Status | Decision | Verification |
| --- | --- | --- | --- |
| Surface nested Smart bypasses typed IR | `valid_fix` | Move nested directive parsing into `MarkdownCompiler`; delete Surface mini-parser | PageBuilder artifact/asset/hydration/provenance integration test |
| Atlas max/count/depth contract differs from runtime | `valid_fix` | One registry-driven validator with separate bounded resource budget | 1/64 accepted; empty/65/order/depth/capability rejected |
| Surface diagnostics lack file location | `valid_fix` | Propagate source spans from compiler to failures | actual file-backed command/integration tests |
| Published digest/counts are stale | `valid_fix` | Preserve old hashes as rejected history and rebuild exact evidence | two clean full roots, single, static, canonical digest |

## Constraints and stop conditions

- one Markdown compiler, renderer registry, Smart Gateway, LayoutComposer and
  PageBuilder only;
- no arbitrary CSS/class/PHP/callback/template/filesystem paths;
- no weakening of global marker/depth/security budgets;
- Hero default output and accepted Goals 1-3/A-C remain regression baselines;
- stop on overlapping user changes, required external owner change, second
  runtime path, or inability to preserve Hero parity;
- S2, release, merge, push, tag, deploy and external repository/site writes are
  forbidden.

## Action gate and routing notes

- exact workspace/branch/cleanliness preflight: PASS at `f99ce6a…`;
- local reversible federation gate: PASS for env, repo hygiene and source
  policy; evidence
  `source/output/action-gates/action-gate-report-20260806132645.json`;
- an earlier route/gate attempt matched the literal negative phrase “no live”
  as production. It is a recorded control-plane false positive, not authority
  to enter an ops/release process;
- process resolver currently fails while parsing an unrelated existing launch
  YAML (`AttributeError: 'str' object has no attribute 'append'`). Repository
  graph/workflow/handoff remain the project-local process source.

## Batch plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| C1.0 | RED fixtures, IR/runtime/contract/source-span map | exact reproductions and focused RED tests | in progress |
| C1.1 | nested compiler IR and artifact aggregation | compiler/PageBuilder/Smart integration tests | complete |
| C1.2 | generic container contract validator and locations | full positive/negative matrix | complete |
| C1.3 | docs/site real-project and browser hydration | preview/production/browser evidence | complete |
| C1.4 | full/build/package/consumer retest | full PHPUnit, deterministic trees/packages | complete |
| C1.5 | evidence/spec/graph/handoff | freshness/context/graph/diff, clean commits | complete |

## Rollback

The correction boundary is `f99ce6a653aeca9fc2ccf5434fc094b7cb8ca66e`.
Each bounded correction commit can be reverted in reverse order. Disposable
build, preview, package and consumer roots are not source.

## Result and next step

Product candidate `80b8102632c922ec44d16947456babeab6d15e25`
closes the reproduced defects. The only next action is an independent Goal S1
reverse-outcome audit. S2 remains unstarted and unauthorized.
