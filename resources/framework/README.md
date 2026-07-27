# Docara Framework consumer projection

This directory is a bounded, consumer-verified projection for the portable
Docara prototype. It contains the accepted `larena/ui` manifest definitions
for `ui.alert` and `ui.button`, rebased only to the exact admitted Smart
revision, the exact accepted SIMAI Framework runtime lock and four locally
published Smart assets from
`simai/ui-smart@daf5f285d00640de8115f0939045828ba473ae1e`:

- `smart/alert/js/alert.js` —
  `6720a3dd126f35c46fc09ecb6aeb0f2d9ebfcce82388ba8cc031c24cead426a7`;
- `smart/buttons/js/buttons.js` —
  `f9d400cd9d88c23243f75b313e9d0040ebee4e12e763d12a5ba86e556cf5c48b`;
- `smart/icons/js/icons.js` —
  `6fe9a1ac7436ba6017addd7c9d389633e1fe4be4ae86cc0cd7fb45c0b31902d1`;
- `smart/modal/js/modal.js` —
  `7ddde60f8a85cc9496685e6d70299d84e67b4cfecde845714ba7e2825b61a045`.

Project sites repeat those hashes, the source revision and the manifest
provider revision in `docara.framework_lock.v1`. A build verifies the bytes,
copies them to the reserved `_docara/framework` namespace and appends one
projection-aware cache version to each URL. SIMAI Framework Core remains an exact-commit jsDelivr
dependency; there is no moving `main`/`latest` or `ui-smart` CDN fallback.
The local consumer adapter waits for the exact full Material Symbols font from
that Core revision before exposing Framework icon glyphs; it does not call the
mutable icon-subset service.

The projection supports only the two named components. It does not claim
production readiness or readiness of every Framework component. It is not a
new component registry, a moving release channel, or an independent source of
Framework truth.

`ui.alert` with `closable: true` is outside this bounded pair because its
`sf-icon-button` dependency is absent and therefore fails closed.

The inspected `ui-smart` revision has no license file in its source tree. These
bytes are retained only for the local non-release prototype. Do not publish,
tag or distribute a package containing them until the owner has explicitly
approved the redistribution terms.
