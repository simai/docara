# Workflow: Docara legacy repositories retirement

Date: 2026-08-09
Status: blocked after partial completion
Process model: `general_delivery` with destructive/action gate
Track ID: `docara-legacy-repositories-retirement`
Current goal: preserve, verify, retire and remove the obsolete external
`docara-mix` and `docara-template` working copies without losing recoverability.

## Final Outcome

`simai/docara` on `main` remains the sole active Docara product repository.
No active default-branch consumer depends on `laravel-mix-docara`,
`simai/docara-template`, `docara-mix`, or the old external template runtime.
Both legacy repositories are archived on GitHub, their dirty recovery states
remain reproducible from verified local backup artifacts, and their local
working directories, recovery branches and stale worktree records no longer
remain in `/Users/rim/Documents/GitHub`.

## Current Baseline

- Canonical Docara repository: `/Users/rim/Documents/GitHub/docara`.
- Canonical branch: `main`.
- Baseline HEAD: `c5f6140a85435913a9d5f7389bdf34967d4d70f8`.
- `docara-mix` baseline: branch `codex/docara-mix-recovery`, HEAD
  `bf3bedee3d2b7d32d35bcae06c5f8e1691f71d80`, dirty recovery state.
- `docara-template` baseline: branch `codex/docara-template-recovery`, HEAD
  `bf1ac812e57fe80cfc8990ec8b52ad23b90f5616`, dirty recovery state.
- The installed Docara owner skill is stale/disabled and is not used or
  enabled. Federation fallback is `graph` for workflow, `dev` for repository
  operations, `ops` for backup/destructive gates and `tester` for the final
  retirement verdict.

## Done When

- An external timestamped backup root contains a verified all-refs Git bundle,
  binary tracked patch and archive of every non-ignored untracked source file
  for each legacy repository.
- Every bundle verifies, every tracked patch applies with `git apply --check`
  to its exact base, and every untracked archive lists the expected paths.
- Fresh scans of the current default branches of all previously known direct
  and indirect consumers find no active dependency, build, CI, install or
  documentation path requiring either legacy repository.
- The external template is either regenerated from current Docara by a proven
  active contract or, when no active consumer/contract requires it, classified
  as obsolete and archived. No ambiguous hand-maintained second template
  remains active.
- A destructive/action preflight passes with explicit scope, backup, rollback,
  verification and stop conditions before any GitHub archive or local removal.
- GitHub reports both retirement candidates as archived; no repository is
  deleted and no history is rewritten.
- Local legacy directories are removed from the GitHub workspace by a
  reversible move to Trash only after remote archival and backup verification.
- Recovery branches and stale worktree records are absent from the active
  workspace; current `docara` stays on clean `main` except for the committed
  retirement workflow/evidence.
- Final evidence names exact revisions, backup hashes, consumer scans, remote
  archive state, local cleanup state and rollback procedure.

## Constraints And Non-goals

- Do not enable or use the stale installed Docara skill.
- Do not release, tag, deploy, publish packages or change local/test/live sites.
- Do not delete GitHub repositories, force-push, reset, rewrite history or
  remove unrelated branches/worktrees.
- Do not read, print, copy or commit secrets. GitHub credentials, if available,
  must be resolved through SIMAI Access Center tooling and never logged.
- Any surviving active default-branch dependency stops retirement of the
  affected repository; preserve its backup and report the exact blocker.
- Existing historical workflow/evidence references are allowed and are not
  active runtime dependencies.

## Batch Plan

| Batch | Goal | Verification | Status |
| --- | --- | --- | --- |
| 1 | Freeze exact state and create rollback artifacts | bundle verify, patch apply-check, untracked archive listing, SHA-256 ledger | completed |
| 2 | Re-scan active default-branch consumers and decide template disposition | fresh clones/default refs, bounded reference matrix, retirement verdict | completed; template PASS, mix BLOCKED |
| 3 | Run destructive/action gate and archive eligible GitHub repositories | gate evidence, GitHub archived state, remote fetch/read smoke | completed for template; mix blocked |
| 4 | Remove local legacy working copies and stale recovery/worktree residue | reversible Trash move, workspace/ref/worktree inventory | completed for template; mix retained |
| 5 | Synchronize evidence and close the workflow | repository checks, diff check, clean committed `main` | completed |

## Rollback Contract

1. Unarchive the GitHub repository through the same authenticated GitHub
   control plane if remote restoration is required.
2. Restore the repository from its verified all-refs bundle.
3. Apply the tracked binary patch against the recorded exact HEAD.
4. Extract the untracked-source archive at the repository root.
5. Until Trash is intentionally emptied, the complete original working
   directory is also available under its timestamped Trash name.

## Evidence

Durable concise evidence is recorded under:

`source/workflow/evidence/2026-08-09-docara-legacy-repositories-retirement/`

Large bundles, patches, archives, clone scans and raw logs remain outside Git.

## Progress

### Batch 1

- Status: completed.
- Done: exact repository/branch/HEAD/dirty baseline inventoried; complete
  bundles, tracked patches and untracked-source archives created and restored
  in disposable roots.
- Verification: bundle completeness, exact checkout, patch apply-check,
  untracked archive extraction and SHA-256 ledger all pass.

### Batches 2-4

- Status: partial completion with a real stop condition.
- Done: every known consumer default ref fetched and scanned;
  `docara-template` independently classified as obsolete, archived on GitHub,
  verified readable and moved to Trash after stale-worktree pruning.
- Blocked: `docara-mix` remains an active dependency of five default branches
  and therefore remains unarchived and locally untouched.
- Evidence:
  `source/workflow/evidence/2026-08-09-docara-legacy-repositories-retirement/INDEX.md`.
- Remaining: a separately governed five-consumer migration/retest batch, then
  a new zero-reference scan and retirement gate.
- Evidence commit: `5537ad942d4c4ad3acfd6face9a343ea97e1aab1` on
  Docara `main`.
- Verification: project-context check returns `issues=[]`, JSON inputs parse,
  staged diff check passes, and the commit was pushed through the authenticated
  GitHub access helper.
- Next: do not manufacture completion by archiving a still-consumed package.
  Resume only as a separately governed five-consumer migration/retest batch.
