# Evidence summary

## Exact target

- branch: `codex/docara-consolidation`
- audit HEAD: `62532404dbc667340cea7bee16cda9fc59ddcd0c`
- accepted product candidate embedded in workflow: `c537e17f61f890fdbf5635c83ee642109bf730a4`
- product files were not modified by this audit
- no fetch, push, merge, tag, release or deploy was performed

## Clean automated acceptance

Tests were executed from a clean local clone at the exact audit HEAD.

| Check | Result |
|---|---|
| `composer validate --strict` | PASS |
| clean dependency install on PHP 8.2 | PASS |
| Pint | PASS |
| PHPUnit, PHP 8.2.29 | PASS — 307 tests, 4149 assertions |
| PHPUnit, PHP 8.4.20 | PASS — 307 tests, 4149 assertions |
| language JSON: ar/en/fr-CA/ru/zh-Hans | PASS |
| Composer advisory audit | PASS — no advisories |
| repository diff/secret hygiene | PASS |

## Portable runtime

The source archive and Composer ZIP were each installed into fresh temporary
directories. For both paths:

- `init` copied 20 starter files;
- `init --update` copied 0 and preserved all 20 files;
- the user `docara.json` hash did not change;
- build produced 20 logical pages;
- verifier checked 58 HTML files and 820 local references with zero broken
  references;
- repeated and independent builds produced the same path-relative digest:
  `74fc9af0d0c87527d8da172906381be3a407590d85fd640dd88d39eb5f713b29`.

## Documentation build

- 86 logical authored/generated pages;
- 190 HTML files;
- 10 480 checked local references;
- zero broken references;
- two independent builds were byte-identical;
- no active Jigsaw/Mix production runtime was found in the candidate.

## Browser acceptance of the exact build

The exact generated build was served on a temporary loopback port, not from
the stale ServBay site.

Desktop 1440x1000:

- locale redirect, landmarks, skip link, left navigation, outline and pager;
- search dialog focus, keyboard navigation, 9 results and visible `<mark>`
  highlights for `руковод`;
- Escape closes the dialog;
- light/dark themes work;
- four-level navigation expands correctly on a deep route;
- zero console errors or warnings.

Mobile 390x844:

- hamburger menu and active page work;
- full-screen search returns highlighted matches;
- next-page card appears before previous-page card;
- landing preset omits documentation navigation;
- horizontal overflow is zero.

## Security observations

The only PHP process-launch primitive is the local development `serve` command;
host and port are validated and shell arguments are escaped. Automated tests
cover traversal, symlink/hardlink escape, collisions, unsafe manifests, atomic
updates, PHP execution attempts and stale builds. No tracked private keys or
credentials were found.
