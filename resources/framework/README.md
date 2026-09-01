# Docara Framework consumer projection

This directory is a bounded, consumer-verified projection for portable Docara
sites. It contains byte-identical copies of the accepted
`larena/ui` manifests for `ui.alert` and `ui.button`, the exact accepted Simai
Framework runtime lock and four locally projected Smart assets from the
published Framework pair
`ui@ce1735ef3427723cdd356126e24d030272edae86` /
`ui-smart@23d00d92346717b8f835297d142a14458f806602`:

- `smart/alert/js/alert.js` —
  `9fa2e29f067379f8400ee4a5bd0ef34832baee42f5a8394f48796719d07e75fa`;
- `smart/buttons/js/buttons.js` —
  `b9804afcf05c718ed51ee0b8b5e04e946c422d2fb8b8fed112e552824054087b`;
- `smart/icons/js/icons.js` —
  `362cef3368003672166a0a99d5026a1712fe4f716f9e614a55037d2429430da5`;
- `smart/modal/js/modal.js` —
  `a14cc8fca8e803328cc082a6290695ded7c7baf97373a6353b765116a2b89cb5`.

Project sites repeat those hashes, the source revision and the manifest
provider revision in `docara.framework_lock.v1`. A build verifies the bytes,
copies them to the reserved `_docara/framework` namespace and appends one
projection-aware cache version to each URL. Core and Smart are taken from the
immutable published UI `v5.5.0` / Smart `v5.4.0` pair; there is no moving
`main`/`latest` fallback.
The local consumer adapter preloads a package-owned outlined shell subset and
keeps exact full Material Symbols Outlined, Rounded and Sharp fonts as lazy
offline fallbacks. It does not call the mutable icon-subset service.

The projection supports only the named consumer components. It does not claim
standalone production readiness or readiness of every Framework component. It
is not a new component registry, a moving release channel, or an independent
source of Framework truth.

`ui.alert` with `closable: true` is outside this bounded pair because its
`sf-icon-button` dependency is absent and therefore fails closed.

The projected bytes belong to the owner-published Framework 5.5.0 release and
remain tied to its immutable Core and Smart revisions.
