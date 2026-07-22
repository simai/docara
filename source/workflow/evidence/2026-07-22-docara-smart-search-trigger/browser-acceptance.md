# Browser acceptance: Docara Smart search trigger

Date: 2026-07-22
Verdict: PASS_WITH_NOTES
URL: `https://docara.test/ru/migration/legacy/`

## Desktop

- generated interactive target is a native `button` owned by `sf-button`;
- generated classes are the Framework contract plus the product behavior hook;
- geometry: `161.38 x 42 px`, reduced from the previous manually composed
  `193.38 x 42 px`;
- component gap is `normal`; no product `gap-1` remains;
- border is 1 px, padding is 8/16 px, radius is 4 px;
- light and dark themes retain correct on-surface color and outline;
- click opens the dialog, sets `aria-expanded=true` and focuses the input;
- Escape closes the dialog and returns visible focus to the trigger;
- `Cmd+K` opens the dialog and focuses the input;
- focus-visible is represented by the Framework 4 px focus shadow;
- browser console errors: zero.

## Mobile 390 x 844

- the generated text and keyboard hint are hidden; the search icon remains;
- geometry: `44 x 33.5 px` at the pinned mobile Framework root;
- click opens the dialog and focuses the input;
- Escape closes it; `Ctrl+K` reopens it and focuses the input;
- horizontal overflow: `0 px`;
- browser console errors: zero.

## Note

The non-square mobile height and desktop 42 px outline height reproduce the
already recorded pinned Framework producer defects. Docara does not add a
private height or padding override because that would make `size=1` misleading
again.
