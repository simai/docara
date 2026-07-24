# Docara Framework consumer projection

This directory is a bounded, consumer-verified projection for the portable
Docara prototype. It contains the accepted `larena/ui` manifest definitions
for `ui.alert` and `ui.button`, rebased only to the exact admitted Smart
revision, the exact accepted SIMAI Framework runtime lock and four locally
published Smart assets from
`simai/ui-smart@84c363daf59dcd62665dae115cc63b0dd7529cb1`:

- `smart/alert/js/alert.js` —
  `32fd607bb1b6cd58911a43cdd143cfab9a0ff9822d423fb97304a2b9cc71c2af`;
- `smart/buttons/js/buttons.js` —
  `4f442e6f61c7278611e98cce5565b5adefa8770849b9b7fc36748cf6219093bd`;
- `smart/icons/js/icons.js` —
  `7618c219901fd6f3fa38f7c8a9c47a5609265197239748b5d64dca15c0419ceb`;
- `smart/modal/js/modal.js` —
  `695c52a086f12f922937a3754d10f561a0b74d622fb7f444ffa88be4b22b1905`.

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
