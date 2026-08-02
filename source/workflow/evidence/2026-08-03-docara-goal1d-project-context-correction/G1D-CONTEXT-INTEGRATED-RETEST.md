# Goal 1-D project-context correction — integrated retest

Date: 2026-08-03
Input revision: `65097a45b2a39ec8350c0f4a05f95dc7c9c80590`
Router implementation revision: `facafaf`
Runtime implementation candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`
Branch: `codex/docara-unified-architecture`
Status: `ready_for_independent_audit`

## Outcome

The repository now has one deterministic current-state router. Canonical
`graph/graph.json` selects Goal 1, its G1 stage/batch, runtime candidate and
independent audit as the next action. The generated packet and all handoff
surfaces agree. Goal 2 is unstarted and unauthorized. The old R2 release
baseline is retained only as non-executable history.

No file under `src/`, `resources/`, `docs/site/content/` or `stubs/` differs
between the input revision and `facafaf`. Goal 1 runtime, public content, ABI,
providers and browser assets were not changed.

## Before and after semantic diff

At the input revision the canonical graph/STATUS/ACTIVE selected Goal 1, while
`graph/generated/ai-context/docara-unified.json` selected R2 production
readiness, `docara.batch.r2.prepare_deployment`, candidate `be0ba2d...` and a
deploy decision. `START.md` separately instructed a new executor to run M1A/M1B.

After correction the active packet is:

- state: `goal1_ready_for_independent_audit`;
- stage: `docara.stage.g1.portable_smart_runtime`;
- batch: `docara.batch.g1.portable_smart_runtime`;
- candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`;
- next action: `independent_goal1_reverse_outcome_audit`;
- Goal 2: `unstarted`, `authorized=false`;
- R2: historical `parked_not_current`, `executable=false`.

The generated packet's canonical input SHA-256 is
`c672c9ad05ec743948eaf981f8c8dc3e19641a26943909216fed95e87750c89c`.

## Deterministic generation and freshness regression

Repository-owned commands:

```text
php scripts/project-context.php generate
php scripts/project-context.php check
```

`check` regenerated the projection in memory and compared it byte-for-byte,
then checked internal graph stage/batch/candidate/next/evidence references and
the semantic markers in START, STATUS, ACTIVE, NEXT, RESULT and the LEGO plan.

`ProjectContextContractTest` passed 9 tests / 126 assertions. Its negative
fixtures independently stale the stage, batch, candidate, next action and
evidence. A separate case proves that regenerating JSON cannot mask a stale
handoff. The regression also rejects active R2/deploy/M1 directives.

## Source and contract verification

- focused documentation/context/cross-host matrix: 26 tests / 1,647
  assertions, PASS;
- project-context suite: 9 tests / 126 assertions, PASS;
- full PHPUnit under PHP 8.4.20: 383 tests / 7,473 assertions, PASS;
- exact pinned SF5 cross-host regression: 1 test / 45 assertions, both hosts
  exit 0, stderr/warnings empty, byte-identical HTML SHA-256
  `7133c5dcd44aa85f351a85c61c280aa883abd5cdb3c91206168ad63ada497b38`;
- Pint `--test`: PASS;
- Composer `validate --strict`: PASS;
- project graph: 1 goal, 9 stages, 12 batches, 6 mappings, warnings=0,
  blockers=0;
- JSON/YAML, PHP lint, link/schema checks and `git diff --check`: PASS.

The machine-readable cross-host result is `cross-host-report.json` in this
directory.

## Build, static and single-page invariance

Two independent `git archive facafaf` source trees with the same dependency
tuple produced identical outputs:

| Check | Result |
| --- | --- |
| routes | 103 / 103 in each build |
| files | 305 in each build |
| HTML files | 206 in each build |
| sorted relative-path/file-hash ledger | `15a5f11f78c9dd115a2f388eafc303709b890922dd4a689ee8b1942d24c2121c` |
| recursive A/B diff | empty |
| static verification | 21,430 local references, broken=0 in each build |

A subsequent `--page=/ru/components/alert/` build retained the complete
305-file ledger. Alert HTML remained
`c3aa323c61b09595c46e805e3d9336f8ae049d8d843af7e3f32d74d3839b9f60`.
This confirms the accepted single PageBuilder result without a runtime change.

## Browser evidence binding

No browser run is presented as new. The implementation-bound matrix in
`source/workflow/evidence/2026-08-02-docara-goal1d-generic-smart-view-correction/browser-results.json`
is rebound to this governance revision because the complete runtime/public
source boundary (`src`, `resources`, `docs/site/content`, `stubs`) has an empty
diff from the independently tested input revision. It continues to cover brand
modes, Framework Alert/Button, Docara shell Smart components, desktop/mobile,
console errors and overflow. This is a provenance rebind, not fresh visual
evidence.

## Rollback and nonclaims

Rollback is `git revert facafaf` followed by the evidence-only commit that
contains this document. It changes only project governance, handoff, generated
context, the repository-owned checker and its tests; historical evidence is
not deleted.

Goal 1 is not independently accepted here. Goal 2 and Goal 3 are unstarted.
No release, deploy, tag, merge, push, external repository or site write was
performed.
