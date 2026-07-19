# Batch 4 — correction of theme ownership

Date: 2026-07-19
Rejected candidate: `02cc2618c89b6c19391165feee9439fe3b94601c`
Status: correction implemented; new exact candidate pending

## Why the first candidate was rejected

Independent exact HCS, tester and browser/UX/design reviews returned
`CORRECTION_REQUIRED`. The exact pinned Core runs its binary theme bootstrap
after Docara's early resolver unless `SF_BOOT_CONFIG.theme === false`.

Reproduction on the rejected candidate:

- author default: `dark`;
- emulated operating-system preference: `light`;
- reader storage and compatibility cookie: empty;
- Docara first applied `theme-dark`;
- pinned Core later replaced it with `theme-light` while the selected radio and
  Docara dataset still said `dark`.

Rejected-candidate evidence:

- tester verdict: `/tmp/docara-batch4-exact-02cc2618/verdict.md`;
- tester runtime trace:
  `/tmp/docara-batch4-exact-02cc2618/runtime-conflict.json`;
- browser/UX/design verdict:
  `/tmp/docara-batch4-exact-acceptance/verdict.md`;
- visual defect:
  `/tmp/docara-batch4-exact-acceptance/author-dark-os-light-defect.png`.

The same review also found that a denied `localStorage` write was reported as
saved even though the effective radio could fall back to the author value.

## Correction

- `FrameworkAssetPlanner` now sets the pinned Core boot option `theme: false`;
- Docara remains the single tri-state resolver but continues to render only
  exact Framework theme classes and tokens;
- denied persistence keeps a volatile current-page override so theme, radio
  and reset remain coherent;
- the status reports that the browser did not allow persistence;
- a successfully persisted choice clears the volatile fallback;
- storage events clear any volatile fallback and synchronize the effective
  preference across tabs;
- documentation now describes compatibility-cookie projection accurately.

## Test-first evidence

The Core ownership assertion was added first and failed on the rejected
candidate because `"theme":false` was absent. The guarded-storage assertions
were then added first and failed because no volatile/result contract existed.
After the corrections, the focused pair passes with `2 tests` and `275
assertions`.

## Browser correction preacceptance

Disposable builds with opposing author and OS settings returned `PASS`:

- author dark + OS light: final class and selected radio are dark;
- author light + OS dark: final class and selected radio are light;
- explicit reader light persists and projects the compatible cookie;
- reset restores author dark and survives reload;
- navigation from root author dark to inherited section light and back applies
  each current author default;
- another open tab receives the persisted dark preference and updates its
  class, radio and reset state;
- denied local storage + system choice keeps system selected, exposes reset,
  applies the OS theme and announces that persistence was unavailable;
- reset from that volatile choice restores the author dark default;
- the exact Core boot asset contains `"theme":false` in every case.

The corrected 390 px screenshot after the Framework transition is:
`/tmp/docara-batch4-correction-storage-denied-390.png`. Its radio labels and
descriptions have normal light-theme contrast; no local color override was
added.

This is correction preacceptance only. The rejected candidate remains
rejected, and all independent exact gates must run again on a new immutable
candidate.
