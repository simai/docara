# Batch 9 — local publication and rollback evidence

Date: 2026-07-19
Candidate: `de87bdef224d518d1c707286d4640be0238d34bc`
Tree: `7c0c20678aff65858e29e9be4dd304ddd44ba17b`
Target: `https://docara.test/`
Status: `PASS`

## Acceptance and action gate

Publication began only after independent exact-archive tester, complete-diff
Human-Centered Simplicity/source/docs/security and exact native-Chrome
UX/design gates all returned `PASS` for the same immutable candidate.

Action-gate evidence:

`source/output/action-gates/action-gate-report-20260719183148.json`

The gate returned expected local-live warnings and no blockers. Inventory,
allowed scope, explicit user authorization/change window, backup evidence,
rollback, stop conditions, smoke plan and cleanup boundary were present.

## Exact source and previous served state

Exact accepted source:

`/private/tmp/docara-exact-acceptance-de87bdef.ZmAtIZ/dist-a/docs/site/build_production`

Previous served state was byte-identical to the exact accepted Batch 7 build:

- files: 70;
- HTML pages: 60;
- exact verifier: 6,477 references, zero broken;
- canonical digest:
  `dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`.

Immediately before swap, `/` and `/components/catalog/` returned `200`;
`/development/extensions/` and a unique missing route returned `404`, as
expected for Batch 7.

## Backup, staging and atomic swap

Timestamped retained rollback:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-docs-de87bde-20260719-213128/build_production`

Preserved same-filesystem pre-swap directory:

`/Users/rim/Sites/docara.test/.docara-staging/product-completion-docs-de87bde-20260719-213128/served-before`

Served target:

`/Users/rim/Sites/docara.test/build_production`

All source, served, backup and staging paths used filesystem device
`16777230`.

Before swap:

- rollback copy was byte-identical to served Batch 7;
- exact Batch 7 verifier passed the rollback copy;
- rollback digest reproduced
  `dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`;
- staged Batch 9 was byte-identical to exact source;
- exact Batch 9 verifier passed staging;
- staged digest reproduced
  `502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`.

The served Batch 7 directory was renamed to `served-before`, then the verified
Batch 9 staging directory was renamed to `build_production`. Both operations
were same-filesystem directory renames. No ServBay configuration or reload was
required.

## Served integrity and HTTPS

The served Batch 9 tree is byte-identical to exact accepted source:

- files: 66;
- HTML pages: 56;
- exact static verifier: 5,793 local references, zero broken;
- served canonical digest:
  `502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`.

HTTPS returned `200` for:

- `/`;
- `/start/`;
- `/development/extensions/`;
- `/components/catalog/`;
- `/components/catalog/ui.alert/`;
- `/_docara/component-catalog.json`;
- `/_docara/search-index.json`;
- `/_docara/framework/smart/alert/js/alert.js`.

A unique missing route returned `404`.

The search index contains 55 documents and exactly one
`/development/extensions/` document containing `расширение`. The effective
catalogue contains 17 records: 12 supported and five explicitly unavailable
across `admission_pending`, `deferred` and `framework_gap` lifecycle states.

## Rollback

No integrity or HTTPS failure occurred. If served-browser smoke fails, rollback
remains immediate:

1. move the failed Batch 9 tree aside inside the unique staging directory;
2. rename `served-before` back to `build_production`;
3. run the exact Batch 7 verifier, digest, HTTPS and browser smoke;
4. retain the separate timestamped rollback copy.

The timestamped rollback will remain after final acceptance. Redundant
`served-before` may be removed only after served-browser and post-check gates
pass and the publication evidence is complete.

## Served browser smoke

Fresh browser sessions verified the published HTTPS site:

- desktop viewport: 1440 pixels;
- cold mobile viewport: 390 by 844 pixels;
- root and landing: PASS;
- desktop and mobile four-level menu: deepest active link and three expanded
  ancestors visible;
- exact search `расширение`: one result leading to
  `/development/extensions/`;
- catalogue query: one of 17 results; zero state: zero of 17; reset: 17 of 17;
- generic `ui.alert` detail: PASS;
- light, dark and restored system themes: PASS;
- page-level horizontal overflow: false.

Mobile outline geometry after real served navigation:

- target top: `140.140625px`;
- sticky header bottom: `127.5px`;
- clearance: `+12.640625px`;
- computed scroll margin: `140px`;
- hit test: target `H2`.

Required pages produced zero browser-console errors, zero warnings and zero
unexpected local resource failures. Required assets returned `200`; the
unique missing route returned `404` in both browser and curl checks.

Served-browser verdict SHA-256:
`68699d7699dbdda286dc8864ff3697b5e4771dd582558e794980555ef92d653d`.
Machine-readable checks SHA-256:
`84da1dd2aa1d4e00ac897889c6d0e6dce5eeeba29d82c6274d94fceaaecdfd82`.

Post-check action-gate evidence:

`source/output/action-gates/action-gate-report-20260719184345.json`

It returned `success` with no warnings or blockers. No rollback was required.
The intentionally retained `served-before` directory is a second immediate
local rollback surface; the separate timestamped backup remains the durable
rollback.

This accepts and publishes the complete local Docara product Goal only. It
does not claim public release, production readiness, readiness of all Simai
Framework components, default-branch migration or repository retirement.
