# Docara Framework consumer projection

This directory is a bounded, consumer-verified projection for portable Docara
sites. It contains byte-identical copies of the accepted
`larena/ui` manifests for `ui.alert` and `ui.button`, the exact accepted Simai
Framework runtime lock and an exact revision-scoped Smart runtime projection
from the published Framework pair
`ui@d328491bc805439f200866f7e04c0e5e853a4998` /
`ui-smart@9e94abc6b10c82f770820807343cecb9a0e27f77`:

- `smart/alert/js/alert.js` —
  `8a755992504633901ac986e6b2d2be5e19591724f6b6eb4e6be1b52374c9f583`;
- `smart/buttons/js/buttons.js` —
  `43078e2958646216ded7e73893c7952c4a49caf75d929e09e76a4107f3bc4e6c`;
- `smart/icons/js/icons.js` —
  `84800076a4a6d99274189114831797ac71a895d332661672f59ba50a3a9002eb`;
- `smart/modal/js/modal.js` —
  `275a848667c001d9e4ce357723ee6325ac6fe45db9866fe2294ca10d0fb2daa7`.

The four shell files form the eager `asset_projection`. All other conventional
unminified Smart entrypoints are recorded separately in
`dynamic_asset_projection` and published without being loaded eagerly, so the
browser Loader can resolve elements created after initial HTML planning. Exact
bytes live in `runtime-smart/<revision>/`; the legacy portable Smart ABI under
`assets/` is intentionally not overwritten.

Project sites repeat the hashes, source revision and manifest provider revision
in `docara.framework_lock.v1`. A build verifies the bytes, copies them to the
reserved `_docara/framework-runtime` namespace and appends one projection-aware cache
version to each URL. Core and Smart are taken from the
immutable published UI `v5.7.0` / Smart `v5.5.0` pair; there is no moving
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

The projected bytes belong to the owner-published Framework 5.7.0 release and
remain tied to its immutable Core and Smart revisions.
