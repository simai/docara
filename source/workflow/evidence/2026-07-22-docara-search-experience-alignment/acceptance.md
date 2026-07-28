# Acceptance: Docara search experience alignment

Date: 2026-07-22
Status: PASS
Scope: local Docara search only

## Implemented contract

- `sf-modal` owns the modal portal, focus trap, Escape and overlay lifecycle.
- The canonical Simai Framework loader exclusively discovers and loads
  `sf-button`, `sf-icon`, `sf-modal` and content Smart elements.
- The query uses the Framework size-1 input anatomy and native icon-button
  component classes without private control geometry.
- Results share one surface with dividers, trail, title and contextual excerpt.
- Query tokens are inserted as escaped semantic `mark` nodes in titles and
  excerpts; no result HTML is assembled from untrusted index text.
- The existing exact search-index schema, digest and same-origin checks remain
  active.

## Automated evidence

- `PortableSiteBuilderTest|FrameworkComponentRuntimeTest`:
  93 tests, 973 assertions, PASS.
- `StaticBuildVerifierTest|PortableInitCommandTest`:
  30 tests, 370 assertions, PASS.
- JavaScript syntax checks: PASS.
- PHP syntax checks: PASS.
- JSON validation: PASS.
- `git diff --check`: PASS.
- Production build: 271 HTML pages, 20,512 local references, 0 broken.

The broad suite was not used as the closure claim because its earlier run
entered a pre-existing long-running CLI build after passing the completed
tests. Acceptance is intentionally based on the focused affected surfaces,
the exact build verifier and live browser behavior.

## Browser evidence

Published URL: `https://docara.test/ru/`

Desktop search for `компонент`:

- 20 displayed results;
- 22 semantic `mark` matches;
- Arrow Down focuses `/ru/components/`;
- the Smart modal renders through one Framework portal;
- overlay computed background is `rgb(0, 0, 0)` with opacity `0.6`;
- no horizontal overflow;
- zero browser warnings or errors.

Responsive acceptance at 390 x 844:

- modal panel: 362 px wide, from x=14 to x=376;
- 20 results and 22 highlighted matches;
- no horizontal overflow.

Light and dark theme checks use the same neutral scrim and retain legible
Framework surfaces. Escape closes the modal and restores orientation.

## Nonclaims

This PASS does not claim a public release, package release, production
readiness or completion of unrelated Docara work.

## Follow-up: contextual matched excerpts

The original card renderer highlighted terms only when they happened to be in
the fixed leading description excerpt. Ranking could therefore find a page by
body text without showing why the page matched.

The corrected renderer:

- inspects description, headings and body text;
- chooses the source containing the most query terms;
- centres a bounded 180-character excerpt around the first real occurrence;
- keeps semantic `mark` highlighting and safe text-node rendering;
- falls back to a leading excerpt only when no visible source occurrence is
  available.

Fresh evidence:

- focused regression: 93 tests, 975 assertions, PASS;
- production build: 271 pages, 20,512 references, 0 broken;
- live query `устано`: 15 results, 15 cards with a highlighted match, 15
  excerpts with a highlighted match;
- no horizontal overflow and zero browser warnings/errors.

## Follow-up: minimum typography scale

The idle hint and keyboard-help footer used `label-small`, which resolves to
the Framework `1/4` token and computed as 10 px / 12 px. This was below the
project minimum of `1/3`.

Both surfaces now use `label-medium`. Fresh browser evidence:

- idle status: 12 px font, 16 px line height;
- keyboard footer: 12 px font, 16 px line height;
- query `устано`: 15 results, all 15 cards contain a highlighted match;
- no horizontal overflow and zero browser warnings/errors;
- focused regression: 93 tests, 984 assertions, PASS.

## Follow-up: keyboard hint spacing

The three keyboard hints now use the existing Simai Framework utilities
`inline-flex items-cross-center p-x-1/2 p-y-1/4`. The former private
`padding-inline` declaration was removed.

Fresh evidence:

- computed key font and line height: 12 px / 16 px;
- computed padding: 4 px top and bottom, 12 px left and right;
- computed total key height: 24 px for `↑↓`, `Enter` and `Esc`;
- live query `устано`: 15 results and 19 visible highlighted occurrences;
- focused regression: 93 tests, 987 assertions, PASS;
- production build: 271 pages, 20,512 references, 0 broken.
