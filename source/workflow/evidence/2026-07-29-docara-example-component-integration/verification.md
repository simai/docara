# Docara example component verification

Date: 2026-07-29
Workflow ID: `2026-07-29-docara-example-component-integration`
Verdict: PASS

## Product result

- `:::example` renders one compact surface with a `Пример` tab and named source
  tabs.
- Markdown examples use one Markdown source. Web examples accept HTML plus
  optional CSS and JavaScript sources.
- Preview, syntax-highlighted source and copy action originate from the same
  accepted input.
- Component pages use the order: common example, parameters, compact
  variations, useful limitations.
- Russian labels use `Пример`, not `Демо`.

## Automated verification

- PHPUnit: `334` tests, `6675` assertions, PASS.
- Two clean documentation builds: `265` files each, byte-identical.
- Static verifier: `200` HTML pages, `17730` local references, `0` broken.
- Production build installed to `/Users/rim/Sites/docara.test/build_production`.
- Post-install static verification: PASS.
- Pint, JSON parsing and scoped `git diff --check`: PASS.

## Browser acceptance

- `https://docara.test/ru/components/example/` exposes `Пример` and `Markdown`
  tabs; selection, panels, tab indexes and copy-action visibility change
  correctly.
- `https://docara.test/ru/components/alert/` is ordered as `Пример`,
  `Параметры`, `Варианты`, `Важно`.
- Alert icons render at `24 x 24` CSS pixels.
- Browser console errors: `0`.

### Underline-tabs correction

- The component uses `sf-tabs sf-tabs--underline`, `sf-tabs-top-container` and
  `sf-tabs-top` instead of a locally drawn underline.
- Tab typography resolves to `16px / 24px`, the Framework `sf-text-1` value.
- Selected-tab lower radii resolve to `0px`; underline width is `2px`.
- The component bottom margin resolves to `16px`, the current `space-1` value.
- Switching from `Пример` to `Markdown` updates selection, panels and copy
  action correctly. Browser console errors: `0`.

## Recovery

Pre-install backup:
`/Users/rim/Sites/docara.test/.docara-backups/20260729-012415-before-example-component`.

Pre-correction backup:
`/Users/rim/Sites/docara.test/.docara-backups/20260729-084720-before-example-tabs-correction`.

No merge, tag, package release or public deployment was performed.
