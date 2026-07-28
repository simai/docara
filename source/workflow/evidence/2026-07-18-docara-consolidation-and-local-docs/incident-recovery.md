# Incident recovery

Date: 2026-07-18
Status: recovered and reverified

This is an immutable historical recovery checkpoint. Its test counts and tree
hash below are not final-candidate evidence; see
`exact-candidate-verification.md` for the current acceptance values.

## Incident

During Batch 1, a new TemplateMirror boundary test created a temporary symlink
from `tests/fixtures/tmp/repository-link` to the repository root. The legacy
shared test teardown recursively followed that symlink while removing its
fixture directory and partially deleted files in the dedicated clean Docara
worktree. The incident affected tracked files that could be restored from Git
and untracked in-progress workflow/core-test artifacts that had to be
reconstructed.

No evidence indicates a write to the user's dirty `docara`,
`docara-template`, `docara-mix`, or `ui-doc` worktrees.

## Safe Restoration Performed

1. The recovery boundary was fixed to
   `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation`
   at exact accepted HEAD
   `f51debe3e1def82d2dcf611bf820c4517cd65f8f`.
2. Deleted **tracked** files were restored path-by-path from that exact HEAD.
   Existing concurrent modifications were preserved; no whole-worktree
   checkout was used.
3. No `git reset --hard`, `git clean`, stash, force operation, or history
   rewrite was used.
4. The following untracked workflow artifacts are reconstructed from the
   accepted baseline and the active conversation/process record:
   - `source/workflow/2026-07-18-docara-consolidation-and-local-docs.md`;
   - `source/workflow/2026-07-18-docara-consolidation-and-local-docs.launch.yaml`;
   - `source/workflow/evidence/2026-07-18-docara-consolidation-and-local-docs/batch-0-preparation.md`;
   - `source/workflow/evidence/2026-07-18-docara-consolidation-and-local-docs/framework-component-classification.md`;
   - this incident record.
5. Untracked implementation-owned core tests and documentation were
   reconstructed and reverified. The unsafe test now links only two disposable
   temporary directories and always unlinks the symlink in `finally`, so the
   shared teardown cannot reach the repository root.

## Acceptance Impact

- Batch 0 remains complete because its exact base, isolation, inventory, and
  runtime preflight facts are independently reconstructable.
- Batch 1 recovery is complete. Focused tests pass with 65 tests and 434
  assertions; the full PHP 8.2 suite passes with 336 tests and 1102 assertions.
- Documentation was rebuilt twice after recovery. Both production trees have
  SHA-256 `6e11424fede7cc771fd4c179e3791871a193d5127410bd5b19cdaf1ef88c20e6`;
  39 HTML pages and 398 internal links were checked with zero broken links.
- Consumer inventory remains a read-only `PASS`, but no migration or retirement
  readiness follows from it.
- `docara-mix` retirement remains **NOT READY**.
- Tabs remain an exact Framework-contract blocker.

## Recovery Exit Criteria And Result

- implementation-owned lost tests reconstructed: **PASS**;
- focused tests and template verification: **PASS**;
- full PHP 8.2 suite from the recovered worktree: **PASS**;
- fresh deterministic documentation builds: **PASS**;
- `git diff --check` and targeted Pint: **PASS**;
- repo hygiene, source/secret gates, and process validation: required again on
  the exact commit before independent acceptance;
- independent tester must receive the exact post-recovery commit rather than
  any pre-incident output.

No release, archive, local runtime replacement, or readiness claim is allowed
from this recovery record alone.
