# Batch 4 exact browser / UX / designer retest

Date: 2026-07-19
Candidate: `4f901507186afa3f5582cc2bb4754148f0df2a5b`
Tree: `9cea3278a558677e8ff32371de1ae43796b939e9`
Baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Rejected parent: `02cc2618c89b6c19391165feee9439fe3b94601c`
Verdict: `CORRECTION_REQUIRED`

## Corrected behavior that passed

The earlier Core-versus-Docara theme-ownership defect is corrected. The exact
candidate emits `SF_BOOT_CONFIG.theme=false` before pinned Core loads. Fresh
browser checks passed for author `system`, `light` and `dark`, opposite OS
themes, live OS changes in system mode, inherited site/section/page defaults,
persistence, navigation, reload, Reset and cross-tab synchronization.

The remaining matrix also passed Enter, native radio arrows, Escape and focus
return; search/settings mutual exclusion; 1440, 900 and 390 px layouts; target
sizes; light/dark contrast; transitions; logo switching; exact pinned assets;
absence of the binary theme hook and unadmitted Smart modal/dropdown; and zero
request failures. Outside the forced complete-storage-denial case, console and
page errors were zero.

## Blocking findings

### `FRAMEWORK-STORAGE-001` — P0

The candidate was opened in an unmodified Chrome process with
`--disable-local-storage`. In that real browser mode `window.localStorage` is
`null`. Pinned Core then throws an uncaught promise error in
`ensureCacheVersion -> init` while reading `SF_CACHE_VERSION` from
`localStorage`. Framework initialization remains incomplete, `body` retains
`opacity: 0`, the page is blank and the Close icon is not initialized.

Required correction belongs to the Framework loader: route cache reads,
writes, removals and scans through a guarded storage boundary; continue through
a no-persistent-cache or in-memory path when storage is unavailable; cover at
least `ensureCacheVersion`, `ensurePluginListVersion`, `checkForCache`,
`clearCache` and `clearAllSfCache`; and guarantee that a loader failure cannot
leave the document hidden. Docara must consume a newly verified exact Core
revision rather than disguise the failure with a page-specific monkeypatch.

### `UX-SETTINGS-FOCUS-001` — P1

After Enter opens the settings dialog, Shift+Tab moves native focus to the
Close button and `:focus-visible` is true, but the focused and blurred visual
states are indistinguishable. The bounded Docara correction is an explicit
`data-docara-reader-settings-close` hook in the existing focus-visible group
with the Framework primary token, a 3 px outline and a 3 px offset. A reusable
`.sf-icon-button:focus-visible` rule is also an upstream Framework gap.

## Evidence boundary

Raw disposable evidence was produced under
`/tmp/docara-batch4-exact-retest-evidence/`, including the full verdict,
machine-readable checks, native Chrome storage-disabled probe, screenshots and
repeatable browser scripts. The reviewer changed neither repository state nor
readiness.

Batch 4 remains open. This verdict makes no public release, production,
default-branch or wider-Goal claim.
