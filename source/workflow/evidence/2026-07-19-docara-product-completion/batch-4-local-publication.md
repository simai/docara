# Batch 4 — local ServBay publication and rollback evidence

Date: 2026-07-19
Candidate: `d26fa66c6d6a5a36ec113288e6fce29f2f6b1a0e`
Tree: `aa20ad5d0d95f82149a30d189dd5fa9d78163d4a`
Target: `https://docara.test/`
Status: `PASS`

## Acceptance and safety gate

Before publication the same immutable candidate had bounded exact
Human-Centered Simplicity, independent non-browser tester and native-Chrome
browser/UX/visual `PASS` evidence.

The action-gate report is
`source/output/action-gates/action-gate-report-20260719064312.json`. It returned
expected local live-write warnings and no blocker. Allowed writes were limited
to unique staging/backup containers and
`/Users/rim/Sites/docara.test/build_production`. `.env`, ServBay
configuration, remote hosts, releases, tags, pushes, merges and default
branches were excluded.

Stop conditions required no swap on source/staging mismatch and immediate
rollback on any served digest, static, HTTPS, browser, focus, modal,
responsive, theme or storage failure.

## Staging and atomic swap

Exact accepted source:

`/private/tmp/docara-b4-d26fa66-browser-acceptance/build_snapshot`

Verified staging container:

`/Users/rim/Sites/docara.test/.docara-staging/product-completion-settings-d26fa66-20260719-094411`

Timestamped rollback copy:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-settings-d26fa66-20260719-094411/build_production`

Source and staging were verified before the same-filesystem rename:

```text
44 HTML pages
4535 local references
0 broken
```

Digests:

```text
source:         9cf966409f87a568fbd6a79efc12c6922369dc5ce5fe92adcbdff074e297e67f
staging:        9cf966409f87a568fbd6a79efc12c6922369dc5ce5fe92adcbdff074e297e67f
served before:  826c8a0dac97bc1a17f7b5926d05908d14424fa410631a35f6e374403656654b
served after:   9cf966409f87a568fbd6a79efc12c6922369dc5ce5fe92adcbdff074e297e67f
rollback copy:  826c8a0dac97bc1a17f7b5926d05908d14424fa410631a35f6e374403656654b
```

The served tree and exact source are recursively equal. The preserved Batch 3
rollback copy still verifies as 43 HTML pages, 4339 local references and zero
broken references. Rollback is the reverse same-filesystem rename followed by
static, digest, HTTPS and browser verification. No rollback was required.

## Served HTTPS and native-Chrome smoke

HTTPS returned `200` for:

- `/`;
- `/authoring/reader-settings/`;
- `/authoring/reading-context/`;
- `/_docara/search-index.json`.

The served runner strengthened the exact matrix with HTTP-error response
tracking and normal-storage reload persistence. Native Google Chrome
`150.0.7871.128` passed all 16 scenarios:

```text
normal/disabled storage × 1440/390 × light/dark × Meta/Control+K
```

Results:

- 16 passed, 0 failed;
- console problems: 0;
- failed requests: 0;
- HTTP responses with status 400 or greater: 0;
- normal light/dark choices persist after reload with `source=reader`;
- disabled storage remains in memory, writes no local value and returns to
  `preference=system`, `source=site` after reload;
- physical Enter, Escape, `Meta+K` and `Control+K`, focus return,
  focus-visible, modal exclusion and responsive geometry passed;
- served/source and served before/after digests remained identical.

Raw artifacts:

- runner: `/tmp/docara-b4-d26fa66-served-acceptance.js`, SHA-256
  `bd5aa82a37a850ebd7d1b1773124c81be01b0b2db5f69d5d05f8bd894b748e8f`;
- checks: `/tmp/docara-b4-d26fa66-served-acceptance/checks.json`, SHA-256
  `de6db6953cbd637be348fedc2800f55fbb54471a06ff6e064f0e22067c777950`;
- time: `2026-07-19T06:46:59.346Z` to
  `2026-07-19T06:48:00.793Z`;
- 16 screenshots:
  `/tmp/docara-b4-d26fa66-served-acceptance/`.

An independent read-only audit returned `PASS` for this bounded local
publication and reconfirmed exact source/served equality, scenario uniqueness,
reload behavior, timestamps, embedded runner hash and zero browser/network/HTTP
findings.

This closes Batch 4 and Milestone 2 only. It is not a public release,
production-readiness, ecosystem-readiness or wider-Goal completion claim.
