# Docara Framework consumer projection

This directory is a bounded, consumer-verified projection for portable Docara
sites. It contains byte-identical copies of the accepted
`larena/ui` manifests for `ui.alert` and `ui.button`, the exact accepted Simai
Framework runtime lock and an exact revision-scoped Smart runtime projection
from the accepted Framework pair
`ui@122b478ab68d8d7be02febe4a87016eb677a69e8` /
`ui-smart@1de6c70ed455fa2d4d568795452b63431fdd73a1`:

- `smart/alert/js/alert.js` —
  `6d0d710cc655135d7845528a2f10ec2d93a9bb1bb40112a9fc8771c7b2ea1da3`;
- `smart/buttons/js/buttons.js` —
  `34ab330dd760390f61492785c29f2970a624ba05886de20396455240ef7e0e46`;
- `smart/icons/js/icons.js` —
  `f5e2e71aa72416158db4d13cfae945ad6c1a6b7620f3f36353a4972d478ed64d`;
- `smart/modal/js/modal.js` —
  `92a56cbfbd7dcac4c102a6eb9b6d936fe7e8c16fb2d7fa0cb90339aad8727903`.

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
immutable accepted UI `v5.7.0` / Smart `v5.5.0` candidate; there is no moving
`main`/`latest` fallback.
The local consumer adapter preloads a package-owned outlined shell subset and
keeps exact full Material Symbols Outlined, Rounded and Sharp fonts as lazy
offline fallbacks. It does not call the mutable icon-subset service.

The projection supports only the named consumer components. It does not claim
standalone production readiness or readiness of every Framework component. It
is not a new component registry, a moving release channel, or an independent
source of Framework truth.

The projected bytes remain tied to the exact Core and Smart revisions above.
