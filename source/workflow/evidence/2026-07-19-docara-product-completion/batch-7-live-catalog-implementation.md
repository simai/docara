# Batch 7 — live component catalogue implementation

Date: 2026-07-19
Accepted base: `68a960ff1debde48664aa8541413dbef208612ee`
Implementation base: `1f68f63fd3aea50d0092b864e2948102fe3cbef5`
Status: `MUTABLE_VERIFIED_PENDING_IMMUTABLE_CANDIDATE`

## Outcome

Docara now projects one live authoring catalogue from the accepted
`docara.effective_component_catalog.v1`:

- `/components/catalog/` is one generated grouped index;
- `/components/catalog/<id>/` is one generic detail shape;
- all 12 supported records have an exact executable example;
- five unavailable records remain visible with owner, reason, fallback,
  admission condition and limitations under progressive disclosure;
- Russian presentation and examples are co-located with each canonical record
  rather than copied into a second registry;
- the generated pages inherit the site's layout, search, reading, TOC and
  navigation settings;
- generated details participate in search, breadcrumbs and previous/next
  without flooding the primary left navigation.

The catalogue receipt is deterministic and fail-closed. The static verifier
reconstructs trusted records, exact examples, generated DOM and assets, then
rejects missing, extra, split, stale or hash-tampered surfaces.

## Required recipe closure

`docara.columns` is now a supported typed portable recipe:

- two to four regions separated by top-level thematic breaks;
- splitting follows parsed CommonMark block structure;
- one, five, nested or malformed compositions fail closed;
- output uses pinned Simai Framework layout and overflow utilities;
- the former requirement-only record is removed rather than mutated into
  executable readiness.

## Author and operator experience

- purpose text explains the component, not the page implementation;
- cards are full-surface links with a visible focus contract;
- live example, exact call, parameters, relationships, states, limitations and
  provenance use one universal detail layout;
- parameter tables show validation, mirrors, allowed combinations and required
  relationships, with local horizontal scrolling on narrow screens;
- `docara serve` uses the running PHP binary and a read-only static router for
  pretty URLs, including dotted component identifiers;
- failed or declined builds cannot silently serve a stale output tree.

`ui.alert` success is intentionally not advertised: the exact pinned Framework
asset has a transparent success icon defect. The catalogue uses an admitted
info state and keeps the limitation explicit rather than consuming moving
Framework `main`.

## Deterministic projection

- effective records: 17;
- supported records with live examples: 12;
- unavailable records: 5;
- generated index pages: 1;
- generated detail pages: 12;
- authored Markdown pages: 47;
- total generated HTML pages: 60;
- search documents: 59;
- total output files: 70;
- catalogue content SHA-256:
  `b767b85606778bf9ce22f6fd1db5a433c55c68d0847aaedfddfbf00849fdc0b1`;
- detail receipt content SHA-256:
  `9c162a082032fb5be7223a806539d7d272d755378a3c5c894a42b6b862b0970d`;
- complete build tree SHA-256:
  `dc6e2a997314a2497da29af3e696937d7f46aee2a832ed3166e2862cdd963675`.

Two clean production builds were byte-identical under `diff -qr`. Packaged
`verify-static` checked 60 HTML pages and 6477 local references with zero
broken references.

## Verification

- complete sequential PHPUnit: 534 tests, 3923 assertions, PASS;
- independent final focused retest: 17 tests, 626 assertions, PASS;
- Composer `validate --strict`: PASS;
- Pint `--test`: PASS;
- 27 catalogue/schema JSON files parse: PASS;
- `git diff --check`: PASS;
- repository `.env` absence after tests: PASS;
- mutable 1440 by 900 and 390 by 844 browser pre-acceptance: PASS;
- root horizontal overflow: absent;
- wide parameter table overflow: contained by its local scroll wrapper.

## Remaining gate

This evidence does not accept or publish Batch 7. One immutable candidate must
pass:

1. independent exact-archive tests, builds, verifier and determinism;
2. complete-diff Human-Centered Simplicity/source/security review;
3. native-Chrome keyboard, responsive, light/dark and UX/design acceptance;
4. safe local staging/publication with timestamped rollback and matching
   source/staging/served digests.

Public release, default-branch migration, Framework owner writes and repository
retirement remain excluded.
