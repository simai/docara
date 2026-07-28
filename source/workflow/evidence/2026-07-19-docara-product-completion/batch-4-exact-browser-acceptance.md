# Batch 4 — exact browser, UX and visual acceptance

Date: 2026-07-19
Candidate: `d26fa66c6d6a5a36ec113288e6fce29f2f6b1a0e`
Tree: `aa20ad5d0d95f82149a30d189dd5fa9d78163d4a`
Verdict: `PASS` for the bounded exact browser slice

## Immutable input

The native-Chrome runner used the frozen exact-candidate build at
`/private/tmp/docara-b4-d26fa66-browser-acceptance/build_snapshot`. It contains
51 files and matches the independently built exact candidate with canonical
digest:

`9cf966409f87a568fbd6a79efc12c6922369dc5ce5fe92adcbdff074e297e67f`.

The same digest and file count were recorded before and after the browser run.
The product build was not modified by acceptance.

## Excluded harness attempt

Attempt 1 is excluded as a harness false failure. It required
`:focus-visible` after a synthetic reverse-trigger event. A programmatically
dispatched click must move focus correctly, but is not required to receive the
keyboard-only focus-visible modality.

- excluded checks SHA-256:
  `34748cb6132cc89572b2c5ce8db490681cce99e9e820c85286d1ff33e8f7f08e`;
- excluded runner SHA-256:
  `05760add0c7ae892e5878fcf512484ae0fb9e11e03b98a0074fedc4de007488f`.

Only that invalid harness assertion was removed. All real keyboard
focus-visible, modal, geometry, theme, storage and runtime assertions remained.

## Accepted matrix

Native Google Chrome passed all 16 unique scenarios:

```text
2 storage modes × 2 viewports × 2 themes × 2 shortcuts = 16/16 PASS
```

- storage: native normal storage and native `--disable-local-storage`;
- viewport: 1440 × 1000 and 390 × 844;
- theme: light and dark;
- shortcut: physical Playwright `Meta+K` and `Control+K`;
- additional physical keys: Enter and Escape;
- initialization overrides: none (`addInitScript=false`).

Every scenario confirmed:

- settings and search are mutually exclusive;
- the shortcut opens search and focuses its input;
- real keyboard paths receive `:focus-visible`;
- Escape closes the active modal and returns focus to the correct trigger;
- the open dialog remains inside the viewport;
- horizontal page overflow is zero;
- all 25 Framework icons are upgraded;
- the system theme is correct on cold load;
- disabled storage uses the honest in-memory fallback and returns to the site
  default after reload;
- browser console problems and failed requests are zero.

Accepted raw artifacts:

- runner: `/tmp/docara-b4-d26fa66-browser-acceptance.js`, SHA-256
  `dbafe095f3f7744989baaae77cad15fac6f33dc0115b63e08805c8a3438e28a1`;
- checks: `/tmp/docara-b4-d26fa66-browser-acceptance/checks.json`, SHA-256
  `beb9d74d34be287eb05bf32aa0c50cf04f834db8c66466873909dbd3d6690cf7`;
- 16 screenshots under
  `/tmp/docara-b4-d26fa66-browser-acceptance/`.

## Independent review

An independent evidence audit recomputed source/snapshot digest and recursive
equality, confirmed the complete unique matrix, keyboard events, focus,
mutual exclusion, storage behavior, zero runtime failures and preserved
attempt-1 hashes. It returned `PASS_WITH_NOTES` with no blocking defect.

An independent screenshot-only visual review inspected all 16 screenshots and
returned `PASS`: no clipping, overflow, overlap, mixed theme, broken icon or
dialog-placement defect was found at either viewport. P0–P3 findings: zero.

The reverse search-to-settings controller invariant uses a synthetic
`dispatchEvent('click')` because a background trigger is intentionally inert
while a native modal is open. This is not presented as a real user pointer
path. The required user path — settings to search through physical
`Meta/Control+K` — is real keyboard input and passed.

This evidence closes the exact browser gate for Batch 4 only. It does not claim
local publication, public release, production, ecosystem or wider-Goal
readiness.
