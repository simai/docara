# Tests

- Baseline reproduction: `SmartRegistryContributionTest` failed with
  `ui_smart_catalog_manifest_invalid:docara.page_title_field:description`.
- Focused regression after the fix: passed, 4 tests / 63 assertions on PHP
  8.4.20.
- Full package quality gate: passed on PHP 8.4.20 after the Admin owner supplied
  the required pinned runtime pair for `ui.dataview`.
- Lint: passed, 65 PHP files.
- Static analysis: passed with no errors.
- Complete feature suite: passed, 42 tests / 557 assertions.
- Evidence contract: passed.
- Scope check: passed, 26 changed files including the preserved naming and
  Smart-contribution prerequisite work.
- Browser evidence from the earlier contribution batch remains historical; no
  new browser-readiness claim is made by this compatibility fix.
