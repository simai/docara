# Batch 4 — served smoke failure and rollback

Date: 2026-07-19
Candidate: `adad417a9ea6cad98bc79650710a4d4e732f8cac`
Tree: `cb2939b33cab30ccfc5e7b0baddb933425fc4d68`
Status: `CORRECTION_REQUIRED`; Batch 4 is not accepted

## Publication identity

The exact candidate build passed the first bounded tester, HCS and browser
attempts and was staged for local publication. Source, staging and the briefly
served tree had the same path-independent digest:

`8726cbb745247cc09d2eaa4f179ca84b05187e083432d2fc93de0669ddfb3cbf`

The build contained 44 HTML pages, 4535 local references and zero broken local
references. The publication preflight was recorded in
`source/output/action-gates/action-gate-report-20260719034812.json`.

## Blocking served-site finding

The post-swap smoke used a real document-level keyboard shortcut rather than a
programmatic click. With reader settings open, physical `Cmd+K` (and the same
`Ctrl+K` handler) called `openSearch()` directly. Only the search trigger click
listener closed reader settings, so both native dialogs remained open:

- reader settings: `open=true`;
- search: `open=true`.

This is finding `SP-001` (P1): the documented keyboard path violated modal and
focus ownership. The root cause is an invariant enforced at one trigger instead
of the shared search-opening boundary.

Raw served-smoke evidence:

- `/tmp/docara-b4-served-publication-smoke/verdict.md`, SHA-256
  `91549ed17db8c0158b599d63a42b06ddfc32d9ecceefeef462333df15e6b1ad0`;
- `/tmp/docara-b4-served-publication-smoke/checks.json`, SHA-256
  `b6b800b0089d71604d2f009672baf998d28cd176344f1ecf0e29845f66720242`.

All other served checks passed, including exact served bytes, normal and
disabled native local storage, 25/25 icons on the reader page, light/dark theme
behavior, volatile fallback honesty, 390 px layout and zero browser/runtime
errors.

## Rollback

Publication stopped immediately. The accepted Batch 3 tree was atomically
restored to `/Users/rim/Sites/docara.test/build_production` and reverified:

- accepted digest:
  `826c8a0dac97bc1a17f7b5926d05908d14424fa410631a35f6e374403656654b`;
- 43 HTML pages, 4339 local references, zero broken references;
- `https://docara.test/`: HTTP 200.

The failed candidate build remains available for diagnosis at:

`/Users/rim/Sites/docara.test/.docara-staging/product-completion-settings-20260719-064831/failed-build_production`

The timestamped accepted-tree backup was consumed by the atomic restore; its
container remains at:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-settings-20260719-064831`

## Correction contract

The correction is test-first and deliberately narrow:

1. assert that the shared search runtime discovers the reader-settings dialog;
2. require it to close settings before `dialog.showModal()`;
3. exercise physical `Cmd+K` and `Ctrl+K`, mutual exclusion, Escape and focus
   restoration in normal and disabled-storage Chrome;
4. create a new immutable candidate and repeat exact acceptance;
5. publish only after the same physical-key served smoke passes.

The focused regression was red before implementation and green after the
shared `openSearch()` boundary was corrected. The full current-worktree suite
then passed: 465 tests, 2385 assertions. No public release, production,
ecosystem or wider-Goal readiness is claimed.
