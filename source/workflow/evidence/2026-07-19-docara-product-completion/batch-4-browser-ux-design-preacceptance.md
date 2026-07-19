# Batch 4 — browser, UX and design preacceptance

Date: 2026-07-19
Baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Status: `PASS` for the candidate-ready worktree; exact-candidate replay remains
required before closure.

Review modes:

- `$ux`: preference scope, information hierarchy, native semantics, keyboard,
  persistence and responsive acceptance;
- `$designer`: compact composition, Framework-native visual hierarchy and
  removal of nonessential controls;
- implementation constraint: exact pinned Simai Framework components,
  utilities and tokens, with no local replacement design system.

## Product and design judgement

The useful minimum is one setting group, not a general preferences centre.
System/light/dark is a mature documentation pattern; browser zoom remains the
text-size control and author-owned `layout.max_width` remains the layout
contract. The dialog therefore has one title, one short explanation, three
described choices and a contextual reset action that is hidden without an
override.

The result uses progressive disclosure and native controls. It keeps the
header quiet, avoids a save step and does not admit a Smart modal or dropdown
whose additional runtime would not improve the task.

## Browser matrix

An installed Chrome instance exercised a clean production build.

- initial state: author/system preference, light OS projection, no reader
  storage or legacy cookie, reset hidden and no binary theme hook;
- keyboard Enter opens the dialog and focuses the selected native radio;
- dark applies immediately, writes the reader key, projects the compatible
  cookie and reveals reset;
- Escape closes the native dialog and restores focus to the trigger;
- reload and page navigation preserve the explicit choice;
- reset removes local and cookie preferences and returns to the author target;
- explicit system follows live emulated OS dark-to-light changes and has no
  compatibility cookie;
- search and settings replace one another and never overlap;
- 900 px and 390 px: dialog remains inside the viewport with zero page
  overflow;
- trigger and close targets are `48 x 48`; choice rows exceed the 44 px target;
- exact Core radio CSS and JavaScript load from pinned commit
  `7e836d8a9414d5da553fb1ab0404721e5b48769a`;
- no Smart modal or dropdown resource is requested;
- console errors: zero; console warnings: zero; failed requests: zero.

The 390 px screenshot was visually inspected: the hierarchy stays compact,
descriptions remain readable, controls have clear separation and no content is
clipped.

This is preacceptance only. Independent browser/UX/design and tester verdicts
must bind to the exact committed candidate before Batch 4 closure.
