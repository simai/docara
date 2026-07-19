# Batch 5 combined directive budget correction

Date: 2026-07-19
Status: correction ready for a new immutable candidate
Rejected candidate: `68fba097d6d629ad77937a09a6c16b25ea709850`
Finding: `B5-HCS-P2-001`

## Reason

The first Batch 5 candidate counted typed and Smart directive-looking source
lines separately even though the iterative CommonMark parser recognized both
families. A large foreign-family input could therefore enter repeated parsing
before its own family-specific check rejected it.

That candidate remains rejected and was not published.

## Corrected contract

- One Markdown page has one shared budget of 64 typed and Smart
  directive-looking opening lines.
- A cheap raw-source preflight runs before CommonMark inspection or directive
  iteration.
- The 65th matched opener determines the stable public error code:
  `FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED` for `ui.*` and
  `MARKDOWN_BLOCK_LIMIT_EXCEEDED` for a typed Docara block.
- The parser and the preflight share one opener matcher.
- Marker-looking lines inside fenced examples are counted conservatively and
  this behavior is documented.

## Implementation

- `DirectiveBlockStartParser::matchOpening()` is the single matcher for both
  families.
- `CommonMarkInspector` performs the shared preflight before building the
  directive environment or entering the iterative extraction loop.
- `DirectiveLimitExceeded` carries the triggering family.
- Framework and portable callers map that family to the existing stable public
  error codes.
- A bounded observer seam lets the direct regression prove that all four
  overflow arrangements exit with zero directive iterations.

## Verification before candidate

- complete sequential PHPUnit before the mechanical Pint correction:
  472 tests, 2,450 assertions — PASS;
- post-format focused PHPUnit:
  73 tests, 271 assertions — PASS;
- direct cross-family preflight cases cover both source orders, both
  same-family overflows and the accepted 32 + 32 boundary;
- Pint, Composer strict validation, 269 PHP syntax checks, 54 literal JSON
  files and `git diff --check` — PASS;
- two isolated production builds are recursively equal:
  54 files, 47 HTML pages, 4,931 local references, zero broken references;
- common path-independent build digest:
  `c0d38e6badc833eaa29cf0f0482d4306c10aca943e993e79ffa629497a5b3060`;
- the pinned Simai Framework lock, manifests and runtime lock are unchanged.

The correction still requires independent exact-archive tester,
native-Chrome/UX/design and source/HCS/security acceptance. This evidence does
not authorize publication or claim whole-goal, release, production or
ecosystem readiness.
