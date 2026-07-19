# Batch 5 — local ServBay publication and rollback evidence

Date: 2026-07-19
Candidate: `918919046a2863a67a306678ad225dbda4549666`
Tree: `06e88da243c04eb88e883181c6f3a4ee014f4a62`
Target: `https://docara.test/`
Status: `PASS`

## Acceptance and safety preflight

The immutable successor candidate has independent exact-archive tester,
native-Chrome browser/UX/design and Human-Centered Simplicity/source/security
`PASS` verdicts. The rejected predecessor
`68fba097d6d629ad77937a09a6c16b25ea709850` remains rejected and is not a
publication source.

The publication action-gate report is
`source/output/action-gates/action-gate-report-20260719093442.json`. It
returned expected local live-write warnings and no blocker. The user
explicitly authorised the bounded local publication and native-Chrome
verification in this Goal.

Allowed write scope:

- a unique directory below
  `/Users/rim/Sites/docara.test/.docara-staging`;
- a unique rollback directory below
  `/Users/rim/Sites/docara.test/.docara-backups`;
- `/Users/rim/Sites/docara.test/build_production` through same-filesystem
  atomic renames.

Excluded scope:

- `.env`, credentials and private runtime configuration;
- ServBay configuration;
- databases and remote hosts;
- pushes, merges, tags, releases and default branches;
- public publication and `docara-mix` retirement.

## Inventory, rollback and stop conditions

The source is the already-built static tree from the exact candidate archive:

`/private/tmp/docara-b5-exact-9189190-browser-20260719-1/tree/docs/site/build_production`

The current served tree is retained intact under a unique timestamped backup.
Rollback is the reverse same-filesystem rename: move the failed candidate out
of `build_production`, restore the retained tree, then repeat digest, static,
HTTPS and native-Chrome checks.

No swap is allowed when:

- candidate identity or source path differs from the accepted candidate;
- source or staging static verification fails;
- source and staging path-independent digests differ;
- the expected accepted digest
  `c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060`
  is not reproduced.

Immediate rollback is required on any served digest mismatch, broken local
reference, unexpected HTTP status, browser console/request error, keyboard
failure, responsive overflow, light/dark/no-JavaScript regression, or changed
landing semantics.

## Smoke and cleanup plan

After the swap:

- reproduce the accepted digest from the served tree;
- run the static verifier and require 47 HTML pages, 4931 local references and
  zero broken references;
- require HTTPS `200` for the landing, start, reader-settings, alert component
  and search-index routes, plus `404` for a nonexistent route;
- run native Google Chrome against the served origin for desktop, tablet and
  mobile in light, dark and no-JavaScript modes, including keyboard CTA
  activation, focus visibility and network/console checks;
- verify the served digest once more after browser activity.

The staging container may be removed only after a successful rename. The
timestamped rollback copy remains available. A failed candidate is preserved
under the staging area until the rollback evidence is complete.

## Exact acceptance inputs

The local publication used the successor only after all three independent
exact-candidate gates passed:

- tester:
  `/tmp/docara-b5-9189190-exact-tester/verdict.md`,
  SHA-256
  `6a5333524c51295f2f0b507821099b3e214130b3131a991469cfaaa59844745a`;
- tester checks:
  `/tmp/docara-b5-9189190-exact-tester/checks.json`,
  SHA-256
  `1257a42716d733b2544aa6cd27a6252473ee669a7f1429c891db2c7e25f87fb4`;
- native-Chrome browser/UX/design checks:
  `/tmp/docara-b5-9189190-exact-browser/checks.json`,
  SHA-256
  `50e607a77d2511d126862e2f8f9134cca52d634bf2008212b3e65e316e34f01a`;
- Human-Centered Simplicity/source/security verdict:
  `/tmp/docara-b5-9189190-exact-hcs/verdict.md`,
  SHA-256
  `def3c6693261f0ccde4cc2dd3bcfad22e8897c0319f6522f23489322d9d8f76a`;
- Human-Centered Simplicity/source/security checks:
  `/tmp/docara-b5-9189190-exact-hcs/checks.json`,
  SHA-256
  `19b439eca57bc8f3c53bb2043fe0795a270242050383fc3aa68ca7d6888c71e4`.

The first candidate remains rejected by `B5-HCS-P2-001`; the successor closes
that finding by applying one combined 64-marker preflight before any iterative
directive parse.

## Verified staging and atomic swap

Verified staging tree:

`/Users/rim/Sites/docara.test/.docara-staging/product-completion-landing-9189190-20260719-123722`

Timestamped rollback tree:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-landing-9189190-20260719-123722/build_production`

The source, staging and target are on filesystem device `16777230`. The
accepted source and staging trees were recursively equal before the
same-filesystem rename.

Static verification before and after the swap:

```text
47 HTML pages
4931 local references
0 broken
```

Path-independent digests:

```text
accepted exact source: c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060
verified staging:      c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060
served before:         9cf966409f87a568fbd6a79efc12c6922369dc5ce5fe92adcbdff074e297e67f
rollback copy:         9cf966409f87a568fbd6a79efc12c6922369dc5ce5fe92adcbdff074e297e67f
served after swap:     c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060
served after browser:  c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060
```

The former staging path was consumed by the rename. The accepted Batch 4 tree
remains intact at the rollback path. No rollback was required.

## Served HTTPS and native-Chrome smoke

HTTPS returned `200` for:

- `/`;
- `/landing/`;
- `/start/`;
- `/authoring/reader-settings/`;
- `/components/alert/`;
- `/_docara/search-index.json`.

A dedicated nonexistent route returned `404`.

Native Google Chrome `150.0.7871.128` passed all nine landing scenarios:

```text
390 / 768 / 1440 × light JavaScript / dark JavaScript / light no-JavaScript
```

Results:

- 9 passed, 0 failed;
- horizontal overflow: 0;
- primary CTA target: 44px;
- physical Tab exposes focus-visible and physical Enter opens `/start/`;
- destination HTTP status: 200 in all nine scenarios;
- console problems: 0;
- unexpected request failures: 0;
- HTTP responses with status 400 or greater: 0;
- the search projection is `docara-prefix-v1` with 46 documents;
- all nine served screenshots are byte-identical to the exact-archive
  screenshots and were visually reviewed.

Served-browser artifacts:

- verdict:
  `/tmp/docara-b5-9189190-served-browser/verdict.md`,
  SHA-256
  `7f39df1a05890e150effa5d48de0dae75d296c5853b7c56e1cc26c04d938892d`;
- checks:
  `/tmp/docara-b5-9189190-served-browser/checks.json`,
  SHA-256
  `f7bda6993960803457b55eb0769f99deed69a7b15b84ef189c3cba98049d2162`;
- runner:
  `/tmp/docara-b5-9189190-served-browser/runner.js`,
  SHA-256
  `9c6d194c66de0d914fe2e913a2fc1243b42cafb7ca90aef85762fdf09cf368e3`;
- integrity manifest:
  `/tmp/docara-b5-9189190-served-browser/SHA256SUMS`,
  SHA-256
  `34e436bf9cd3963502243983f5868856844b4dac93c92b67718bef684fca4755`.

This accepts and locally publishes Batch 5 only. It does not claim public
release, production readiness, all-Framework-component readiness, completion
of Milestones 3–5, or completion of the wider Goal.
