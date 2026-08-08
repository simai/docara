# Docara legacy repositories retirement evidence

Date: 2026-08-09
Verdict: `PARTIAL` — `docara-template` retired; `docara-mix` blocked by active
default-branch consumers.

## Canonical Product Boundary

- Repository: `/Users/rim/Documents/GitHub/docara`.
- Branch: `main`.
- Unchanged product/runtime baseline:
  `c5f6140a85435913a9d5f7389bdf34967d4d70f8`.
- This batch changed no Docara product/runtime/public-site source and performed
  no release, tag, package publication or deployment.

## Verified Rollback Package

External backup root:

`/Users/rim/Git/.artifacts/docara-legacy-retirement-20260809T120000+0300`

### `docara-mix`

- source branch: `codex/docara-mix-recovery`;
- source HEAD: `bf3bedee3d2b7d32d35bcae06c5f8e1691f71d80`;
- fetched `origin/main`:
  `cec3c648e5c1d465a589bbe37388227979c3820d`;
- all-refs bundle SHA-256:
  `15de344660d1e6a79e311f1d33cb645992d0aec06258eb6752a62a33921a36c7`;
- tracked binary patch SHA-256:
  `5535fc1e3fda309f16bb2b2370643529c3d3dc2602ef79f25e222ae716db674e`;
- untracked-source archive SHA-256:
  `5150b22a078849c02bd36ff63ab46d4c561ebf30f6de71f460c031a445649cc4`.

The bundle verifies as complete. A disposable clone checked out the recovery
branch at the exact HEAD and accepted the patch with `git apply --check`. The
untracked archive was extracted and contains exactly:

- `.github/workflows/ci.yml`;
- `lib/docara.js`;
- `package-lock.json`;
- `test/consumer-smoke.sh`;
- `test/docara.test.js`.

### `docara-template`

- source branch: `codex/docara-template-recovery`;
- source/local/remote main HEAD:
  `bf1ac812e57fe80cfc8990ec8b52ad23b90f5616`;
- all-refs bundle SHA-256:
  `ed1842d21d30a26968a848a403457114b17a3185eebffec421fc596a2fd770a7`;
- tracked binary patch SHA-256:
  `f9c65a6e958ab7e02a39edd7a2e26b93800d2ba48dc8ab12b628378330342d0c`;
- untracked-source archive SHA-256:
  `594c3c2ca4f291fb6fc96e017729aeff038fdedcfd52711b86a568f7fbe3142d`.

The bundle verifies as complete. A disposable clone checked out the recovery
branch at the exact HEAD and accepted the patch with `git apply --check`. The
untracked archive contains exactly:

- `.github/workflows/ci.yml`;
- `scripts/verify-template-build.mjs`.

The stale detached worktree registration for
`/private/tmp/docara-template-legacy-build` was pruned before retirement.

## Active Default-branch Scan

All six repositories from the previously accepted direct-consumer inventory
were freshly fetched without changing their working trees. The exact scanned
default revisions were:

| Consumer | Default revision | Active `docara-mix` references |
| --- | --- | --- |
| `simai/simai-env` | `f2d165e7a5fec9588aba36015ef68f7036c65f3a` | package manifests/locks and root+nested webpack entrypoints |
| `zabarov/publications` | `0cf1cffa5369a5b7dc38dce45349028b969f2102` | package manifest/lock and webpack entrypoint |
| `simai/sf4-doc` | `4ec2ad6101c8d7c40d97cc075b76d44f8f2437c6` | package manifest and active frontend documentation |
| `simai/ui-doc-core` | `32e2ac293c39189cc02d209b1d3379441413c111` | package manifest and webpack entrypoint |
| `simai/sitepack` | `245187eac523ee10de1cf717b4364084a0abf3fb` | package manifests/locks and root+nested webpack entrypoints |
| `simai/ui-doc` | `c1550094c205515d705fa4e796c510ae6afe07a2` | none |

GitHub default-branch code search independently returned zero results for
`simai/docara-template` and `docara-template` in the `simai` organization.
It still returned active `laravel-mix-docara`/`docara-mix` references in
`simai/ui-doc-core`; the direct Git scans above expose the additional nested
and personal-repository references that organization code search does not
cover.

## `docara-template` Decision And Retirement

Verdict: `PASS` for retirement.

Reasons:

- no active default-branch consumer references the repository/package;
- current Docara owns first-class scaffolding through its bundled `stubs/` and
  `docara init` contract;
- the external template still documents old Yarn/Mix-era behavior and
  `docara init --update`, while current Docara explicitly disables implicit
  `init --update`;
- retaining the repository would preserve a second hand-maintained starter
  source with no active consumer.

The destructive preflight succeeded on the development-line, env, Git-history,
pre-commit, repository-hygiene, secret and source-policy gates. Naming/release
context produced warnings only because this is retirement rather than a
release; no hard blocker was present.

GitHub API evidence:

- before: `simai/docara-template`, `archived=false`, default `main`;
- after: `simai/docara-template`, `archived=true`, default `main`;
- post-change read verification: `archived=true`;
- read-only `git ls-remote` still resolves `main` to
  `bf1ac812e57fe80cfc8990ec8b52ad23b90f5616`.

The local repository was reversibly moved to:

`/Users/rim/.Trash/docara-template-retired-20260809T120000+0300`

and `/Users/rim/Documents/GitHub/docara-template` no longer exists.

Rollback is: unarchive the GitHub repository, clone the verified bundle, apply
the tracked patch, extract the untracked archive; before Trash is emptied, the
complete original working directory is also directly restorable.

## `docara-mix` Stop Condition

Verdict: `BLOCKED` for remote archival and local retirement.

The required zero-reference condition is false on five active default
branches. The remote repository remains unarchived and the dirty local recovery
worktree remains untouched. No branch, worktree, package or history was removed
from this still-active compatibility contour.

The smallest safe next work is a separate multi-repository migration and
acceptance batch for the five listed consumers. Existing
`codex/docara-vite-migration` branches are evidence/candidates only: they must
be rebased or replayed onto the current default revisions and independently
tested before any default-branch update. Only a new fresh zero-reference scan
can unlock `docara-mix` archival.

## Final Outcome Integrity

- Outcome: partial; one obsolete repository retired, one correctly stopped.
- Integration: GitHub archive state and local removal were independently
  re-read after mutation.
- Evidence freshness: all consumer refs and repository metadata were fetched
  in this batch.
- Simplicity: the obsolete second template source is gone; no compatibility
  dependency was hidden or broken to manufacture a clean result.
