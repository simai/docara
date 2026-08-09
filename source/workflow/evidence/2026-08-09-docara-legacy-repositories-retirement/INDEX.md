# Docara legacy repositories retirement evidence

Date: 2026-08-09
Verdict: `PASS` — five consumers migrated to Vite; `docara-template` and
`docara-mix` archived with verified rollback.

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

- Outcome: PASS; both obsolete repositories are archived and absent from the
  active GitHub workspace, while their complete dirty states remain
  recoverable.
- Integration: the exact local and GitHub default revisions are
  `simai-env@c9c7d88dc9fb787fb67c0d55b294220e440558f1`,
  `publications@599a193c2b01690b68904427ecea6bdbe4a90855`,
  `sf4-doc@b7ab42f5cfe591898c2295e92660c8fa4e204278`,
  `ui-doc-core@e3420d565bd46a0e00337e0d7c61216176aabe16`, and
  `sitepack@197f60ea19a3f02527b38b99e2dba702f3d30e6a`.
- Candidate proof: exact Composer locks installed; Docara init and exact
  Node 22/Yarn 1.22 production builds passed. Stable output ledgers were:
  `simai-env` 95 files/77 HTML digest
  `569080176fb21f8bf9124b541c5cdbd75c7a6134d9b4753880eba4a9abb93fe0`;
  `publications` 67/49
  `6b25ceb42c64572974d283b076358987535456d4c50d7f7a5635c5e23dfa423a`;
  `sf4-doc` 159/141
  `a8e1fcb4dd2b0617e0ada7181d080c6b1c11311815dc730f5e754abfcc6b8992`;
  `sitepack` 71/52
  `254f139cbc4603cc023797d2dbdfad423aabbe2eeeecf85940f66df53dcf884f`;
  disposable `ui-doc-core` consumer 61/49
  `ee3d916b23d39f7ee923d5c70555e38e0b060cc8372cf1542f6051660e656699`.
- Runtime note: a generic post-build link probe also exposed existing
  language self-links such as `/en/en` in several locked Docara 1.3 sites;
  asset builds, generated manifests and CI passed. This is not an active
  `docara-mix` dependency and was not hidden as part of retirement.
- GitHub verification: push-triggered current-main Actions succeeded for
  `simai-env`, `sf4-doc` and `sitepack`; `publications` is intentionally
  manual-only and `ui-doc-core` has no current workflow.
- Reference gate: exact local/remote main trees contain zero active package or
  repository references. GitHub Search temporarily returned deleted
  `ui-doc-core` paths; authoritative Contents API returned a clean
  `package.json` and HTTP 404 for `webpack.mix.js`, proving index lag.
- Retirement: `simai/docara-mix` was changed from `archived=false` to
  `archived=true`, then re-read as archived; `git ls-remote` still resolves
  main at `cec3c648e5c1d465a589bbe37388227979c3820d`. The original dirty local
  directory is at
  `/Users/rim/.Trash/docara-mix-retired-20260809T103851+0300`.
- Cleanup: the five obsolete migration branches were deleted locally and
  remotely, their dedicated worktrees were removed, and the generated
  disposable integration roots were retired after evidence capture.
- Safety: the action gate had no hard blocker; no force push, reset, history
  rewrite, tag, package publication or manual deploy was performed.
