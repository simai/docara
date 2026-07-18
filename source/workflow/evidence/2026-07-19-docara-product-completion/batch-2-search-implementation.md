# Batch 2 — deterministic local search implementation

Status: implementation complete, independent exact-candidate acceptance pending.

## Product result

- inherited strict `search.enabled` and `search.indexed` configuration with
  `$reset` and provenance;
- canonical build-time index from hydrated Markdown and the exact supported
  Smart text projections (`ui.alert` title/supporting text and `ui.button`
  text);
- locale-isolated browser search with no endpoint, external search provider or
  Node.js user build;
- native dialog, input and result links composed with pinned Simai Framework
  classes, utilities and theme tokens;
- lazy same-origin load, strict client contract, safe URLs inside `base_url`,
  text-only DOM rendering and independent index/runtime cache revisions;
- keyboard contract: `Cmd/Ctrl+K`, arrows, Enter, one-step Escape, focus loop
  and focus return;
- 44 by 44 CSS-pixel mobile search/close targets and zero horizontal overflow;
- public documentation, migration mapping, troubleshooting and privacy
  boundary.

## Fail-closed boundary

The build rejects invalid runtime bytes, invalid UTF-8, unsupported Smart text
projections, an enabled locale with zero indexed pages and an invalid generated
index before destination cleanup. The static verifier independently checks:

- search artifacts are present exactly when search is enabled;
- root/document/heading shapes and additional-property boundaries;
- canonical `content_sha256` and exact runtime byte hash;
- document ids, ordering, duplicates, locale, safe URLs and exact manifest
  membership;
- HTML index/runtime references and their cache revisions.

`navigation.hidden` and `search.indexed: false` are not access control. HTML and
the local JSON index are public static artifacts.

## Verification before candidate commit

- focused search/config/builder tests: PASS;
- static verifier suite: `11 tests, 91 assertions`, PASS;
- full PHPUnit: `444 tests, 2193 assertions`, PASS on PHP 8.2.29;
- Pint: PASS;
- `node --check resources/portable/search.js`: PASS;
- production build: `42` HTML pages;
- static verifier: `3831` local references, `0` broken;
- two consecutive production trees:
  `f8567047548733a852e2ccd0fdf358798da3074fe837bde20be8fbc3b0011486`;
- browser smoke: title result first for `наследование`, one Escape closes and
  restores focus, empty-result Tab loop is contained, `390x844` targets are
  `44x44`, page overflow is `0`.

This evidence does not claim completion of the product Goal, public release or
production readiness.
