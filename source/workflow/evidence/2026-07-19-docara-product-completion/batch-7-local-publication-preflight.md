# Batch 7 — local publication preflight

Date: 2026-07-19
Candidate: `a5cc0e7ddd3a4ef218381e3e4129825eedf6d671`
Target: `https://docara.test/`
Status: `COMPLETED_PASS`

Execution and final smoke evidence are recorded in
`batch-7-local-publication.md`.

## Inventory

Current served tree:

`/Users/rim/Sites/docara.test/build_production`

The current tree contains 55 files and 47 HTML pages. A fresh exact-archive
build of accepted Batch 6 candidate
`68a960ff1debde48664aa8541413dbef208612ee` is byte-identical to the current
tree under `diff -qr`. The Batch 6 verifier checks 4,943 local references with
zero broken references. The current canonical path-independent digest is:

`c22a0867fe32fb0d8ade57955085f0e592eab07cd9da45c29e32308f712c4c11`.

The previously written Batch 6 evidence digest does not reproduce under the
canonical algorithm used for Batch 7. File-by-file equality with a fresh exact
Batch 6 archive and the exact-version verifier are the authoritative drift
checks; no served-file drift was found.

Candidate source:

`/private/tmp/docara-b7-exact-a5cc0e7/tree/docs/site/build_production`

It contains 70 files and 60 HTML pages. Its exact digest is:

`dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`.

## Allowed write scope

Only these local paths may change after every acceptance gate passes:

- one unique child of `/Users/rim/Sites/docara.test/.docara-staging/`;
- one timestamped child of
  `/Users/rim/Sites/docara.test/.docara-backups/`;
- `/Users/rim/Sites/docara.test/build_production` through same-filesystem
  directory renames.

ServBay configuration, `.env`, credentials, databases, source code, vendor
trees, remote hosts, branches, tags, releases and Framework owner repositories
are excluded.

## Backup and rollback

Before replacement:

1. copy the current served tree to a unique timestamped backup;
2. compare the backup byte-for-byte with the current served tree;
3. verify the backup with the exact Batch 6 verifier;
4. record its canonical digest;
5. copy the exact Batch 7 build to a unique staging directory;
6. verify staging with the exact Batch 7 verifier and compare its digest with
   the exact source.

The swap is a same-filesystem rename. Rollback moves a failed served candidate
aside, renames the preserved backup to `build_production`, then repeats exact
static, HTTPS and browser checks. A separate retained copy remains in the
timestamped backup directory.

## Change window and authorization

This is the user-authorized local test site and the active Goal explicitly
requires publication of accepted stages to `docara.test`. Execution is allowed
only after independent exact tester, complete-diff HCS/source/security and
native-Chrome UX/design gates all return `PASS`, followed by the explicit
publication action gate.

## Stop conditions

Stop without replacing the served tree if:

- any exact acceptance verdict is not `PASS`;
- candidate SHA, tree or exact build digest changes;
- the current served tree changes after this inventory;
- source, staging and served filesystems cannot support the planned atomic
  rename;
- backup equality or exact Batch 6 verification fails;
- staging verification, digest comparison or file-containment checks fail;
- a required HTTPS route, asset, search receipt or browser smoke fails.

If a post-swap check fails, roll back immediately and record both the failure
and restored-site verification.

## Smoke plan

After the swap verify:

- packaged `verify-static` on the served tree;
- served digest equals exact source and staging;
- HTTPS `200` for `/`, `/start/`, `/components/catalog/`,
  `/components/catalog/ui.alert/`, `/_docara/component-catalog.json`,
  `/_docara/search-index.json` and one Framework asset;
- a unique nonexistent route returns `404`;
- catalogue index has 17 records, 12 supported details and five unavailable
  disclosures;
- desktop and cold-mobile page widths do not overflow;
- search, mobile disclosure, light/dark/system theme and a live Smart example
  still work;
- Chrome console errors remain zero.

## Cleanup

Failed staging trees are moved to a named evidence location or removed only
after the failure record is complete. The timestamped rollback remains
retained. Disposable exact-archive and preview-server trees may be removed
after closure because their candidate SHA, tree, digest and commands are
durably recorded.
