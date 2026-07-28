# Batch 9 — exact browser, UX and design attempt 1

Date: 2026-07-19
Candidate: `4164ba2aa890a711b58a2ea016c4f4fbb77ef865`
Candidate build digest:
`e60f6bbea7b59de84184025fe6322781db605067afb60ffbc2ea9cdf48576972`
Reviewer: independent `/root/batch9_browser_ux`
Verdict: `CORRECTION_REQUIRED`

## Accepted scope

The browser reviewer used the candidate's exact archive and two identical
production builds. The following product surfaces passed:

- desktop 1440: themes, four-level menu, exact `расширение` search, catalogue
  filter, component surfaces and reader journeys;
- tablet 768: documentation root, landing, catalogue filtering and responsive
  component behavior;
- mobile 390: documentation root, four-level menu, keyboard/focus path, search,
  reading settings and catalogue.

Both exact static verifiers returned zero broken references. These passing
observations do not override the blocker below and do not accept the candidate.

## Blocking finding

Following the mobile outline link to `Как устроен результат` placed the target
under the sticky header:

- sticky header bottom: `127px`;
- target heading top: `111.8359375px`;
- computed heading `scroll-margin-top`: `112px`;
- `elementFromPoint(33, 116.8359375)`: `HEADER`.

The mobile anchor reserve was therefore 15 pixels smaller than the actual
header. This is a reproducible responsive-accessibility defect, not visual
polish.

Supplemental screenshot:
`/private/tmp/docara-ux-accept-4164ba2.CN1sIW/screenshots/mobile-390-outline-anchor.png`
with SHA-256
`ac55d81afe169a027c5d3e60734555f807f24af49874cdb17a78c010a78e094d`.

The independent review also produced disposable reports:

- `verdict.md` SHA-256
  `0254eb6f1276a2877c5cffb7a5a63b3b57cdfe9e7544c293a4a3e0698154d66e`;
- `qa-report.md` SHA-256
  `b2bb9426989d9088bc7524a4efda40a4eabd15bba5000a3028b0ecd307377b3b`.

## Not completed after the blocker

The reviewer stopped before mobile landing/component-detail completion, the
final console/network snapshot and the browser live-link crawl. Static link
verification had already returned `broken=[]`. The temporary exact server was
stopped and the native Chrome session was retained for the corrected
candidate's retest.

No publication was attempted. The accepted Batch 7 build remained served.
