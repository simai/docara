# Goal S2 — Hero Background Media on the Shared Surface

Date: 2026-08-06
Status: `goal_s2_ready_for_independent_audit`
Workflow ID: `2026-08-06-docara-goal-s2-hero-background-media`
Track: `docara.track.surface-hero-media`
Entry product candidate: `ac53ea4d372a47dc8278b595accca9e7b85c66a3`
Entry governance HEAD: `4feb910b4d1a822dd323d559855c020ba4e3480d`
Independent S1 verdict: `PASS`
Branch: `codex/docara-unified-architecture`
Exact product candidate: `7eeba4ad7b5acd00f833bf2022e45775444fb69c`

## Authority and process selection

The independent S1 verdict explicitly authorizes only Goal S2 from the
repository-owned Surface & Hero track. The federation resolver selected the
`teamlead` owner with the repository graph/workflow fallback; the explicit
user assignment resolves the low-confidence process choice to the existing
goal protocol. One executor owns integration and no subagents are used.

## Outcome

Extend the existing semantic `docara.hero` with
`media=auto|side|background|none`. The default remains byte-identical. The
background mode consumes one admitted local Markdown image and delegates its
geometry, decorative layer, overlay and content boundary to the accepted
shared Surface presentation. No second parser, registry, renderer, Gateway,
LayoutComposer or PageBuilder is allowed.

## Batches

- S2.0: record S1 acceptance, freeze baseline HTML and launch S2.
- S2.1: typed Hero media/overlay contract and exact diagnostics.
- S2.2: shared Surface presentation integration and local-asset admission.
- S2.3: Atlas/catalog/schema and isolated production-path example.
- S2.4: focused/full/build/package/consumer/browser acceptance.
- S2.5: exact evidence, graph and handoff; stop for independent audit.

## Acceptance

- absent `media` and explicit `media=auto` reproduce the accepted S1 HTML;
- `side`, `background` and `none` implement the exact track semantics;
- every invalid combination and asset fails closed with source path, line and
  column;
- the decorative background is rendered once with empty alt and
  `aria-hidden=true`, while semantic content and focus remain above it;
- default public pages and homepage art direction are unchanged;
- focused/full tests, preview/production, full/full/single, static,
  package/consumer and browser matrices bind one exact candidate;
- final state is `goal_s2_ready_for_independent_audit`; S3 stays unstarted.

## Final result

S2.0-S2.5 plus correction S2-C1 are complete on exact product candidate
`7eeba4a…`. Full PHPUnit is 502/10,650; fresh full/full/single share 393 files
and canonical digest `108cba01…`; static checks 266 HTML / 35,583 references /
broken=0. Two clean clones reproduce verified ZIP `40d86ea6…`; fresh dist
consumer and proportional desktop/mobile browser regression are green.
Detailed commands and hashes are linked from
`source/workflow/evidence/2026-08-06-docara-surface-hero/INDEX.md`.

The result is executor-ready only. Next action is
`independent_goal_s2_reverse_outcome_audit`; S3 remains unauthorized.

Independent audit returned `CORRECTION_REQUIRED` for exact candidate
`794fac0…`: early generic unsafe-protocol rejection lost the Markdown image
line for `data:` and `javascript:` URLs in production PageBuilder. Completed
bounded correction S2-C1 is tracked in
`source/workflow/2026-08-06-docara-goal-s2-c1-hero-diagnostic-location.md`;
`794fac0…` and ledger `7a8c5645…` remain rejected history.

## Safety and rollback

Only repository product, tests, specification, graph, workflow and handoff are
in scope. External repositories/sites and release actions are forbidden.
Rollback is the parent of each bounded S2 commit; the accepted S1 product
candidate remains the immutable comparison boundary.
