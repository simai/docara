# Docara.test documentation refresh

Date: 2026-07-20
Status: completed_pass
Target: `https://docara.test/`

## Objective

Publish the already-built documentation update from
`docs/site/build_production` to the local ServBay stand without changing
ServBay configuration, public releases, branches or tags.

## Boundary

- source build:
  `docs/site/build_production`;
- served build:
  `/Users/rim/Sites/docara.test/build_production`;
- only timestamped children of `.docara-backups` and `.docara-staging` may be
  created outside the served directory;
- no `.env`, credentials, database, package release or public deployment;
- no ServBay restart or reload.

## Preflight

- federation route owner: `docara`;
- companion gates: `ops`, `tester`;
- action gate: `success`;
- current served tree and source build have different canonical digests;
- source documentation contains the accepted immutable Composer reference
  `2640503ba14913aa83bc3b4343c86966a807e29f`;
- the generic Docara doctor reports the new portable site layout as a
  non-legacy project and confirms that `build_production` exists.

## Publication Plan

1. Verify the source static build and compute its canonical tree digest.
2. Copy the current served tree to a timestamped independent backup and verify
   byte equality.
3. Copy the source build to a timestamped staging tree and verify byte
   equality and static integrity.
4. Confirm all publication paths are on the same filesystem.
5. Rename current `build_production` to `served-before`, then atomically rename
   the verified staging tree to `build_production`.
6. Verify served digest, required HTTPS routes, updated documentation marker,
   assets and a unique 404 route.
7. On any failure, move the failed tree aside and rename `served-before` back
   to `build_production`, then repeat baseline smoke checks.

## Verification Summary

- publication batch:
  `documentation-refresh-41c44c9-20260720-135200`;
- served output:
  `/Users/rim/Sites/docara.test/build_production`;
- independent backup:
  `/Users/rim/Sites/docara.test/.docara-backups/documentation-refresh-41c44c9-20260720-135200/build_production`;
- same-filesystem instant rollback:
  `/Users/rim/Sites/docara.test/.docara-staging/documentation-refresh-41c44c9-20260720-135200/served-before`;
- previous relative-tree digest:
  `7d1a2e6c384925f81022c7b30c9a255744d107ed0f41ace78b01658fc163b834`;
- source and served relative-tree digest:
  `ce2d4d0de86e334ab627f4a9bfbbc9fa7af258456d0f406ada803a6631978ef7`;
- all publication paths use filesystem device `16777230`;
- static verifier: 66 HTML pages, 6,036 local references, zero broken;
- HTTPS: required routes return `200`, unique missing route returns `404`;
- updated immutable Composer reference is present in the served
  troubleshooting page;
- browser smoke: root and troubleshooting pages render with `lang=ru`, the
  expected H1 and navigation, no horizontal page overflow, and no browser
  console warning or error;
- ServBay configuration and processes were not changed.

## Kaizen Review

The first publication attempt stopped before the atomic swap because a
diagnostic tree digest included absolute path prefixes. The served site
remained unchanged. The procedure was corrected to hash sorted relative paths
inside each tree; byte equality, static verification and relative-tree digests
then agreed before the swap.
