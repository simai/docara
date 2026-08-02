# G1C-R5 — integrated acceptance

Status: `PASS_READY_FOR_INDEPENDENT_AUDIT`

Runtime implementation revision:
`94d2afd9cb71d6b02d8f4a63d4f807e127b1b190`.
Verified governance revision:
`46c9ac6ad99ec0b4bb72501ddab954925becf19c`.

## Integrated verification

- exact cross-host: 1 test / 45 assertions; byte-identical HTML
  `7133c5dcd44aa85f351a85c61c280aa883abd5cdb3c91206168ad63ada497b38`;
- focused documentation/contract/provider/project/search matrix: 41 tests /
  1,631 assertions, PASS;
- broad Goal 1 runtime/security matrix: 79 tests / 671 assertions, PASS;
- full PHPUnit: 373 tests / 7,239 assertions, PASS on PHP 8.4.20;
- Pint `--test`, Composer validate strict, 308-file PHP lint, 459 JSON files,
  210 YAML files and `git diff --check`: PASS;
- project graph: 1 goal, 9 stages, 12 batches, 6 mappings, warnings=0,
  blockers=0;
- final full builds A/B: 103 routes, 305 files, 206 HTML, byte-identical
  ledger `021f223c8aa4edf369ed4ba57628862ec192b775883e521e4174579075b94424`;
- final full/single equality: Alert, Button and Smart authoring route preserve
  the entire 305-file ledger;
- static verifier A/B: 21,430 local references each, broken=0;
- fresh Chromium desktop/mobile smoke: Framework Alert/Button, all four
  `docara.*` shell components, tabs/copy/navigation/search/settings/focus/Esc,
  console errors=0 and overflow=0.

The first disposable build attempt inherited a broken Homebrew PHP 8.2 binary
whose ICU library was missing. The evidence matrix uses the explicit ServBay
PHP 8.4.20 binary; this environment correction changed no source or output.

## Structural conclusion

Goal 1 contains one Gateway, one renderer registry and one PageBuilder. Active
Goal 1 runtime/search/admission has no central component-ID list. Provider,
artifact and exact lock data own resolution. The sole known shell-region
allowlist remains explicitly deferred to unstarted Goal 2 and is not counted as
a Goal 1 claim.

Executor evidence sets Goal 1 to `ready_for_independent_audit`; it does not mark
it independently accepted.

Goal 2 and Goal 3 remain unstarted. No merge, push, tag, release, deploy or
live-site write is part of this contour.
