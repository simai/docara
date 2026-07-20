# Batch 8 — local publication preflight

Date: 2026-07-20
Candidate: `2640503ba14913aa83bc3b4343c86966a807e29f`
Tree: `4a0b5a68f613853ba9503f76d48068a1a6ca6724`
Target: `https://docara.test/`
Status: `COMPLETED_PASS`

## Boundary

Only the exact candidate may be published, and only after technical and
Human-Centered Simplicity gates return `PASS`, while browser/UX/design returns
`PASS` or `PASS_WITH_NOTES` with no blocker for the same SHA. Those gates are
now satisfied. This is a local ServBay publication. Public release, push,
merge, tag, default-branch migration, repository retirement, Framework-owner
writes, ServBay configuration, credentials and databases are excluded.

## Exact candidate source

Two standard `git archive` exports are byte-identical:

- archive SHA-256:
  `fbda45bb8042140e817aafdcb881482765f39740b58b4e697db95e307049080b`;
- exact build A:
  `/private/tmp/docara-exact-2640503.oCHReP/dist-a/docs/site/build_production`;
- exact build B:
  `/private/tmp/docara-exact-2640503.oCHReP/dist-b/docs/site/build_production`;
- both builds contain 77 files and 66 HTML pages;
- both exact verifiers checked 6,033 local references with zero broken;
- both builds have canonical digest
  `a16d61252837c8d23102e2285a948d7a81c513150080f09b2e9095c31ba475f4`.

The two build trees are byte-identical. The candidate source stays immutable
for the whole acceptance and publication batch.

## Current served state

Current path:

`/Users/rim/Sites/docara.test/build_production`

The preflight rechecked it against the previous exact accepted build:

- byte-for-byte comparison: `PASS`;
- files: 66;
- HTML pages: 56;
- previous exact verifier: 5,793 references, zero broken;
- canonical digest:
  `502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`;
- HTTPS `/` and `/start/`: `200`;
- new `/authoring/redirects/` and `/components/code/`: `404`, as expected
  before publication;
- a unique missing route: `404`.

## Allowed write scope

After the three exact-candidate gates and the action gate pass, writes are
restricted to:

- one unique timestamped child of
  `/Users/rim/Sites/docara.test/.docara-staging/`;
- one unique timestamped child of
  `/Users/rim/Sites/docara.test/.docara-backups/`;
- `/Users/rim/Sites/docara.test/build_production`, only through
  same-filesystem directory renames.

## Backup, staging and atomic swap

1. Recheck the served tree against the previous exact build and verifier.
2. Copy it to a timestamped rollback directory, compare byte-for-byte, run
   the previous exact verifier and reproduce its digest.
3. Copy exact candidate build A to a timestamped staging directory, compare
   byte-for-byte, run the candidate exact verifier and reproduce its digest.
4. Confirm served, backup and staging paths are on one filesystem.
5. Rename the current served directory to `served-before`, then rename the
   verified staging tree to `build_production`.
6. Re-run candidate verifier, digest, HTTPS and browser smoke against the
   served site.

No ServBay reload is planned: its document root already points at the stable
`build_production` path.

## Rollback

If any post-swap check fails:

1. move the failed candidate tree aside inside the same staging batch;
2. rename `served-before` back to `build_production`;
3. rerun the previous exact verifier and digest;
4. rerun HTTPS and browser smoke;
5. retain the separate timestamped rollback copy.

## Stop conditions

Stop without changing the served tree if:

- any exact-candidate gate is not `PASS`;
- candidate SHA, tree, archive or build digest changes;
- current served state drifts from the previous exact accepted build;
- backup or staging equality, verifier or digest fails;
- paths do not support same-filesystem rename;
- the local publication action gate blocks the write.

After swap, any verifier, digest, required route, browser interaction,
viewport, console or resource failure triggers immediate rollback.

## Completion

The action gate returned `success` with no blocker or warning. Publication
batch `replacement-ready-docs-2640503-20260720-032656` completed exactly as
planned. The served tree reproduces candidate digest
`a16d61252837c8d23102e2285a948d7a81c513150080f09b2e9095c31ba475f4`;
the independent backup and `served-before` reproduce previous digest
`502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`.
Static, HTTPS and native-Chrome served checks passed. Detailed evidence is in
`batch-8-local-publication.md`.

## Served smoke

- candidate static verifier and canonical digest;
- byte equality with exact build A;
- HTTPS `200` for `/`, `/start/`, the four-level page,
  `/authoring/redirects/`, `/components/catalog/`,
  `/components/catalog/native.code/`, `/landing/`,
  `/_docara/component-catalog.json` and `/_docara/search-index.json`;
- one unique missing route returns `404`;
- `/components/code/` exposes canonical/noindex/meta refresh/visible link and
  reaches `/components/catalog/native.code/`;
- search query `перенаправления`;
- catalog query, empty state and reset;
- desktop active path and right outline;
- mobile menu and outline with no article movement, focus containment,
  Escape and focus restoration;
- reader settings focus trap and light/dark/system persistence;
- exact code clipboard payload with permission-enabled browser context;
- no page overflow, unexpected resource failure or browser console error.
