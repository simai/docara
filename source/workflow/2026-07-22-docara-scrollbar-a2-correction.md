# Workflow: Docara scrollbar a2 correction

Date: 2026-07-22
Status: review ready
Baseline: `a6a718beb9b22bd0124bde35bd1d114399a5b047`
Primary owner: `docara`
Companions: `sf5`, `ux`, `tester`, `ops`

## Primary user outcome

Both desktop rail scrollbars sit one Framework `a2` token (`2 px`) from their
divider, and the active page-outline marker is drawn directly over the divider
instead of following the scrollbar inset.

## Scope matrix

| Surface | Previous result | Corrected result |
| --- | --- | --- |
| left navigation scrollbar | `b2` (`12 px`) from divider | `a2` (`2 px`) from divider |
| right contents scrollbar | `b2` (`12 px`) from divider | `a2` (`2 px`) from divider |
| active contents marker | follows the scroll-container edge | overlays the `1 px` divider |
| content padding | independent inner padding | unchanged |
| directionality | logical LTR/RTL scrollbar side | unchanged and reverified |
| mobile | desktop rails hidden | unchanged |

## Findings register

- `P2`: the accepted `b2` interpretation is wider than the clarified design token.
- `P2`: the active outline marker must be anchored to the divider, not the
  scroll-container edge.

## Graph routing note

The federation semantic router treated the literal design-token name `a2` as
an accounting signal and selected an unrelated process. This is a non-blocking
graph gap. Raw owner sources and the existing repo-local Docara workflow are
used as the authoritative fallback.

## Readiness matrix

- source guards: PASS;
- focused PHPUnit: PASS, `37 tests`, `811 assertions`;
- full PHPUnit: PASS, `623 tests`, `5568 assertions`;
- exact build and static verification: PASS, `271` HTML pages and `20512`
  local references with no broken references;
- desktop browser geometry and visible overflow: PASS;
- active marker/divider geometry: PASS, `0 px` displacement for both short and
  overflowing contents;
- mobile regression: PASS at `390 x 844`, no horizontal overflow;
- local publication backup and rollback: PASS.

## Implementation result

- Both rails use `var(--sf-a2)` as the spacing token. The observed `3 px`
  distance between the outer edge of a rail and the scroll container is the
  required `2 px` spacing plus the `1 px` divider itself.
- The active outline marker is `2 px` wide and is placed on the divider.
- `docara.toc` measures the native scrollbar contribution and writes only a
  private marker offset variable. This keeps the marker on the divider when a
  platform scrollbar appears without hard-coding its width.
- The verified build is served at `https://docara.test/`.
- Rollback copy:
  `/Users/rim/Sites/docara.test/.docara-backups/scrollbar-a2-20260722-125627/build_production.previous`.

## Exclusions

- no menu hierarchy, copy, URL, locale or behavior changes;
- no Simai Framework source or revision change;
- no public push, merge, tag, release or production-readiness claim.
