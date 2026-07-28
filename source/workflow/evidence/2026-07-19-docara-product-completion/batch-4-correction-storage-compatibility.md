# Batch 4 — storage-denial compatibility correction

Date: 2026-07-19
Rejected candidate: `4f901507186afa3f5582cc2bb4754148f0df2a5b`
Accepted baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Status: candidate-ready correction; independent exact acceptance pending

## Confirmed defect

The exact pinned Core revision
`7e836d8a9414d5da553fb1ab0404721e5b48769a` reads `localStorage` directly
during loader initialization. Native Chrome `--disable-local-storage` exposes
`window.localStorage === null`; Core then throws in
`ensureCacheVersion -> init`, leaves the body at opacity zero and does not
finish Framework or Smart-component initialization.

This is stronger than a denied write for the Docara reader key. The bounded
tester matrix for `4f901507…` passed the latter, while the independent browser
gate found the complete-origin denial and correctly kept Batch 4 open.

## Upstream audit and boundary decision

A proper Core fix remains the target architecture: all cache operations should
use a guarded internal storage adapter and continue without persistent cache.
It cannot be released safely inside this Goal because the current `ui-loader`
checkout does not reproduce the pinned distribution:

- its build pipeline depends on an external, unversioned builder path;
- no dependency lock fixes that builder input;
- source and current distribution already differ in the icon-manifest runtime;
- the active Goal explicitly excludes a public Framework release and new tag.

Editing generated or minified `ui` bytes manually was rejected. A new Core pin
must wait for the separate Framework control-plane and reproducible-release
workstream.

To keep the fixed Core revision unchanged while preventing a blank Docara
site, the bounded integration correction adds one Docara-owned boot asset:
`docara.framework.storage.compatibility`. It runs before Docara's theme
bootstrap and before Core, probes the native storage and does nothing when it
works. Only when storage is null or unusable does it install a page-lifetime
Storage-like cache implementing `length`, `key`, `getItem`, `setItem`,
`removeItem` and `clear`.

The fallback is explicitly marked as volatile through
`data-docara-framework-storage="memory"`. Reader settings do not count writes
to it as persistent, so the interface keeps the current-page override and
reports honestly that the browser did not save the choice. Reload discards the
fallback values. No session storage, account, server, database, moving asset
reference or second theme system is introduced.

## Focus correction

The native Close button now has the explicit
`data-docara-reader-settings-close` hook and participates in the existing
Framework-token focus-visible rule. Browser preacceptance computes a solid 3 px
primary outline; the final offset is the Framework icon-button value of 1 px.
The focus remains clearly visible in both themes and both tested viewports.
This is a bounded Docara accessibility correction; a reusable generic
icon-button focus rule remains a Framework proposal.

## Test-first and current verification

- storage-plan and rendered-order assertions failed before implementation;
- the Close focus hook assertion failed before implementation;
- focused renderer/runtime tests now pass with 313 assertions;
- full PHPUnit: 465 tests, 2382 assertions, PASS;
- Composer strict validation and Pint: PASS;
- production verifier: 44 HTML pages, 4535 local references, zero broken;
- two clean builds are byte-identical with relative tree digest
  `8726cbb745247cc09d2eaa4f179ca84b05187e083432d2fc93de0669ddfb3cbf`.

The earlier feasibility experiment proved native Chrome storage denial,
throwing storage access and normal-storage non-interference against the same
exact Core. A no-injection current-build browser preacceptance also passed:

- native `--disable-local-storage`: visible body, zero page/console/request
  errors, exact Core, 25/25 initialized icons and volatile-memory marker;
- `system` announces non-persistence and disappears after reload;
- normal storage remains a native `Storage` and persists the selected theme;
- 390 and 1440 px, light and dark: visible Close focus, no overflow, dialog
  inside the viewport and controls at least 44 px.

Raw mutable-build evidence is under
`/tmp/docara-current-build-preacceptance-evidence/`. Immutable candidate and
all independent exact gates remain required before Batch 4 closure or local
publication.

## Nonclaims

- no new Framework release or Core revision;
- no Batch 4 acceptance or wider Goal completion;
- no public release, production readiness or default-branch change;
- no authorization to archive `docara-mix` or other repositories.
