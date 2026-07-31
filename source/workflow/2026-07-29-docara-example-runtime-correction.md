# Docara example runtime correction

Date: 2026-07-29
Status: complete
Workflow ID: `2026-07-29-docara-example-runtime-correction`
Track: docara-consolidation

## Goal

Restore the shared component example viewer and Framework icons without
page-specific workarounds.

## Findings

- inactive example panels remain in an overlapping CSS grid and therefore keep
  contributing their intrinsic height;
- source code blocks are stretched to that shared height instead of using their
  own symmetric `pre` padding;
- the immutable icon font URL points to `distr/fonts`, while the accepted Core
  artifact stores the file under `distr/component/icons/fonts`;
- the missing font explains both the copy-icon and navigation-icon failures.

## Scope

- correct the immutable font URL in the Docara Framework asset planner;
- make inactive example panels leave layout completely;
- keep source-block spacing owned by the existing `pre` padding;
- update regression assertions and rebuild the local documentation site;
- verify badge, alert and navigation surfaces in a real browser.

## Done when

- top and bottom source padding are equal;
- a short example does not inherit the height of a longer source panel;
- copy and navigation icons render without a font 404;
- focused tests, full static build verification and `git diff --check` pass;
- no Framework distribution is edited manually.

## Boundary

The federation resolver selected the installed Docara owner skill, but the user
explicitly excluded it as stale. Implementation therefore follows the current
repository contract and the development workflow. No merge, tag, release or
public deployment is authorized by this batch.

## Result

- the immutable icon font is loaded from the real Core artifact path;
- copy, navigation, search, settings and alert icons render in the browser;
- inactive example panels no longer participate in layout;
- preview panels remove the trailing margin of their final block;
- source panels keep only the symmetric internal `pre` padding;
- the corrected build is published to the local `docara.test` test site.

## Verification

- PHPUnit: `339` tests, `6734` assertions, PASS;
- production build: `100` source pages, PASS;
- static verification: `200` HTML pages, `17739` local references,
  `0` broken references;
- `git diff --check`: PASS;
- desktop short preview: `20px` top and `20px` bottom spacing;
- mobile source panel: `16px` top and `16px` bottom spacing;
- desktop copy control: `40x40`, icon `24x24`;
- mobile copy control: `36x36`, icon `20x20`;
- light and dark browser checks: visible Framework icons and zero console
  errors or warnings;
- rollback snapshot:
  `/Users/rim/Sites/docara.test/.docara-backups/example-panel-spacing-20260729-234933/build_production.previous`.

No commit, push, tag, release or public deployment was performed.

## Follow-up: copy interaction and line-number cleanup (2026-07-30)

- example source panels no longer expose the highlight.js line-number column;
- the generated line-number table is neutralized inside the example viewer, so
  it adds no background, border, margin or extra spacing;
- copy extracts only source rows, without generated line numbers;
- the copy control uses the muted on-surface variant at rest and the regular
  on-surface color on hover;
- successful copying temporarily changes `content_copy` to `check` and restores
  the original icon after 1.6 seconds;
- focused and clicked copy controls do not retain a decorative outline or
  shadow, while the accessible name remains available;
- the updated badge page and shared runtime assets were published locally;
- rollback snapshot:
  `/Users/rim/Sites/docara.test/.docara-backups/badge-copy-runtime-20260730-011234`.

Verification: focused PHPUnit suites PASS, partial badge build PASS, live HTTP
200, clipboard content matches the Markdown source, copy state and icon restore
correctly, generated line-number cells are hidden, and relevant
`git diff --check` is clean. Full static verification currently reports the
pre-existing `docara.alert` trusted-fragment mismatch; the generated badge page
itself is not the failing surface.
