# Batch 3 — reading context implementation

Baseline closure: `f482ced`
Status: candidate-ready; exact-archive acceptance and local publication pending.

## Product result

- one inherited strict `reading` branch controls breadcrumbs, page outline,
  outline depth and previous/next links at site, section and page levels;
- one canonical topology produces the visible menu, full breadcrumbs and
  deterministic document adjacency without a second registry;
- every H1–H6 receives a stable Unicode id; the outline projects H2 through
  the configured maximum level and remains identical on desktop and mobile;
- wide screens use a sticky right outline, tablet/mobile use native
  progressive disclosure, and previous/next links stack on narrow screens;
- landing pages ignore documentation-reading chrome by design;
- public docs, starter, migration guidance, strict schemas and negative tests
  are synchronized with the implementation.

## Correctness and fail-closed boundary

The implementation rejects invalid outline depth, invalid UTF-8, headings
without an accessible name, duplicate HTML ids, missing local fragments and
unsafe fragment encoding. Heading ids avoid fixed shell ids, include image
alternative text and normalize decomposed combining marks before slugging.

Hidden pages remain in the canonical topology but are not adjacency targets.
An unlinked structural section is kept only while it has visible descendants;
an empty structural placeholder disappears. Home lookup is independent of
sort order.

## Simai Framework mapping

- Core is pinned to `simai/ui v5.3.2` commit
  `7e836d8a9414d5da553fb1ab0404721e5b48769a`;
- breadcrumbs use exact Core classes and autoloaded runtime;
- `data-max-items` equals the semantic breadcrumb count, so the pinned Core
  runtime retains every level and does not generate its inaccessible default
  English ellipsis control;
- outline, previous/next and responsive disclosure reuse Framework utilities,
  tokens, icons and native semantics;
- no new Smart-component, package, dependency or Framework repository write
  was required.

## Verification before immutable candidate

- isolated full PHPUnit: `464 tests, 2308 assertions`, PASS;
- focused reading-context suite: `36 tests, 368 assertions`, PASS;
- Pint: PASS;
- Composer strict validation: PASS;
- four schemas plus canonical docs/starter JSON parsing: PASS;
- JavaScript syntax check for the retained search runtime: PASS;
- two consecutive production builds: `43` HTML pages, `4339` local
  references, zero broken references;
- both production tree digests:
  `826c8a0dac97bc1a17f7b5926d05908d14424fa410631a35f6e374403656654b`;
- `git diff --check`: PASS;
- federation process check: PASS with the expected nonterminal
  `simplicity_review_ref_missing` warning only;
- independent implementation reverse-audit: PASS;
- independent documentation re-audit: PASS;
- independent UX/designer browser review: PASS.

One earlier full-suite invocation is explicitly excluded from evidence: it ran
concurrently with an auditor that cleaned the shared fixture directory and
therefore reported only disappearing `.cache` and `tests/fixtures/tmp` paths.
The later isolated run above is the valid result.

## Nonclaims

This record does not yet accept an immutable Batch 3 candidate, local
publication, Batch 4, the wider Goal, public release or production readiness.
