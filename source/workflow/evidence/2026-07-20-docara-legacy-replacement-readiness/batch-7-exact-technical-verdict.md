# Batch 7 — independent exact technical verdict

Date: 2026-07-20
Verdict: `PASS`

## Identity

- candidate:
  `2640503ba14913aa83bc3b4343c86966a807e29f`;
- tree:
  `4a0b5a68f613853ba9503f76d48068a1a6ca6724`;
- baseline:
  `a065fd46f941c77d6cde1b45c73578020488e2f0`;
- standard archive SHA-256:
  `fbda45bb8042140e817aafdcb881482765f39740b58b4e697db95e307049080b`;
- canonical build digest:
  `a16d61252837c8d23102e2285a948d7a81c513150080f09b2e9095c31ba475f4`.

## Independent checks

- full suite: 548 tests / 4,474 assertions — `PASS`;
- focused final-candidate suite: 33 tests / 370 assertions — `PASS`;
- two standard archives and two builds were byte-identical;
- output: 77 files, 66 HTML pages;
- static verifier, explicit and default modes: 6,033 local references,
  zero broken;
- 32 supplemental ledger, corpus, redirect, locale/version and immutable
  Framework contract checks — `PASS`;
- Composer/platform requirements, 356 PHP files, generated JavaScript,
  JSON/YAML/JSONL, Pint and `git diff --check` — `PASS`.

The first parallel run in one clone was discarded because temporary PHPUnit
fixtures intersected. Full and focused suites were repeated in separate clean
exact-SHA clones and passed.

## False-green regression probes

Every independent mutation returned exit code 1:

| Mutation | Required marker |
| --- | --- |
| remove top-level `manifest.build` | `@resolved-page-plans` |
| forge root HTML `lang` | `@page-build-identity` |
| forge root `data-docara-documentation-version` | `@page-build-identity` |
| forge root documentation-version meta | `@page-build-identity` |

This closes the earlier verifier defect: a page can no longer claim a locale
or documentation version that differs from the resolved build identity.

## Independent packet

The disposable independent packet was produced under
`/private/tmp/docara-replacement-qa-2640503.VcTCOV/`:

- `checks.json` SHA-256:
  `ed615a25035b4e3766c9868ba85bcffd5c32bb463b1be7e119b2e38a98e0aabb`;
- `supplemental-contracts.json` SHA-256:
  `4ca191166316f4195da4d7a9cce2314f9252c5acb645acb746991404ecd8dd5b`;
- `mutations.json` SHA-256:
  `6b5736900234772898e508a0f01a9ccb1c2f6ca52713ebd3552c56ac4508eeac`.

The verdict changed neither the source worktree nor the local served site.
