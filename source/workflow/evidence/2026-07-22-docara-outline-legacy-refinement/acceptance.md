# Acceptance evidence: Docara right outline refinement

Date: 2026-07-22
Candidate baseline: `dda73e8a5188a4b177d5c5aa8bea29f51727fe9d`
Local surface: `https://docara.test/ru/migration/`

## Legacy comparison

At 1440x900, `https://docara-legacy.test/en/` reported 14px links with a
24px line height inside 36px list rows. Its active item projected a vertical
segment onto the navigation rail.

Before this batch the canonical publisher reported 16px links, 24px line
height, 16px block padding and 56-104px rows. No outline link had an active
semantic state.

## Accepted browser result

At 1440x900 on the published local candidate:

- font size: 14px;
- line height: 20px;
- Framework `p-1/3`: 8px inline and block padding;
- normal single-line row: 36px;
- active items in the desktop outline: exactly one;
- active semantics: `aria-current="location"` plus `data-docara-active` on
  the owner item;
- active marker: 2px, `var(--sf-primary)`, placed over the logical-start rail;
- after scrolling from 700px to 860px, active text changed from
  `Маршруты перехода` to `Общий безопасный порядок`;
- no browser warnings or errors.

At 390x844 the desktop rail was hidden, the mobile outline remained enabled,
its link minimum block size remained 44px and the page had no horizontal
overflow.

## Build and regression evidence

- JavaScript syntax: PASS (`node --check resources/smart/assets/toc.js`).
- Focused builder suite: PASS, 35 tests / 688 assertions.
- Exact production documentation build: PASS.
- Static verifier: 271 HTML pages, 20,569 local references, zero broken.
- Full PHPUnit: PASS, 618 tests / 5,465 assertions.
- `git diff --check`: PASS.

## Local publication and rollback

The exact verified tree was staged and compared with `rsync -ani --delete`,
then published to `/Users/rim/Sites/docara.test/build_production`.

Accepted publication stamp: `20260722-011142-outline-accepted`.

Rollback backup:
`/Users/rim/Sites/docara.test/.docara-backups/20260722-011142-outline-accepted/build_production`.

HTTP smoke: `https://docara.test/ru/migration/` returned HTTP 200. The served
tree matched the exact build after publication.

Action-gate evidence:
`source/output/action-gates/action-gate-report-20260721220821.json`.

## UX and simplicity verdict

PASS. The change removes excess density and adds one necessary orientation
signal without adding controls or page-specific styling. It preserves visible
focus, semantic current-location state, logical properties for RTL, mobile
touch targets and the existing declarative ownership boundary.

## Nonclaims

No public push, merge, tag, package release, production deployment or
production-readiness claim was made.
