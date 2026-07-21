# Acceptance: declarative region composition recipes

Date: 2026-07-21
Verdict: PASS
Baseline: `5823e5b974ceb4e26e8e7973903e1cfc87f37ce0`

## Outcome

Docara's primary declarative generator now assembles practical shell regions
from registered definitions. Authors can inspect and reuse five exact-source
recipes without editing PHP or templates:

- branded header with the built-in header Smart component and `ui.button`;
- sidebar with navigation, safe text and a local link;
- aside with the outline Smart component and `ui.alert`;
- footer with safe structured elements;
- site, section and page inheritance with replace, `$reset` and provenance.

## Done When verdict

Every required recipe is present in the existing demonstrator and exposes its
descriptor, Markdown/configuration sources and generated route. Authored input
is limited to registered section, element and Smart definitions; tags, links,
utilities, props, IDs and ordering fail closed. Raw HTML, PHP, Blade,
callbacks, scripts and arbitrary template paths remain unavailable.

The examples use Simai Framework assets and the pinned compatibility contract.
Desktop, mobile, light and dark browser checks pass without horizontal
overflow or console errors. Exact source provenance and deterministic output
are verified.

## Verification

- focused composition/configuration/parity: 36 tests, 244 assertions;
- full suite: 595 tests, 4,947 assertions;
- formatter, Composer validation and `git diff --check`: PASS;
- two clean builds: 152 HTML pages and 15,489 verified local references each;
- build A, build B and served-site manifest digest:
  `68475469b5a1e84e85bdc07ae4da9ad30bb2e3b9c5d69c37154c8578adde92d3`;
- legacy renderer SHA-256 unchanged:
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`;
- human-centered simplicity validator: PASS, no warnings or blockers.

## Publication and rollback

The verified build was atomically published only to the local ServBay site at
`/Users/rim/Sites/docara.test/build_production`. The previous build is stored
at
`/Users/rim/Sites/docara.test/.docara-backups/region-composition-20260721T063332Z/build_production`.
HTTP and browser smoke checks pass. No push, merge, tag, release or production
deployment was performed or claimed.
