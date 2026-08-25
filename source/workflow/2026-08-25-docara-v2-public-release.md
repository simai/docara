# Workflow: Docara 2.0.0 public release

Date: 2026-08-25
Status: completed

## Goal

Publish the current standalone Docara 2 product from the exact public `main`
revision as `v2.0.0`, make the stable package available through Packagist, and
replace candidate-only installation guidance with the verified stable command.

## Explicit User Authorization

The user explicitly authorized preparation and publication of `v2.0.0`, the
GitHub push/tag/release, Packagist availability, and installation-documentation
correction on 2026-08-25.

## Done When

- release documentation and package metadata are consistent with `2.0.0`;
- all repository quality checks pass from a clean clone;
- two independent clean clones produce byte-identical verified archives;
- a fresh consumer can install the exact candidate and complete the required
  init/build/verify smoke;
- the release commit is on `origin/main`;
- `v2.0.0` and its GitHub Release point to the verified release commit;
- Packagist exposes `v2.0.0` and `composer require simai/docara:^2.0` resolves;
- the original dirty worktree remains untouched except for this local workflow
  and action-gate evidence.

## Constraints And Stop Conditions

- Work from a separate clean clone; never stage the existing dirty worktree.
- No force push, history rewrite, deletion of old releases, `docara.test`
  deployment, hosting change, or secret output.
- Stop before tagging on any failed test, archive mismatch, consumer failure,
  stale remote `main`, unavailable GitHub access, or existing `v2.0.0` tag.
- A published tag is immutable; corrections use a later version, never a tag
  rewrite.

## Batch Plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| 1 | Intake, gates, access and release inventory | route, action gate, GitHub/Packagist readback | completed |
| 2 | Release docs and metadata in a clean clone | focused diff, docs/link/package checks | completed |
| 3 | Full QA and deterministic candidate | tests, two archives, clean consumer | completed |
| 4 | Commit and push release revision | remote `main` exact readback and CI | completed |
| 5 | Tag, GitHub Release and Packagist | immutable refs and stable Composer install | completed |

## Current Evidence

- Starting source and remote `main`:
  `d514c536b8cf379b90a15be8aaf14bcb85b06f7e`.
- Public repository: `https://github.com/simai/docara`.
- Packagist currently exposes only legacy stable `v1.3.66`; `^2.0` fails while
  exact `dev-main#d514c536...` resolves.
- Release preflight completed with no blockers. Runtime-naming warnings are
  limited to existing graph schema filenames and are excluded from the package.
- Release commits: `1fa4db8ff28350ea41d97a6f6d62cddff6526a95` and the
  packaged-README link correction
  `d879a69f72e00c8329c74ce7d22d0840860f88c4`.
- Clean-clone QA: Composer validation and Pint passed; PHPUnit passed with
  511 tests, 11,511 assertions and one skipped test; the documentation site
  built 127 source pages and verified 261 HTML pages with 32,965 local
  references and no broken links.
- Two independent clean checkouts produced byte-identical release artifacts
  from `d879a69f72e00c8329c74ce7d22d0840860f88c4`. Archive SHA-256:
  `33d8785360c73d83660bacb2f0a677fb781c6d3160eed201afcdfdf56c60c14a`;
  file count: 997.
- A fresh temporary Composer VCS consumer installed exact `v2.0.0` from the
  release commit, initialized and built a site, and verified 78 HTML pages /
  4,087 references. Update verify/dry-run/apply and rollback
  `20260825093221-670523571534` preserved a green rebuild.
- GitHub `main` was fast-forwarded from the starting revision to
  `d879a69f72e00c8329c74ce7d22d0840860f88c4`. Quality run
  `32832620855` completed successfully.
- Annotated tag `v2.0.0` was pushed; tag object
  `77dcb33c4eba5e33aa651c9700ef1a4b3d1075b0` peels to the exact release
  commit.
- The Access Center API token lacks GitHub Release write scope (HTTP 403).
  An authenticated GitHub UI draft is prepared with title, release notes and
  all three verified artifacts. After explicit action-time confirmation, the
  Release was published through the authenticated UI.
- Public release: `https://github.com/simai/docara/releases/tag/v2.0.0`;
  it is neither draft nor prerelease and exposes the verified ZIP, manifest
  and checksum assets.
- Fresh public downloads of all three GitHub Release assets are byte-identical
  to the locally verified candidate; ZIP SHA-256 remains
  `33d8785360c73d83660bacb2f0a677fb781c6d3160eed201afcdfdf56c60c14a`.
- Packagist exposes `v2.0.0` at exact source revision
  `d879a69f72e00c8329c74ce7d22d0840860f88c4`.
- A new public consumer resolved `composer require simai/docara:^2.0` with
  `--prefer-dist`, initialized a clean project, built 39 source pages and
  verified 78 HTML pages / 4,087 local references with zero broken links.
- Mirai Graph Project Technology was transactionally enabled and verified
  current after the release boundary. The local inventory and migration
  receipt live under `.mirai-graph/project-technology/`; private `source/`
  was not exported. Task-context generation remains blocked by the explicit
  graph gap `no_verifiable_runtime_objects`, so GitHub, Packagist and this
  workflow evidence remain authoritative until graph coverage is designed as
  a separate bounded task.

## Next

No active release task remains. Any deployment or change to `docara.test`
requires a separate explicit request.
