# Standalone Docara verification

Date: 2026-07-18
Scope: local non-release portable-format candidate
Base: `v1.3.65` / `ba7724ae3d9e2b99388098637b81a35a2646e6a4`

## Implemented outcome

- strict `docara.json`, inherited `_section.json`, and optional page sidecars;
- schemas `docara.site.v1`, `docara.section.v1`, `docara.page.v1`,
  `docara.component_call.v1`, and `docara.framework_lock.v1`;
- deterministic merge and explainable `ResolvedPagePlan` provenance;
- one fixture with nested documentation, a landing page, and real
  `ui.alert`/`ui.button` calls;
- exact Simai Framework Core commit and verified local Smart asset projection;
- `docara init --portable` plus legacy `.settings.php` compatibility.

## Immutable Framework evidence

- Core: `simai/ui@7e836d8a9414d5da553fb1ab0404721e5b48769a`.
- Smart source: `simai/ui-smart@dd786bbae98391fb21df9b4e1e6cd402ead0614c`.
- Runtime pair: `sf-v5.3.2-7e836d8a-dd786bba` (immutable upstream lock
  identifier).
- Alert asset SHA-256:
  `e994066dd2a7f9c4d15c573ea66bb47ccb0f12c24f4cf2e7dedee29eaddf9f1c`.
- Button asset SHA-256:
  `fe977fc7c608b7bacb79b7641a302c30a6195659ac2351594ae5aef0656d0a27`.
- Icon asset SHA-256:
  `c810be681b51f98002e01fb8852e992e454fa607af005033f9cc10309016fa09`.
- Smart URLs use one deterministic `sf_v` derived from the runtime pair and
  canonical projection hash. Core's native loader loads each local Smart file
  once; there is no second component loader or Smart CDN fallback.

## Automated verification

Focused portable command:

```text
/Applications/ServBay/package/php/8.4/8.4.20/bin/php vendor/bin/phpunit --do-not-cache-result tests/Unit/FrameworkComponentRuntimeTest.php tests/PortableSiteBuilderTest.php tests/PortableInitCommandTest.php tests/Unit/PortableConfigurationTest.php
```

Result: `PASS`, 51 tests / 328 assertions.

Complete repository command (serialized, UTC):

```text
TZ=UTC PHP_INI_SCAN_DIR="/Applications/ServBay/package/etc/php/8.4/conf.d:/tmp/docara-php-ini" PATH="/Applications/ServBay/package/php/8.4/8.4.20/bin:/Applications/ServBay/bin:/usr/bin:/bin:/usr/sbin:/sbin" /Applications/ServBay/package/php/8.4/8.4.20/bin/php -d date.timezone=UTC vendor/bin/phpunit --do-not-cache-result --no-progress
```

Result: `PASS`, 322 tests / 996 assertions, 1 minute 56.593 seconds.

Additional gates:

- Pint `--test`: PASS.
- Composer `validate --strict --no-check-publish`: PASS (the installed Composer
  emits PHP 8.4 deprecation notices, exit code remains 0).
- Every JSON file under `resources`, `stubs`, and workflow evidence: PASS via
  `jq empty`.
- `git diff --check`: PASS.
- Empty-directory `init --portable` and `build local`: PASS; no `.env`, legacy
  `source/_core`, or Node dependency was created.
- Repeated build tree hashes: identical.
- Bounded pre-commit reverse review: PASS after regression coverage for mixed
  legacy/portable markers, lexical root symlinks, and JSON object/list type
  stability in the published diagnostics.

## Browser verification

Server: disposable `/tmp/docara-portable-smoke/build_local` on
`http://127.0.0.1:8765`.

Verified outcomes:

- root `sf-alert` and nested/landing `sf-button` are defined and hydrated;
- icon custom element is defined, its full pinned font is ready, its inner
  glyph is `24x24`, and the screenshot shows the actual info symbol;
- Core and local Smart requests return 200; local `alert.js`, `icons.js`, and
  `buttons.js` use the projection-aware exact cache version;
- console: 0 errors, 0 warnings;
- desktop and mobile: no horizontal overflow;
- mobile documentation collapses to one column and a static sidebar;
- the theme button persists and applies light/dark state.

Screenshots:

- `browser-desktop-light.png`
- `browser-desktop-dark.png`
- `browser-mobile-light.png`
- `browser-mobile-dark.png`

## Nonclaims and open gates

- This is a local non-release candidate, not production or ecosystem readiness.
- The inspected `ui-smart` revision has no license file. Do not publish, tag,
  distribute, or release the bundled Smart bytes until the owner/legal
  redistribution decision is explicit.
- Simai Framework Core remains an exact-revision network dependency; a fully
  offline build requires a separately accepted Core projection.
- Larena cross-repository parity and independent exact-candidate acceptance
  remain required before the goal can close.
- `docara-template` and `docara-mix` remain untouched and must not be archived
  until consumer migration and separate acceptance are complete.
