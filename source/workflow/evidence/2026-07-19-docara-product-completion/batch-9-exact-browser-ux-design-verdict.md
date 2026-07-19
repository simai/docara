# Batch 9 — exact native-Chrome UX/design verdict

Date: 2026-07-19
Candidate: `de87bdef224d518d1c707286d4640be0238d34bc`
Tree: `7c0c20678aff65858e29e9be4dd304ddd44ba17b`
Exact build digest:
`502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`
Verdict: `PASS`

## Exact-source boundary

The visual and interaction matrix used a fresh Git archive and two clean
byte-identical builds under
`/private/tmp/docara-ux-accept-de87bdef.dJ39Qu`. The mutable worktree and
`docara.test` were not used as candidate source and were not changed.

Both builds contain 66 files and 56 HTML pages, have byte-identical canonical
manifests and pass the exact static verifier with 5,793 local references and
zero broken references.

## Rejected-candidate correction

The previous candidate placed the mobile outline target at 111.84 pixels while
the sticky header ended at 127 pixels. The exact corrected candidate measures:

| Viewport | Header bottom | Target top | Hit test |
| --- | ---: | ---: | --- |
| 390 | 127px | 139.84px | target `H2` |
| 768 | responsive header | 140.23px | target `H2` |
| 1440 | 91px | 96.09px | target heading |

Computed mobile scroll margin is 140 pixels. The heading is visible, receives
the hit test and is no longer occluded.

Representative corrected-anchor screenshot:

`/private/tmp/docara-ux-accept-de87bdef.dJ39Qu/screenshots/mobile-390-outline-anchor-fixed.png`

SHA-256:
`d278fb2963dd1d1cc21a32b37885f9cf4ff19b89c46eba967a6d719b9af92e0e`.

## Responsive product matrix

Native Chrome checks passed at 1440, 768 and 390 pixels:

- clear four-level menu hierarchy, active page and ancestor trail;
- keyboard menu open/close, focus restoration and `Escape`;
- breadcrumbs, outline and previous/next reading context;
- exact search query `расширение`;
- landing layout and primary action;
- catalogue query, zero state, reset and generic component detail;
- light, dark and restored system themes;
- beginner, author, migration, maintainer and extension-developer journeys;
- no page-level horizontal overflow.

Visual evidence includes 16 screenshots across desktop, tablet and mobile.
Representative hashes:

- desktop landing:
  `f2450d3ecdedcccd210c94d3ab05ca5d5344b96afce03b5860ab06da274f8747`;
- desktop catalogue:
  `20b5f35b6706a049326f97dc1437e94053226f5f684e6185e8ca1a8859b98a37`;
- mobile four-level menu:
  `f1a802b8d0789558f5dd5ff2c9ea4c263cf488f6f7cf2c6190a367d260ed2ae7`;
- mobile landing:
  `ee21e3724ed5350ddb4589c002f7d64b9528eafe53737f2cc8ce3433aa37dc48`.

The layout remains intentionally simple: one clear navigation zone, one
content job and one contextual outline. Landing cards, catalogue filters and
component examples reuse the pinned Simai Framework presentation rather than
introducing a parallel visual system.

## Console, resource and route diagnostic

A separate bounded fresh-browser diagnostic used the same exact accepted
build:

- `/`, `/development/extensions/`, `/components/catalog/` and
  `/components/catalog/ui.alert/`: zero console errors and zero warnings;
- unexpected failed local resources: zero;
- external resources without data: zero;
- required search, icon and alert assets: HTTP `200`;
- real navigation root → catalogue → `ui.alert`: PASS;
- a unique missing route: HTTP `404` with `text/plain`.

Diagnostic verdict SHA-256:
`98babbc9617249c0f765ede797921ee6886062cee8df952986c391b2f5215955`.
Machine-readable checks SHA-256:
`fa7891e538e260d77f165975c98afe707e42ec6b595dbd65b86b4026ecb69e46`.

A native-Chrome diagnostic event cursor stalled after all UI journeys had
completed. It changed no page or runtime state. The cursor was stopped and the
bounded console/resource/404 checks were repeated in isolated fresh Chrome
sessions. This was browser-control telemetry, not a product defect.

This verdict accepts only the exact candidate's browser, UX and design
surface. It does not itself publish the site or claim public release,
production readiness or readiness of the entire Framework ecosystem.
