---
description: Reference for simai-framework.lock.json: the exact Core and Smart revisions, manifests, consumer policy, hashes and asset projections that the lock pins.
---

# Framework lock file

`simai-framework.lock.json` (the Framework lock) ties together the exact
Core/Smart revisions, the registry identity, manifests, the consumer policy,
hashes and asset projections. Besides Smart artifacts and typography, the
lock pins the local Core runtime package: the bootstrap JavaScript, the
required webpack/language chunks, Material Symbols, admitted component assets
and the Docara utilities in use.

## Why the lock is separate

Presentation JSON chooses how a page looks, but it cannot admit a new Smart
component. The runtime surface is defined by a separate immutable input that
you can check and compare between builds.

`main`, `master`, `latest` and other moving references are forbidden. Local
projected bytes are checked by SHA-256 before publication. The manifest of
the runtime package records every distribution-relative path and a shared
path-sorted ledger; a path is kept relative to `distr`, because Core resolves
lazy chunks and utilities from `window.sfPath`.

## What to check

1. The lock has the schema `docara.framework_lock.v1`.
2. Every revision is immutable.
3. The bundled manifest and the provider revision match the lock.
4. The asset plan holds only allowed dependencies and omission contracts.
5. The build and `verify-static` pass on one exact input.

A Docara build does not need jsDelivr, Google Fonts or any other external
asset host: Inter, Material Symbols, Core, utilities and admitted Smart assets
are published into `_docara/vendor` and `_docara/framework`. Links to
external sites in the content itself stay ordinary links and are not a
runtime dependency.

Having a component name or a runtime file does not mean admission. Admission
needs a full authoring contract, a manifest, dependencies, a host renderer,
accessibility, tests and a consumer policy.

Look up the exact identity of the current site in
`simai-framework.lock.json` itself, in
`build_<environment>.docara/resolved-page-plans.json` and in
`_docara/component-catalog.json`. Do not copy commits, pair IDs and the
component list into a hand-written reference: generated artifacts reflect a
specific build without drift.

The Framework-owned surface includes not only Smart components but also the
behaviour components of Core. For example, `sf-scrollbar` provides the modes
`overlay`, `persistent`, `standard` and `hidden`; Docara chooses a preset
through `layout.scrollbar.preset` but does not copy the scrollbar
implementation.

## File fields

`docara init` creates the file together with the project. You do not usually
edit it by hand: the build checks the pinned files by their hashes. The
tables below show what exactly is pinned.

### SIMAI Framework version and build

:::schema_reference {schema=framework-lock scope=lock fields=schema,version,runtime,nonclaims}
:::

### Files and resources

:::schema_reference {schema=framework-lock scope=lock fields=runtime_projection,asset_projection,dynamic_asset_projection,package_projections}
:::

### Icons and fonts

:::schema_reference {schema=framework-lock scope=lock fields=icon_projection,typography_projection}
:::

### Component descriptions

:::schema_reference {schema=framework-lock scope=lock fields=manifests}
:::

## Checking Smart components

See the effective support with `docara atlas --json` and
`docara inspect smart <id> --json`. The neutral identity is
`sf.smart_artifact_abi / 1.0.0 / sf-smart-artifact-abi-v1`; the storage
compatibility alias, the provider adapter and the template ABI are shown
separately.

The accepted form wave is `ui.input`, `ui.dropdown` and `ui.checkbox`; a
populated dropdown depends on the exact-pinned text-only `ui.list-item`.
Related icons, avatars and tags remain nonclaims.

Before publication every Smart component passes checks of the schema, the
delivery owner, the namespace, the template, the assets, hydration and the
hashes.

## Browser storage fallback

If the pinned Core uses browser storage, the integration layer may provide a
bounded in-memory fallback for the current page only. Such a fallback is not
persistent storage and does not create a separate implementation of SIMAI
Framework.

## The boundary

The Framework lock proves a bounded consumer contract of a specific build. It
does not admit an arbitrary raw Framework component only because its file is
in the local package, and it does not prove the readiness of the whole
ecosystem, production, a public release or the right to distribute other
upstream bytes.

## See also

- [JSON schemas](/en/reference/schemas/)
- [Updating Docara](/en/build/update/) — how the pinned version changes
- [Smart components](/en/development/smart-components/) — admitting a
  component through the lock
