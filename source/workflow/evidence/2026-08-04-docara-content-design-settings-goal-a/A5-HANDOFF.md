# A5 — independent-audit handoff

Date: 2026-08-04
Status: `ready_for_independent_audit`
Exact product candidate: `8c04160ab50549b060fb933cf80f86193cd92113`
Baseline: `d748eca04cd09e79ed6e2079a56b077265bcf905`

## Current router

Canonical graph, generated project context, START, STATUS, ACTIVE, NEXT,
RESULT, ROADMAP and ACCEPTANCE all point to:

```text
state: goal_a_ready_for_independent_audit
candidate: 8c04160ab50549b060fb933cf80f86193cd92113
next_action: independent_goal_a_reverse_outcome_audit
```

Goal B, release review, merge, push, tag and deploy remain unauthorized.

## Reproduction summary

- full PHPUnit: 452 tests / 8,225 assertions;
- focused documentation/context/binding/discovery: 31 / 1,847;
- focused Binding/Design/Preview/QA/security: 76 / 490;
- exact SF5 cross-host: 1 / 44, byte-identical;
- Pint, Composer strict/audit, PHP lint 340, JSON 607, YAML 34: PASS;
- project-context generate/check: PASS, issues `[]`;
- graph: 1 goal / 12 stages / 15 batches / 4 metrics / 8 mappings,
  warnings=0, blockers=0;
- two full builds: 104 routes / 307 files / 208 HTML, complete tree
  `2e1ecaa1da0d5d0303b65b450d8655e16992377c7f26055f7713a9afad5d9d42`;
- selected Alert rebuild: same complete tree;
- static verification: 21,844 local references, broken=0;
- navigation browser QA: 24/24 across header/tree/compact;
- candidate-range and worktree `git diff --check`: PASS.

## Independent audit entry points

1. read the Goal workflow and this evidence index;
2. inspect commits `1fb4b5c`, `01deb3b`, `ac34558`, `d2ea745`, `c6fcd07`
   and `8c04160`;
3. reproduce focused/full tests and the two-build ledger;
4. validate each saved browser plan/reference/report and screenshot hashes;
5. test negative ownership, capability, prop and path cases;
6. confirm the exact candidate contains one BindingRegistry, one Gateway and
   one PageBuilder, with no central binding/component identity dispatch.

## Nonclaims

This handoff does not independently accept Goal A, authorize Goal B/C, publish
a package, update either test site or open any release/production gate.

## Rollback

Use normal `git revert` of the bounded Goal A commits in reverse order. The
baseline is immutable, public/project content was not migrated, and no external
state was changed.
