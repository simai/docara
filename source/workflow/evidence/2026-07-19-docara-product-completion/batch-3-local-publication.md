# Batch 3 — local publication and rollback evidence

Date: 2026-07-19
Accepted candidate: `73eae43b9e8f715c0dc978390f4e60a1011465c9`
Candidate tree: `0f1aa5544dabf9631550a349caa9641f04384bd3`
Target: `https://docara.test/`
Status: `PASS`

## Acceptance and safety gate

The same immutable candidate received independent exact tester, complete-diff
HCS and exact browser/UX/design `PASS` verdicts before publication.

The action gate report is
`source/output/action-gates/action-gate-report-20260719011832.json`. It returned
warnings but no blockers. The bounded local write had explicit source,
staging, backup, rollback, digest, HTTPS, browser and stop conditions. No
credential, `.env`, ServBay configuration, remote deployment, release, tag,
push, merge or default-branch operation was needed or performed.

Allowed writes were limited to a unique staging directory, a new timestamped
backup and `/Users/rim/Sites/docara.test/build_production`.

## Atomic publication result

Exact accepted source:
`/private/tmp/docara-batch3-exact-73eae43b.Xrof6T/docs/site/build_production`.

The source and staging verifier each returned:

```text
43 HTML pages
4339 local references
0 broken
```

Digests:

```text
source:        826c8a0dac97bc1a17f7b5926d05908d14424fa410631a35f6e374403656654b
staging:       826c8a0dac97bc1a17f7b5926d05908d14424fa410631a35f6e374403656654b
served before: 81945efbe610a54986586a2a61f16e64556528e235596fb016330923f450fd1f
served after:  826c8a0dac97bc1a17f7b5926d05908d14424fa410631a35f6e374403656654b
```

The previous accepted Batch 2 tree was moved intact to:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-reading-20260719-041915/build_production`

Its preserved digest remains:

`81945efbe610a54986586a2a61f16e64556528e235596fb016330923f450fd1f`.

Rollback is the reverse same-filesystem rename followed by static, digest,
HTTPS and browser verification. No rollback was required.

## Served smoke

HTTPS returned `200` for:

- `/`;
- `/authoring/reading-context/`;
- `/authoring/layout-and-navigation/hierarchy/four-level/`;
- `/_docara/search-index.json`.

Live browser at 390 × 844 confirmed five breadcrumbs, zero generated
ellipsis, the full active trail, zero overflow and zero console messages.
Regression smoke opened search with `Cmd+K`; query `наследование` returned five
results, first `/authoring/inheritance/`, with zero overflow and console
errors.

This local publication closes only Batch 3. It is not a public release,
production-readiness claim or completion of the wider Goal.
