# Verification: canonical `section.json`

Date: 2026-07-20
Baseline: `250a12f`
Branch: `codex/docara-consolidation`

## Contract

- Canonical descriptor: `section.json`.
- Legacy `_section.json`: rejected with
  `SECTION_DESCRIPTOR_LEGACY_NAME`.
- If both names exist, the legacy-name error wins; no implicit precedence is
  created.
- Existing build output is preserved when the legacy-name error is raised.

## Repository checks

- 14 source/starter descriptors are valid JSON.
- 0 physical `_section.json` descriptors remain in current documentation and
  starter trees.
- PHP syntax: PASS for all three changed runtime classes.
- Pint: PASS for changed runtime and test classes.
- `git diff --check`: PASS.

## Tests

- Focused portable suite: PASS, 74 tests and 1443 assertions.
- Legacy descriptor scenarios: PASS, 3 tests and 6 assertions.
- Full non-legacy-snapshot suite: PASS, 544 tests and 4332 assertions.
- The complete 551-test invocation has two failures only in the legacy Jigsaw
  snapshot group. The same two date-path failures reproduce from exact
  baseline `250a12f`; they are not introduced by this change.

## Static build

- Production documentation built twice from `docs/site`.
- Both generated trees are byte-for-byte identical.
- Static verifier: PASS.
- Pages checked: 66.
- Local references checked: 6036.
- Broken references: 0.

## Local publication

- Served target:
  `/Users/rim/Sites/docara.test/build_production`.
- Backup:
  `/Users/rim/Sites/docara.test/.docara-backups/section-json-20260720151515/build_production`.
- Same-filesystem rollback tree:
  `/Users/rim/Sites/docara.test/.docara-staging/section-json-20260720151515/served-before`.
- Publication used a verified staging copy and atomic directory moves.
- HTTPS smoke: `/`, `/authoring/project-files/`,
  `/authoring/inheritance/`, `/migration/legacy/` and `/troubleshooting/`
  all returned 200.

## Browser acceptance

- Desktop title and H1 match the requested page.
- Current page and its ancestor are exposed in navigation.
- Canonical name and migration error guidance are rendered.
- 390 px viewport has no horizontal overflow.
- Mobile navigation opens and preserves the active page.
- Browser console warnings/errors: 0.
