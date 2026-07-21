# Acceptance: Docara compact header controls

Date: 2026-07-22
Baseline: `853959b`
Verdict: PASS for the local implementation batch

## Reference comparison

- Before, current search trigger: `sf-button--size-2`, 222 x 50 px.
- Before, current reader-settings trigger: `sf-icon-button--size-2`, 48 x 48 px.
- Legacy search control: `sf-input--size-1`, 272 x 42 px.
- Legacy settings control: `sf-icon-button--size-1`, 40 x 40 px.

## Applied contract

- Search trigger: `sf-button--size-1`.
- Reader-settings trigger: `sf-icon-button--size-1`.
- Search dialog: existing `sf-input--size-1`, unchanged.
- Accessibility floor: existing 44 px minimum target, unchanged.
- Canonical source only: `resources/publisher/components/header-actions.php`.
- Legacy renderer and page CSS: unchanged.

## Verification

- Focused builder: 1 test / 467 assertions, PASS.
- Full PHPUnit: 619 tests / 5,498 assertions, PASS.
- Production and deployed builds: 271 pages, 20,512 references, zero broken.
- Desktop 1440 x 900: search 137 x 44 px, settings 44 x 44 px, no overflow.
- Mobile 390 x 844: search 44 x 44 px, settings 44 x 44 px, text label hidden,
  no overflow.
- Search dialog opens; field class is `sf-input--size-1`, field height 42 px.
- Browser console errors: none.

## Runtime safety

- Active build: `/Users/rim/Sites/docara.test/build_production`.
- Staging:
  `/Users/rim/Sites/docara.test/.docara-staging/compact-header-controls-20260722-021519`.
- Rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/compact-header-controls-20260722-021519/build_production.previous`.

No public push, merge, tag, release or production-readiness claim was made.
