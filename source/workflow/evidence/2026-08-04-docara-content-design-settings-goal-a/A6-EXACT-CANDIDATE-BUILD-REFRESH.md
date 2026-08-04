# A6 — exact-candidate build evidence refresh

Date: 2026-08-04
Status: `PASS`
Exact Goal A product candidate: `8c04160ab50549b060fb933cf80f86193cd92113`
Evidence execution HEAD: `efaf6ea84a1972f74c944a146ae249be1b7437a3`

This is executor evidence for a new independent audit. It does not accept Goal
A and does not authorize Goal B, release, merge, push, tag or deploy. Product,
runtime, public content and package surfaces were not changed by A6.

## Correction boundary

The previous complete-tree ledger
`2e1ecaa1da0d5d0303b65b450d8655e16992377c7f26055f7713a9afad5d9d42`
was produced before final public-guide commit `c6fcd07`. It is retained only as
historical pre-final-doc evidence and is not attributed to the exact product
candidate.

The two refreshed builds contain revision `c6fcd07` in
`_docara/page-metadata.json` for both:

- `content/ru/authoring/regions.md`;
- `content/ru/development/composition-extensions.md`.

## Reproduction

The roots did not exist before the run. From `docs/site`:

```sh
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build goal-a6-exact-a
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build goal-a6-exact-b
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara verify-static build_goal-a6-exact-a
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara verify-static build_goal-a6-exact-b
diff -qr build_goal-a6-exact-a build_goal-a6-exact-b
/Applications/ServBay/package/php/8.4/8.4.20/bin/php ../../docara build goal-a6-exact-a --page=/ru/components/alert/
diff -qr build_goal-a6-exact-a build_goal-a6-exact-b
```

Both full builds reported 104 routes. Each tree contains 307 files and 208 HTML
files. Both static verifications reported 21,844 local references and
`broken=[]`. Both `diff` commands exited 0, including after the selected Alert
rebuild.

## Canonical complete-tree ledger

For each regular file, A6 computed SHA-256 and formed
`<file-sha256>  <relative-path>`. The lines were sorted by relative path,
joined with `\n`, terminated with a final `\n`, and the resulting bytes were
hashed with SHA-256. This is the same algorithm as
`scripts/atomic-static-cutover.php::treeDigest()`.

Full A, full B and full A after the selected Alert rebuild all produced:

`8b7fdb611647e545c6dabe11ed9e31a43a655f36e87739be5fc44dddd6ca25f2`

Alert HTML remained:

`23f4f52e645e61060afd88abd36012c8566540e058923338b12380d0ec328e40`

## Result and rollback

Exact-candidate full/full/single equality and static integrity are PASS. No
runtime defect was found, so the product candidate remains unchanged. Rollback
is limited to reverting the A6 evidence/governance commit; generated build
roots are disposable and are not source of truth.
