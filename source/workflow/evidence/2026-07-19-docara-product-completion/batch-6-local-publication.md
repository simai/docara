# Batch 6 — local ServBay publication and rollback evidence

Date: 2026-07-19
Candidate: `68a960ff1debde48664aa8541413dbef208612ee`
Tree: `9363f21c63e516ee4b97772f097b70aa52ff412f`
Target: `https://docara.test/`
Status: `PASS`

## Safety boundary

Publication started only after exact tester, complete-diff Human-Centered
Simplicity/source/security and native-Chrome UX/design verdicts returned
`PASS`.

The action-gate report is:

`source/output/action-gates/action-gate-report-20260719122452.json`

It returned expected local live-write warnings and no blocking finding. The
write scope was limited to unique staging and backup directories plus the
same-filesystem `build_production` rename. ServBay configuration, `.env`,
credentials, databases, remote hosts, releases, branches and Framework owner
repositories were excluded.

## Exact source, backup and rollback

Exact accepted source:

`/private/tmp/docara-b6-browser-68a960f-20260719/docs/site/build_production`

Timestamped rollback copy:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-catalog-contract-68a960f-20260719-152452/build_production`

Served target:

`/Users/rim/Sites/docara.test/build_production`

Source, staging and target are on filesystem device `16777230`. The verified
staging directory was consumed by the atomic rename. Rollback is the reverse
same-filesystem rename followed by static, HTTPS and browser verification.

## Integrity and static verification

Path-independent digests:

```text
accepted exact source: 16bbdd52e2dc0e0c058c02dfbc61e3dd824fa2e59be23b45848f587d83a3fc50
verified staging:      16bbdd52e2dc0e0c058c02dfbc61e3dd824fa2e59be23b45848f587d83a3fc50
served before:         c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060
rollback copy:         c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060
served after swap:     16bbdd52e2dc0e0c058c02dfbc61e3dd824fa2e59be23b45848f587d83a3fc50
served after browser:  16bbdd52e2dc0e0c058c02dfbc61e3dd824fa2e59be23b45848f587d83a3fc50
```

The served verifier checked 47 HTML pages and 4,943 local references with zero
broken references.

HTTPS returned `200` for:

- `/`;
- `/start/`;
- `/components/button/`;
- `/authoring/reader-settings/`;
- `/_docara/component-catalog.json`;
- `/_docara/search-index.json`.

A dedicated nonexistent route returned `404`. The served component catalogue
contains 17 records, 11 supported records and content hash
`02d716492fffb02e801f2cabe0b2a2c763ffd6e8685c6b1489a6b5db03194d98`.
All four readiness/release nonclaims remain false.

## Served native-Chrome smoke

The served origin reproduced the exact candidate behavior:

- search query `настройки чтения`: six results;
- desktop width: document 1,426px and viewport content 1,426px;
- dark theme: `html.theme-dark`, body `rgb(26, 27, 31)` and
  `sf-theme=dark`;
- cold mobile width: document 378px and viewport content 378px;
- active links: Button in desktop and mobile navigation;
- Components ancestor expanded on mobile;
- first Tab selected the skip link and Enter focused `MAIN#docara-main`;
- browser console errors: zero.

Served mobile screenshot:

`/private/tmp/docara-b6-browser-68a960f-20260719/.playwright-cli/page-2026-07-19T12-29-24-960Z.png`

Size: 390 × 844. SHA-256:
`1e3215339528ccf2ba9a964ad5a85a6fef66bd291edaa5e1afaf138ac96db418`.

No rollback was required. The accepted Batch 5 tree remains available at the
timestamped rollback path.

This accepts and locally publishes Batch 6 only. It does not claim a public
release, production readiness, readiness of all Simai Framework components,
completion of Batch 7 or completion of the wider Goal.
