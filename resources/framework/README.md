# Docara Framework consumer projection

This directory is a bounded, consumer-verified projection for portable Docara
sites. It contains byte-identical copies of the accepted
`larena/ui` manifests for `ui.alert` and `ui.button`, the exact accepted Simai
Framework runtime lock and four locally projected Smart assets from the
published Framework pair
`ui@202fa232de9ab135127f1ec6e723a3dcf966c879` /
`ui-smart@4dda05d566d774bc90f2631d9c21bd5f26bf082e`:

- `smart/alert/js/alert.js` —
  `4b4005fd348d4121732e3b19bef85a67ca4d121f3efd4146ce5ce4ea130ef901`;
- `smart/buttons/js/buttons.js` —
  `b51493a921b5a63cdccb055b5e5261b89c143790c5ccbf2b99e1055cf63e6fd0`;
- `smart/icons/js/icons.js` —
  `3f014eff4c9176b7586fd8652b500f9505588223072f00b5ed3586320607c52b`;
- `smart/modal/js/modal.js` —
  `c5e4714eb95ae88575fbec27381903c7bd15257a0505fc9568ddb44a2da49a0a`.

Project sites repeat those hashes, the source revision and the manifest
provider revision in `docara.framework_lock.v1`. A build verifies the bytes,
copies them to the reserved `_docara/framework` namespace and appends one
projection-aware cache version to each URL. Core and Smart are taken from the
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
