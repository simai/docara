# Batch 2 — deterministic local search implementation

Status: corrected implementation complete, independent exact-candidate
reacceptance pending.

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

## First exact-candidate correction

Candidate `1d9bfed313efc7be725b577e53ce1b9271ec76d4` passed the full
automated suite but was not accepted. Independent HCS/source and UX/browser
review found four contract gaps:

- verifier ignored inherited `default_locale` while the builder used it;
- invalid index revision/origin/path rejected before the visible error state;
- the trigger/list markup used two inexact Framework class mappings;
- direct search planning did not reject a document outside `base_url`.

The correction tree aligns effective locale, adds the canonical starter
build-to-verifier regression, enters error state before early browser
rejection, uses `sf-button-text-container` plus a native unprojected `<ul>`,
and rejects cross-base documents with a dedicated negative test.

## Verification before corrected candidate commit

- focused search/config/builder tests: PASS;
- static verifier suite: `11 tests, 91 assertions`, PASS;
- focused correction/search/builder/verifier tests: `39 tests, 414 assertions`,
  PASS;
- canonical starter with only `default_locale`: build and static verifier PASS;
- full PHPUnit: `447 tests, 2205 assertions`, PASS on PHP 8.2.29;
- Pint: PASS;
- `node --check resources/portable/search.js`: PASS;
- production build: `42` HTML pages;
- static verifier: `3831` local references, `0` broken;
- two consecutive production tree aggregates:
  `81945efbe610a54986586a2a61f16e64556528e235596fb016330923f450fd1f`;
- prior browser matrix passed except the early invalid-revision error state;
  the correction requires a new exact browser reacceptance before publication.

This evidence does not claim completion of the product Goal, public release or
production readiness.
