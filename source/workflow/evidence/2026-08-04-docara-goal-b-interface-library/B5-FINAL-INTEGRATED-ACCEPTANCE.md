# B5 final integrated acceptance candidate

Date: 2026-08-05
Status: `ready_for_independent_audit`
Branch: `codex/docara-unified-architecture`
Exact product candidate: `e06ff0c945dafd4e9678794773d8bde83c8de535`
Entry/rollback boundary: `3280a89cc21f2b4fcfc8e7539c673ca62a199446`

This is executor evidence, not independent acceptance. Goal C, merge, push,
tag, release and deploy remain unauthorized.

Product checkpoint commits after the Goal A handoff:

- `2ab17d8` — consume the accepted Framework form wave;
- `b82e93a` — bind discovery contracts to the Framework wave;
- `8dccc89` — protect immutable Framework artifacts from formatting;
- `cf542a1` — publish Framework Smart assets at the canonical mount;
- `c7b4c45` — fail closed on Framework packet mutations;
- `234a54a` — document the accepted Framework form wave;
- `327d038` — bind portable Framework assets to the build receipt;
- `e06ff0c` — retain the complete Smart asset receipt on single-page builds.

## Exact accepted owner inputs

- form wave `ui.input`, `ui.dropdown`, `ui.checkbox`: owner product
  `7e0b87187ceb1f89fad730094bcc4aada3e4f3f2`, packet content SHA-256
  `83551f972ad0b1a6e2037f61583769e32a4a78081e01ed0a0fe888b1187baca1`;
- `ui.list-item`: owner product
  `639d7b67833cfdf1e2c349c5f83669ba0e34fe05`, owner handoff
  `95932c20f6d5bbe12fc27d3870b75161cd395fa4`, packet content SHA-256
  `7dbcb161e8bb48c342a385c3f28f7dc8628eecdf0c09758ab3113eb8dc2107db`,
  packet file SHA-256
  `008e5d4c7a6afc7b0dc99b39c6bfe9c328627391116f84793d79cbc91346c5a6`,
  artifact tree SHA-256
  `6029ca7c9ded244d5d4326d526dd9d91396551cf595ce872bc95119fda90a47c`.

The list-item scope is deliberately narrow: `type=text` as an admitted direct
child of `ui.dropdown`. Icons, avatars, tags, standalone list-item support,
raw option markup and a local `items` dialect are not claimed.

## Runtime outcome

The unchanged artifacts are admitted by one content-addressed lock and the
existing `FrameworkLockSmartProvider -> SmartRegistry ->
SmartComponentGateway -> SmartRenderer` path. The populated product
configurator resolves three text `ui.list-item` children through the same
Gateway. The install builder uses the admitted input and checkbox controls.
No component-ID/namespace dispatch, second dialect, parser, renderer, registry,
Gateway, LayoutComposer or PageBuilder was added.

Framework portable assets are hash-checked before render, published only when
used at the canonical `/_docara/framework/smart/` mount and recorded in the
build receipt. The static verifier independently recomputes this projection.
Single-page build retains the accepted complete diagnostics/asset ledger.

Permanent negative tests cover artifact-byte mutation, symlink and traversal,
receipt-hash mutation and published-asset-byte mutation. Existing namespace,
container, duplicate, path, hardlink and hash-bound write gates remain green.

## Test and quality results

Commands use ServBay PHP 8.4.20.

- full PHPUnit: `467 tests, 8699 assertions`, exit 0;
- exact cross-host plus Framework wave plus project demos: `11 tests, 153
  assertions`, exit 0;
- Framework/project security-focused: `10 tests, 109 assertions`, exit 0;
- static verifier and portable asset receipt focused: `24 tests, 234
  assertions`, exit 0;
- partial-build retention plus multilingual/static focused: `18 tests, 191
  assertions`, exit 0;
- Pint: PASS;
- Composer validate strict: PASS (tool-owned PHP 8.4 deprecation notices only);
- PHP lint: 387 tracked PHP files, PASS;
- JSON parse: 525 tracked JSON files, PASS;
- YAML inventory: 36 tracked files; project schema/context checks pass;
- `git diff --check` and candidate-range diff from the Goal A handoff: PASS.

Exact cross-host rendering retains the accepted neutral Portable Smart ABI and
empty render stderr/warnings.

## Public build, single-page and static verification

Two new clean-root builds from the exact product candidate each contain 104
routes, 307 files and 208 HTML files. Both static verification runs report
21,844 local references and `broken=[]`.

The complete tree digest for both full builds and for the representative
`/ru/components/alert/` single-page rebuild is:

`7771142ea3820538be684ee9ee38e42360826add3dd5eb7e2068c9ac9fdf701a`

Digest algorithm:
`sha256(sorted("<file-sha256>  <relative-path>\n"))`.

Default public output remains unchanged because the Framework controls and
their assets are usage-driven project demonstrations. The former partial
candidate `ccb076a…` and ledger `417ebc62…` remain historical pre-wave
evidence, not current proof.

## Browser and accessibility evidence

The exact project demo fixture passed eight desktop/mobile x light/dark x
LTR/RTL scenarios. Every scenario had local assets 200, zero console
errors/warnings, zero bad responses, no horizontal overflow and reduced-motion
support. The populated dropdown hydrated three admitted `.sf-list-item`
options, selected `Командный`, and the configurator checkbox changed the total
from `2500 ₽` to `3700 ₽` with synchronized native/host checked state.

Project-demo screenshot SHA-256 values:

- desktop dark LTR: `98308d8e7048cb4bd11be636630210d2ec000192c60b14163f5b3e80ce38d58c`;
- desktop dark RTL: `86bcda38ab4c3f69cd572c4175e5e777e7227e527dd00018cd4702c018fa8d44`;
- desktop light LTR: `eda88a185eb053ab31d24db392060d620055801e4b0bbd767383017abebe4deb`;
- desktop light RTL: `81211dadd44d21f47f4c1517cabf592050e31697901656b51f33be2a5da73df8`;
- mobile dark LTR: `8c4c08dd81c863ce7408af51dd57403c9199d978a47f2292d6af7c758496405c`;
- mobile dark RTL: `173a6c235e44e95019a03254f29cac5511336382f09111ccf0a6c85561fe954e`;
- mobile light LTR: `cc32f35dda054b5e68890485ba12ae818b6248f5afcf584d4ea5abc3c09f0b14`;
- mobile light RTL: `8cb14a52e90d354fb333d2095a004989054f8f6611be81a4206e6efd5567eb05`.

The exact public Alert shell also passed eight scenarios: search/settings open,
Esc close and focus return, keyboard, light/dark, LTR/RTL, desktop/mobile,
reduced motion, zero console/network errors and no overflow. The final check
waited for the declared 400 ms panel transition; an earlier 80 ms observation
is not used as acceptance evidence. The overlay remains
`backdrop-blur-none`.

Public-shell screenshot SHA-256 values:

- desktop dark LTR: `8eedb564bb2b170207b2084f2697b68e1656ab25ae6d434a0c2a773887109a7f`;
- desktop dark RTL: `07b6b76c74bd905a4582a3238c930b250491aea15add7ad1b57032e20940eeec`;
- desktop light LTR/RTL: `086e26f3883958c4e49b0eb4c6f99dd76a68d000b23fff68958ed4dbaa8c20c3`;
- mobile dark LTR: `822c8f6370af7b320f2fd94363eda56bf19de99459f1909357d0f56d8b10a664`;
- mobile dark RTL: `2187d04bc5b09673ebbc276649604ad646abe6d7f0d4ce5e6bbad67a687ab8b6`;
- mobile light LTR: `aa9be2a200c593b8728c215966964b617a069e630e6a0d82709d9c9e2620c814`;
- mobile light RTL: `ba05c410f88207848e3bec0b9c9d6e9aff3270a26482559c984bd60faf60d8cd`.

Screenshots are evidence only; the runtime artifacts and reports remain the
source of truth.

## Deterministic package and fresh consumers

Two independent clean clones at the exact product candidate produced identical
809-file unpublished packages:

- ZIP SHA-256:
  `1dea7df79187f63e99e24eb2e4a5782ecf826afccc16a5d8fff6516e23de9145`;
- release manifest SHA-256:
  `af9012a2668189b4029647521e4aeabced09d83eef572ca79c792a8cfede3dfd`;
- checksums SHA-256:
  `0e0e50d197918966d74b96058ab9aea2c9d34b7bc67f11e809b9d87d5fb25080`.

The repository verifier passed both packages. Two fresh Composer dist
consumers used the same lock SHA-256
`8cc627cd0517485a112bb29964449dd02d67498d6f63614f0f9bdf307ffae90f`,
contained neither package `.git` nor `node_modules`, and produced identical
196-file / 78-HTML trees. Full and `/ru/project-demos/` single rebuilds share
ledger:

`79d2ada09d93b1d16cb322720422f78ec200fa664e2b85bfb7829d2c6cc57ce4`

Static verification checked 3,931 references with `broken=[]`. Composer audit
reported no known security advisories.

## Rollback and nonclaims

Rollback is the immutable Goal A handoff `3280a89…` or the pre-wave Goal B
candidate `ccb076a…`; remove the exact Framework lock/artifacts and revert the
listed Goal B product commits as one bounded unit. No legacy surface was
removed without zero-reference proof.

This candidate does not accept related list-item icon/avatar/tag surfaces,
does not start Goal C, and does not authorize merge, push, tag, release,
publication or deployment.
