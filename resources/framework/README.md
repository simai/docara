# Docara Framework consumer projection

This directory is a bounded, consumer-verified projection for the portable
Docara prototype. It contains byte-identical copies of the accepted
`larena/ui` manifests for `ui.alert` and `ui.button`, the exact accepted Simai
Framework runtime lock and four locally projected Smart assets from the
published Framework pair
`ui@d1daa951dd08b94a9f209fd9f31a78d2b3779563` /
`ui-smart@aa9f34a4d2bf421e20970ab4eb0418f017c62059`:

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
The local consumer adapter publishes exact full Material Symbols Outlined,
Rounded and Sharp variable fonts from the source-pinned official Google
projection before exposing Framework icon glyphs; it does not call the mutable
icon-subset service.

The projection supports only the named consumer components. It does not claim
production readiness or readiness of every Framework component. It is not a
new component registry, a moving release channel, or an independent source of
Framework truth.

`ui.alert` with `closable: true` is outside this bounded pair because its
`sf-icon-button` dependency is absent and therefore fails closed.

The inspected `ui-smart` revision has no license file in its source tree. These
bytes are retained only for the local non-release prototype. Do not publish,
tag or distribute a package containing them until the owner has explicitly
approved the redistribution terms.
