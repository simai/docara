# R1-C.5 corrected exact candidate and lifecycle

Status: `implementation_pass_pending_independent_retest`

Exact source revision: `56a2abf8bad05923f689141afc0bb045aa4d6734`

## Deterministic package

Parameters are local dry-run facts, not a published version or tag:
`2.0.0-rc.2` and `v2.0.0-rc.2`.

Two independent `--no-local` clean clones built and verified the same 650-file
ZIP:

- ZIP SHA-256: `04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753`;
- external manifest SHA-256:
  `d709d27cc226a3833c05ca62271525dfe48042d967940eaa9e8b9ac6a7185669`;
- checksum file SHA-256:
  `4cea80db782ae402bdd3c36b9f799afe548dd2fd46fe35c594900141058fdb9b`.

ZIP, manifest and checksums compare byte-identical. The artifact verifier reads
the ZIP, validates its manifest, paths, modes, timestamps and hashes, and now
also resolves every packaged README/non-site documentation link. Broken links
are zero. Archive inventory has no `resources/language-packs`, `languages/`,
language-pack schema or LanguagePack runtime class, and public schema/starter
contain no `language_pack` field.

## Fresh consumers and public site

Two fresh Composer package-repository dist consumers install exact reference
`56a2abf…` without package `.git`. Both pass init, `update --verify`, full build
and static verification. The initialized starter has 38 routes, 166 output
files, 76 HTML documents, 3,742 local references and zero broken references.

The package's public documentation site produces two byte-identical 103-route,
305-file full builds. Each static verifier checks 206 HTML documents and 21,437
local references with zero broken. Rebuilding every route through `--page` in
the same accepted output preserves its exact HTML: 103/103, failures 0.

Consumer lock hashes differ because each evidence consumer intentionally names
a different local byte-identical dist URL. The installed Docara version and
dist reference are identical; this evidence does not mislabel path-bearing
consumer locks as byte-identical.

## R1 predecessor to R1-C update

The historical R1 source reproducibly rebuilt its immutable 655-file ZIP at
the recorded SHA-256 `83afd355…`. A consumer initialized from it had 28
engine-owned files and engine state `43181f4f…`.

The corrected package then produced hash-bound plan `0916bf069e…` with six
operations: five engine-owned manifest/schema updates and deletion of only
`.docara/engine/schemas/language-pack.schema.json`. Apply produced 27 files and
state `a45bebff…`; verify reported `current`. Rollback restored the exact old
28-file state `43181f4f…`; verify again reported `update_available`.

Hashes of user-modified Markdown, asset, `docara.json`, `section.json`, page
JSON and `content/ru/lang.json` match before apply, after apply and after
rollback. Focused/full tests cover dirty engine state, unknown ownership, stale
plan, corrupt rollback, symlink/path escape, wrong revision, moving dependency
and interrupted apply fail-closed behavior.

## Reproduction

```text
php scripts/build-release-package.php \
  --revision=56a2abf8bad05923f689141afc0bb045aa4d6734 \
  --version=2.0.0-rc.2 --tag=v2.0.0-rc.2 --output=<empty-output>
php scripts/verify-release-package.php \
  <empty-output>/docara-2.0.0-rc.2.release-manifest.json
```

Use two clean clones and compare ZIP, manifest and checksum files. The exact
commands and machine counts are summarized in `release-candidate.json`.

This is executor-owned implementation evidence. It does not pass the
tester-owned local release-readiness gate and does not authorize publication.
