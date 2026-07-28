# Acceptance: Docara adaptive mobile contents

Date: 2026-07-22
Candidate baseline: `2732d37a`
Verdict: PASS for the local implementation batch

## Functional evidence

- Russian desktop and mobile label: `Содержание`.
- Bundled translations updated for `ru`, `en`, `fr-CA`, `ar`, `zh-Hans`.
- Validated configuration: `reading.mobile_toc = auto | always | never`.
- `auto`: show for at least four outline entries or any H3-H6 entry.
- Generated state: `shown | auto-hidden | disabled | unavailable`.
- The static verifier accepts each internally consistent state and rejects a
  forged `shown` state with a missing mobile outline.
- The duplicated `docara.toc` title is hidden only inside the mobile sheet;
  its dialog title and desktop heading remain visible and accessible.

## Automated verification

- Focused publisher/configuration/rendering suite: 71 tests, 1,056 assertions.
- Full PHPUnit: 619 tests, 5,481 assertions, PASS.
- Production build: 271 HTML pages.
- Static verifier: 20,512 local references checked, zero broken references.
- `git diff --check`: PASS.

## Browser verification

- `https://docara.test/ru/migration/`, 1440x900:
  desktop heading `Содержание`, state `auto-hidden`, no horizontal overflow.
- same page, 390x844: no redundant mobile contents trigger, desktop rail hidden,
  no horizontal overflow.
- `https://docara.test/ru/authoring/reading-context/`, 390x844: state `shown`,
  one trigger, sheet opens, `aria-expanded=true`, visible dialog title
  `Содержание`, duplicate component heading hidden, no horizontal overflow.
- Console errors: none.

## Runtime safety

- ServBay build root: `/Users/rim/Sites/docara.test/build_production`.
- Staging: `/Users/rim/Sites/docara.test/.docara-staging/adaptive-mobile-toc-20260722-014125`.
- Rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/adaptive-mobile-toc-20260722-014125/build_production.previous`.
- The candidate was verified before and after the atomic directory swap.

No public push, merge, tag, package release or production-readiness claim was
made.
