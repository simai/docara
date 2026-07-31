# Docara modular reader preferences — acceptance

Date: 2026-07-24  
Result: `PASS`  
Scope: local candidate on `codex/docara-consolidation` and local publication at
`https://docara.test/`.

## Accepted outcome

- Author-time presentation defaults remain in `settings`.
- Reader-facing controls are declared only in the site-level
  `reader_preferences` contract.
- The initial registry exposes only `appearance.theme`.
- The generic compiler validates contributions, groups, copy, values and
  effects fail-closed.
- The product-owned `docara.preferences` Smart component renders a logical
  side panel using the admitted SIMAI Framework `sf-modal`.
- The runtime stores one versioned JSON record, isolated by site base URL.
- Theme application supports prepaint, reset, storage fallback and cross-tab
  synchronization.
- The disabled contract renders no trigger, modal or preference assets.
- No legacy theme cookie or standalone theme storage key participates in the
  declarative runtime.

## Automated verification

- PHPUnit: `331 tests`, `5117 assertions`, PASS.
- Composer strict validation: PASS.
- Selected Pint formatting: PASS.
- PHP and JavaScript syntax checks: PASS.
- JSON validation: PASS.
- `git diff --check`: PASS.
- Static build verification: `198 HTML pages`, `11450 references`,
  `0 broken`, PASS.
- LTR and RTL logical panel placement: covered by build tests.
- Disabled reader-preferences path: covered by build tests.

The PHP checks used the ServBay PHP 8.2 runtime because the unrelated Homebrew
PHP installation is missing its ICU library.

## Browser acceptance

Verified on the locally published site:

- the panel opens from the right in the Russian LTR build;
- the Framework overlay remains dark and theme-independent;
- opening locks body scroll and closing restores it;
- System, Light and Dark choices update the document theme;
- Light persists after reload and the selected radio is restored;
- Reset returns to the authored System value and removes the override;
- Escape closes the panel and returns focus to the settings trigger;
- `aria-expanded` follows the open state;
- no horizontal overflow was observed at the tested desktop viewport;
- final browser error log was empty.

During acceptance, a real integration defect was found: `sf-modal` moves or
clones slotted content, invalidating previously captured input references.
The runtime was corrected to resolve controls dynamically and handle
click/change events at the document capture boundary. The corrected behavior
was then re-tested live.

Mobile behavior is covered by the responsive full-viewport panel contract and
automated output assertions; no separate live mobile screenshot is claimed in
this acceptance.

## Publication and rollback

- Exact generated source:
  `docs/site/build_production`.
- Local published destination:
  `/Users/rim/Sites/docara.test/build_production`.
- Exact-tree comparison after publication: PASS.
- HTTP checks for `/`, `/ru/` and `/ru/authoring/reader-settings/`: `200`.
- Pre-publication backup:
  `/Users/rim/Sites/docara.test/.docara-backups/20260724-210404-reader-preferences/build_production.previous`.

## Nonclaims

- No commit, push, pull request, public release or default-branch merge was
  performed by this batch.
- This does not claim that every future reader preference already exists.
  Extensions now have a typed admission path; only theme is enabled initially.

## Post-acceptance correction: fixed dark shadow

The reader reported a light modal shadow in the dark theme. The cause was the
theme-dependent compiled value of the Framework `--sf-ui-shadow-3` token.

The Docara preferences Smart component now sets the public
`--sf-modal-surface-shadow` variable directly. It preserves the Framework
level-3 geometry and size-system variables while using only the fixed
`--sf-black--alfa-8`, `--sf-black--alfa-12` and
`--sf-black--alfa-24` palette tokens. The shadow therefore remains dark in
both light and dark themes.

Correction verification:

- focused builder suite: `35 tests`, `832 assertions`, PASS;
- complete suite: `331 tests`, `5120 assertions`, PASS;
- static verification: `198 HTML pages`, `11450 references`, `0 broken`;
- exact build/publication tree comparison: PASS;
- published CSS contains the fixed modal shadow contract;
- live dark-theme visual check: PASS, no light edge remains;
- `git diff --check`: PASS.

Correction rollback backup:
`/Users/rim/Sites/docara.test/.docara-backups/20260724-2144-fixed-dark-shadow/build_production.previous`.
