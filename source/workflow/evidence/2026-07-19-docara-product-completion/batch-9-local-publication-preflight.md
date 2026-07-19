# Batch 9 — local publication preflight

Date: 2026-07-19
Candidate: `de87bdef224d518d1c707286d4640be0238d34bc`
Tree: `7c0c20678aff65858e29e9be4dd304ddd44ba17b`
Target: `https://docara.test/`
Status: `COMPLETED_PASS`

## Acceptance boundary

Independent exact-archive tester, complete-diff Human-Centered
Simplicity/source/docs/security and matching native-Chrome UX/design gates
returned `PASS` for the same candidate. Publication remains stopped only until
the explicit publication action gate allows the local served-site write.

Public release, push, merge, tag, default-branch migration, Framework-owner
writes, repository retirement, ServBay configuration, credentials and
databases remain excluded.

## Current served tree

Current served path:

`/Users/rim/Sites/docara.test/build_production`

It contains 70 files and 60 HTML pages and is byte-identical to the exact
accepted Batch 7 tree:

`/private/tmp/docara-b7-exact-a5cc0e7/tree/docs/site/build_production`

The exact Batch 7 verifier passed the served tree with 6,477 local references
and zero broken references. Its canonical digest remains:

`dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`.

HTTPS returns `200` for `/` and `/components/catalog/`; the new
`/development/extensions/` route correctly remains `404` before publication,
as does a unique missing route.

## Exact accepted source

Planned source:

`/private/tmp/docara-exact-acceptance-de87bdef.ZmAtIZ/dist-a/docs/site/build_production`

It is one of two byte-identical clean builds produced only from exact Git
archives:

- 66 files;
- 56 HTML pages;
- 5,793 checked local references;
- zero broken references;
- canonical digest
  `502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`;
- canonical manifest SHA-256
  `0e0bb352d6bad9c7b26445585470e3f4dce6318afbf965179b3ffa54749c8826`.

## Allowed write scope

Only these local paths may change after all three exact acceptance gates and
the publication action gate pass:

- one unique child of `/Users/rim/Sites/docara.test/.docara-staging/`;
- one timestamped child of
  `/Users/rim/Sites/docara.test/.docara-backups/`;
- `/Users/rim/Sites/docara.test/build_production` through same-filesystem
  directory renames.

## Backup, staging and rollback

1. Recheck that the current served tree is still byte-identical to the exact
   Batch 7 build and passes its exact verifier.
2. Copy it to a unique timestamped rollback directory, compare byte-for-byte,
   run the exact Batch 7 verifier and record its digest.
3. Copy the exact Batch 9 build to a unique staging directory, compare it
   byte-for-byte with source, run the exact Batch 9 verifier and record its
   digest.
4. Confirm served, backup and staging paths use one filesystem.
5. Atomically rename the served Batch 7 directory to a preserved pre-swap
   staging path and the verified Batch 9 staging directory to
   `build_production`.
6. Verify the served Batch 9 tree, digest, HTTPS routes and browser behavior.

If any post-swap check fails, move the failed tree aside and rename the
preserved Batch 7 pre-swap directory back to `build_production`, then repeat
its exact verifier, HTTPS and browser smoke. A separate timestamped rollback
copy remains retained regardless of outcome.

## Stop conditions

Stop without replacing the served tree if:

- any exact acceptance verdict is not `PASS`;
- candidate SHA, tree or exact source digest changes;
- the current served tree drifts from exact Batch 7;
- paths do not support the planned same-filesystem rename;
- backup or staging equality, verifier or digest checks fail;
- the publication action gate blocks the write.

After swap, any failed exact verifier, digest, required HTTPS route, unique
missing-route status, browser interaction, viewport, console or network check
triggers immediate rollback.

## Served smoke plan

- exact Batch 9 static verifier against the served tree;
- source, staging and served digests and byte comparisons;
- HTTPS `200` for `/`, `/start/`, `/development/extensions/`,
  `/components/catalog/`, `/components/catalog/ui.alert/`,
  `/_docara/component-catalog.json`, `/_docara/search-index.json` and
  `/_docara/framework/smart/alert/js/alert.js`;
- one unique missing route returns `404`;
- search query `расширение`;
- catalogue query, zero state and reset;
- mobile four-level menu, active trail and outline anchor;
- light, dark and system themes;
- no page overflow, unexpected failed resources or browser console errors.
