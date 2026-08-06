# S2 Hero background implementation evidence

Date: 2026-08-06
Status: `ready_for_independent_audit`
Entry product: `ac53ea4d372a47dc8278b595accca9e7b85c66a3`
Exact product candidate: `794fac076be86ed4d03167120800ab0e91715aff`

## Outcome

The existing semantic `docara.hero` now accepts the closed
`media=auto|side|background|none` contract. Absent `media` and explicit
`media=auto` render the exact accepted S1 bytes. `side` keeps one meaningful
ordinary image and requires the split presentation. `background` consumes one
empty-alt local Markdown image and delegates decorative media, overlay and
content geometry to `SurfacePresentation`. `none` rejects authored media.

The admitted background fields are `background_fit=cover|contain|auto`,
`background_x=left|center|right`, `background_y=top|center|bottom`,
`overlay=light|dark` and `overlay_strength=soft|medium|strong`. They are rejected
outside background mode. Unknown props, image-count/alt/presentation conflicts
and unsafe local assets fail closed with exact source path, line and column.

## One production path

The implementation remains:

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilder
```

There is no authored Surface wrapper, second Hero/background renderer or
component-ID dispatch. Local background media uses the normal public asset
receipt. The final bounded correction strips the internal publication marker
from public HTML while preserving the build receipt and deterministic copy.

## Permanent regression proof

`tests/Unit/HeroMediaRuntimeTest.php` covers exact default parity, side,
background and none semantics, shared Surface structure, exactly-one image,
overlay/focus/accessibility behavior, prop combinations, source locations and
local-asset security. `tests/Unit/PortableSiteBuilderTest.php` proves a
root-owned local asset is published once and a conflicting locale-owned target
is rejected before overwrite.

The accepted S1 default Hero HTML SHA-256 remains
`886d1e4b0b2066004431427c1f69e2b0d34b2ce71b4960db16ebf1e667cb9684` for
both omitted `media` and explicit `media=auto`.

## RED to GREEN local-asset receipt

An AR-only production-path fixture exposed that validation admitted
`/assets/docara-screen.png` but a build without a locale duplicate did not
publish it. Commit `07aa374` moved that dependency into the generic artifact
receipt and publication path; commit `794fac0` removed the internal receipt
marker from public HTML. The AR output now publishes the exact source bytes at
SHA-256 `f3192e...`; traversal, remote/data/protocol, symlink, hardlink, case
collision and output collision remain rejected.

## Bounded commits and rollback

- `94f2f20` records the independently accepted S1 entry and launches S2.
- `071b12e` implements the typed Hero media contract and shared Surface use.
- `07aa374` publishes admitted local media through normal build receipts.
- `794fac0` keeps internal asset receipts out of public HTML.

Rollback is the ordered revert of `794fac0`, `07aa374` and `071b12e` to the S2
launch boundary `94f2f20`; accepted S1 product `ac53ea4…` remains the immutable
behavioral baseline.

## Nonclaims

S2 does not change homepage art direction, migrate Showcase/Promo, begin S3,
write an external repository/site, or authorize merge, push, tag, release or
deploy.
