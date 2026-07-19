# Batch 7 — local publication and rollback evidence

Date: 2026-07-19
Candidate: `a5cc0e7ddd3a4ef218381e3e4129825eedf6d671`
Tree: `ec09ea5249a43c712729cbb74ab03e736987a353`
Target: `https://docara.test/`
Status: `PASS`

## Safety boundary

Publication began only after independent exact tester, complete-diff
Human-Centered Simplicity/source/security and native-Chrome UX/design verdicts
all returned `PASS`.

Action-gate evidence:

`source/output/action-gates/action-gate-report-20260719152702.json`

It returned expected warnings for the local served-site write and no blocking
finding. Scope was limited to unique staging and backup children plus the
same-filesystem `build_production` rename. ServBay configuration, `.env`,
credentials, databases, source, vendor, remote hosts, branches, tags, releases
and Framework owner repositories were excluded.

## Source, backup and rollback

Exact accepted source:

`/private/tmp/docara-b7-exact-a5cc0e7/tree/docs/site/build_production`

Timestamped rollback:

`/Users/rim/Sites/docara.test/.docara-backups/product-completion-live-catalog-a5cc0e7-20260719-182708/build_production`

Served target:

`/Users/rim/Sites/docara.test/build_production`

All paths used filesystem device `16777230`. Before the swap, the served tree
was byte-identical to a fresh exact-archive build of accepted Batch 6 and passed
that version's static verifier. The rollback copy was byte-identical to the
served tree and retained after publication.

Rollback is a same-filesystem rename restoring the retained Batch 6 tree,
followed by its exact verifier, HTTPS and browser checks. No rollback was
required.

## Integrity

```text
served before:        c22a0867fe32fb0d8ade57955085f0e592eab07cd9da45c29e32308f712c4c11
rollback copy:        c22a0867fe32fb0d8ade57955085f0e592eab07cd9da45c29e32308f712c4c11
exact Batch 7 source: dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675
verified staging:     dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675
served after swap:    dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675
served after browser: dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675
```

The exact Batch 7 verifier checked 60 HTML pages and 6477 local references
with zero broken references. Source, staging and served trees were
byte-identical. Consumed staging and the redundant pre-swap copy were cleaned;
the timestamped rollback remains retained.

## HTTPS and product checks

HTTPS returned `200` for:

- `/`;
- `/start/`;
- `/components/catalog/`;
- `/components/catalog/ui.alert/`;
- `/_docara/component-catalog.json`;
- `/_docara/search-index.json`;
- `/_docara/framework/smart/alert/js/alert.js`.

A unique nonexistent route returned `404`.

The served catalogue contains 17 records, 12 supported details, five
unavailable records and canonical content hash
`b767b85606778bf9ce22f6fd1db5a433c55c68d0847aaedfddfbf00849fdc0b1`.
The search receipt contains 59 documents.

## Served native-Chrome smoke

- desktop: 1440 by 900 viewport and 1426 pixel document width;
- cold mobile reload: 390 by 844 viewport and 378 pixel document width;
- dark theme is coherent on both layouts;
- search query `columns` returns three results;
- `ui.alert` renders one live pinned Smart-component;
- the mobile parameter wrapper is 328 pixels wide and contains its 462 pixel
  table with local automatic overflow;
- browser console warnings/errors: zero.

This accepts and locally publishes Batch 7 only. It does not claim public
release, production readiness, readiness of all Framework components, Batch 8,
Batch 9 or completion of the wider Goal.
