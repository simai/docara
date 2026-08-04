# A1 — typed BindingRegistry

Date: 2026-08-04
Status: `PASS`
Parent: `193dd10`
Exact implementation candidate: `1fb4b5c6c1cb72c29d61b8f85959966438202474`

## Outcome

The compiler no longer contains `boundProps()` or a binding-ID `match`.
One deterministic `BindingRegistry` accepts provider-owned typed descriptors
and resolves the existing shell calls before the same SmartComponentGateway.

Canonical IDs:

- `docara.branding` — `shell.brand`;
- `docara.navigation` — `shell.primary-navigation` and
  `shell.secondary-navigation`;
- `docara.outline` — `shell.outline`.

The package Section artifacts keep `branding`, `navigation`,
`header_navigation` and `outline` only as explicit internal storage aliases.
They resolve to the canonical descriptors and do not form a second public
contract. New authored/config calls use canonical IDs.

Each descriptor records provider, revision, owner namespace, capabilities,
admitted presentations, binding-owned props, output schema, source and hash.
The registry rejects provider/namespace/binding/alias duplication, wrong Smart
targets, unregistered presentations, owned-prop spoofing and undeclared
resolver output before the Gateway.

## Focused verification

```text
phpunit --filter BindingRegistryTest|DeclarativeViewCompositionTest|
  DeclarativePageCompilerTest|DesignRegistryTest|PortableConfigurationTest
```

Result: 58 tests, 334 assertions, PASS. PHP lint for all Binding classes: PASS.

## Default parity

Fresh full build: 104 routes, 307 files, 208 HTML. A selected Alert rebuild
preserved the same candidate receipt SHA-256:
`f54555a0f952a3c66a2fba18eedf21c3d574c959c2edeecf5933157b1904ce3d`.

All 306 public/runtime output files other than the provenance receipt are
byte-identical to A0. Their content-addressed ledger is:
`ab0a4d0304a919986e5348ec43a8577740e01dbf899c932b09bc91c275e25ba0`.
Alert HTML remains:
`e1803412dc2ed849afc2f74711831ab2309df9f39f90c2034d2db0c43a281131`.

The only baseline diff is the receipt's engine source/tree digest, which must
change when tracked engine source changes. No binding, HTML, asset, route,
metadata or search payload changed.

## Rollback

Revert the dedicated A1 commit. Package Section artifacts themselves remain
unchanged, so rollback does not require content or configuration migration.
