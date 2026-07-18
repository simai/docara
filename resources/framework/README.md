# Docara Framework consumer projection

This directory is a bounded, consumer-verified projection for the portable
Docara prototype. It contains byte-identical copies of the accepted
`larena/ui` manifests for `ui.alert` and `ui.button`, the exact accepted Simai
Framework runtime lock and three locally published Smart assets from
`simai/ui-smart@dd786bbae98391fb21df9b4e1e6cd402ead0614c`:

- `smart/alert/js/alert.js` —
  `e994066dd2a7f9c4d15c573ea66bb47ccb0f12c24f4cf2e7dedee29eaddf9f1c`;
- `smart/buttons/js/buttons.js` —
  `fe977fc7c608b7bacb79b7641a302c30a6195659ac2351594ae5aef0656d0a27`;
- `smart/icons/js/icons.js` —
  `c810be681b51f98002e01fb8852e992e454fa607af005033f9cc10309016fa09`.

Project sites repeat those hashes, the source revision and the manifest
provider revision in `docara.framework_lock.v1`. A build verifies the bytes,
copies them to the reserved `_docara/framework` namespace and appends one
projection-aware cache version to each URL. Simai Framework Core remains an exact-commit jsDelivr
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
