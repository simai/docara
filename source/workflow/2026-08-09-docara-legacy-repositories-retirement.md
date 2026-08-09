# Workflow: Docara legacy repositories retirement

Date: 2026-08-09
Status: completed
Process model: `general_delivery` with destructive/action gate
Track ID: `docara-legacy-repositories-retirement`
Current goal: terminal closeout; the five consumers are migrated and both
obsolete external repositories are archived with verified rollback evidence.

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

## Authorized Resume: Five-consumer Migration

User authorization: migrate the five active default-branch consumers to their
existing Vite migration intent, independently retest the exact current-main
integrations, repeat zero-reference proof, then archive and locally retire
`docara-mix` only after the gate passes.

### Resume Done When

- `simai-env`, `publications`, `sf4-doc`, `ui-doc-core`, and `sitepack` each
  have a verified rollback bundle covering current refs and any dirty state.
- The migration intent is replayed onto each freshly fetched `origin/main`
  without using stale branch history as current evidence.
- Every repository passes its available focused install/build/test checks from
  the integrated candidate; dirty primary worktrees remain untouched.
- Exact integrated commits reach their GitHub `main` only after checks and an
  action gate; no force push or history rewrite occurs.
- Fresh default-branch scans and GitHub code search return zero active
  `laravel-mix-docara`/`docara-mix` references.
- Obsolete migration branches/worktrees are removed only after the matching
  `main` contains and verifies the intended changes.
- `docara-mix` is archived, remains readable, and its dirty local recovery
  directory is removed from the workspace only after the zero-reference and
  rollback gates pass.

### Resume Stop Conditions

- Stop an individual consumer if the existing migration does not replay cleanly
  or its current main has materially changed the frontend contract.
- Stop default-branch mutation if install/build/test fails, a dirty primary
  worktree would be overwritten, or the candidate is not a fast-forward from
  the current remote main.
- Stop `docara-mix` retirement if any active default branch, CI path, generated
  package or documented install path still requires it.

### Resume Batch Plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| R1 | freeze five repos and migration refs | exact refs/status/worktrees plus verified bundles/patches | completed |
| R2 | replay migrations in detached clean worktrees | diff review, no stale source replacement, candidate refs | completed |
| R3 | install/build/test exact candidates | repository-specific checks and package output | completed |
| R4 | fast-forward GitHub mains and clean migration refs/worktrees | remote SHA, merged ancestry, zero unique refs | completed |
| R5 | zero-reference retest and `docara-mix` retirement | Git/default-branch/API scan, archive/read smoke, rollback | completed |
| R6 | final evidence and Docara main synchronization | project-context/diff/clean-status checks | completed |

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

### Authorized Resume Closeout

- Status: completed.
- The current-main migration candidates were installed and built with ServBay
  PHP 8.2 plus Node 22/Yarn 1.22. The four locked Docara 1.3 sites received a
  bounded Vite-to-legacy-manifest compatibility bridge; `ui-doc-core` uses its
  native Vite helper and was verified through a disposable consumer fixture.
- Exact GitHub and local `main` revisions are:
  `simai-env@c9c7d88dc9fb787fb67c0d55b294220e440558f1`,
  `publications@599a193c2b01690b68904427ecea6bdbe4a90855`,
  `sf4-doc@b7ab42f5cfe591898c2295e92660c8fa4e204278`,
  `ui-doc-core@e3420d565bd46a0e00337e0d7c61216176aabe16`, and
  `sitepack@197f60ea19a3f02527b38b99e2dba702f3d30e6a`.
- All pushes were fast-forwards. The five remote/local
  `codex/docara-vite-migration` branches and their dedicated worktrees were
  removed only after main integration. Two replayed migrations were proven
  equivalent to their obsolete branches by stable patch-id; the other three
  old commits are ancestors of main.
- Final branch cleanup removed the now-merged publications graph worktree,
  the superseded `simai-env/codex/docs-restructure` branch and the obsolete
  `ui-doc-core/detached` branch. The two non-ancestor tips remain recoverable
  from the verified all-refs bundles. Every active repository in this scope
  now exposes exactly one local/remote branch, `main`.
- Direct exact-main Git scans return no active `laravel-mix-docara` or
  `github:simai/docara-mix` reference. GitHub Contents API independently
  confirms the last stale-search hit, `ui-doc-core`, has a clean package
  manifest and no `webpack.mix.js` on main; the Code Search result still
  naming those deleted paths is an indexing lag, not an active reference.
- `simai/docara-mix` now reports `archived=true`; read-only `git ls-remote`
  still resolves main to `cec3c648e5c1d465a589bbe37388227979c3820d`.
  Its dirty recovery worktree was reversibly moved to
  `/Users/rim/.Trash/docara-mix-retired-20260809T103851+0300` after backup
  verification.
- No force push, history rewrite, tag, package publication or manually
  initiated deployment was performed. Push-triggered existing GitHub Actions
  completed successfully for `simai-env`, `sf4-doc` and `sitepack`.
