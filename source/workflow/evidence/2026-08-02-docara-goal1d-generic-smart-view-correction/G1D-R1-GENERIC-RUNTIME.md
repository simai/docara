# G1D-R1 — generic Smart view resolution

Date: 2026-08-02
Input revision: `c5ea85f8d25deff99b671486fdc4d1e820a86491`
Implementation revision: `44acc1ff91233fa78140222fcb0589bf55b65ca0`
Runtime commit: `c891820`
Formatting commit: `44acc1f`

## Correction

`DeclarativePageCompiler::defaultCompositeView()` and its `docara.brand`
branch are removed. The compiler passes an optional authored view plus a named
preset supplied by the generic binding. Product, Framework and portable
provider resolvers use one rule:

1. an explicit view wins;
2. otherwise a selected preset may name a registered view;
3. otherwise the registered `default` view is used.

The compiler does not inspect a Smart component ID. `SmartCallNode` permits an
empty unresolved view; each resolver produces a non-empty validated view before
creating the resolved plan.

## Generic extension proof

`tests/fixtures/smart/variant/variant.card` is a provider-local component with
`default` and `compact` views and a `compact` preset. The behavioral regression
proves preset-selected view, explicit-view precedence and fail-closed unknown
preset without adding a component-specific branch under `src/`.

Branding uses the same mechanism. The existing `full`, `compact`, `logo` and
`text` modes resolve respectively to the registered `default`, `compact`,
`logo` and `text` views. Public HTML remains unchanged.

## Structural scope

`SmartGenericRuntimeTest` now discovers bundled component IDs from
`SmartRegistry` and scans the active central compiler, parser, plan resolvers,
provider registry, Gateway, renderer, search and admission sources. It also
rejects the retired `defaultCompositeView` helper. The broad source scan has no
component-ID match in the accepted Goal 1 scope.

The one remaining `ui.alert`/`ui.button` allowlist is confined to
`RegionCompositionResolver`. It is the explicitly documented Goal 2 Design
Registry boundary and is not claimed by Goal 1.

## Contract pin

The independently recomputed `git show <pin>:<path>` SHA-256 values match
`resources/contracts/sf5/smart/v1/source.json`:

| Upstream blob | SHA-256 |
| --- | --- |
| manifest schema | `9d65a9b3d63567ef8a12dd43f5c3e24913e2659105b088778dc50476a9578037` |
| view schema | `f7592ddd3c1fabf8ed9a6f32984f8745f0e4f031b50ab1b15617f093ab26ffdc` |
| preset schema | `cbaa993e005a710a79a0dce4c2cd41063d8fc8da6cd4b01b9ea1ee6d039cea5c` |
| `Smart.php` | `5052fad560faf71766a52ed2402266d8ef5f64c3467b6d4294b9763a766fae9a` |
| runtime proof | `bf7276a9fff990b40047d9155c5ba4c7b24fc44359d87e1c95125f3263663221` |

Pin: `b3cdff87563ff78e7eddf044048a4b298fc69036`; tree:
`5b1e61012d7f3aa7202ec71b368dec9730d94bc8`.

## Rollback

Revert Goal 1-D commits in reverse order. No history rewrite, external owner
write or legacy deletion is required. Candidate `c5ea85f8…` remains the
rejected rollback boundary and must not be restored as an accepted result.
