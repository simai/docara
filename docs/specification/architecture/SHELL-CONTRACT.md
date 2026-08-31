# Shell Contract & Safe Configuration

Status: Goal A implementation contract
Contract version: `docara.shell_contract.v1`

## Purpose

Docara lets a project select registered shell composition without allowing
configuration to load executable code or filesystem paths. The shell uses the
same DesignRegistry, SmartComponentGateway, LayoutComposer and PageBuilder as
normal production pages and preview.

## Binding registry

`BindingRegistry` is a deterministic registry of trusted providers. A binding
descriptor contains:

- namespaced ID, owner namespace, provider and provider revision;
- versioned shell capabilities where it is valid;
- output prop schema and binding-owned prop names;
- allowed application-state inputs and dependency trace;
- source/provenance and readiness status;
- a trusted package resolver object registered by the provider.

Project configuration may select a registered binding ID. It cannot supply a
class, callback, method, PHP file, template or arbitrary path. A project cannot
implicitly shadow package ownership.

Built-in Goal A bindings are:

| ID | Capabilities | Output responsibility |
| --- | --- | --- |
| `docara.branding` | `shell.brand` | normalized branding and its registered preset |
| `docara.navigation` | `shell.primary-navigation`, `shell.secondary-navigation` | normalized navigation items and shared labels |
| `docara.outline` | `shell.outline` | page outline items and label |

## Shell capabilities

The v1 allowlist is:

```text
shell.brand
shell.primary-navigation
shell.secondary-navigation
shell.outline
shell.content-before
shell.content-after
shell.footer
```

A layout region declares capabilities. A Section declares the capabilities it
may satisfy. A configured Section/Block/binding must be compatible with the
selected region before Smart resolution. Capabilities are stable IDs, not DOM
selectors, component-ID switches or template paths.

## Prop merge and validation

```text
validated static props
  -> binding-owned props
  -> final registered Smart manifest validation
```

Static configuration cannot set a binding-owned prop. A collision fails with a
stable diagnostic. Both binding output and final Smart props are validated.

## Navigation vertical slice

One semantic `docara.navigation` artifact owns the `header`, `tree` and
`compact` presentations. Selection uses a registered View/preset through the
same Gateway. The compiler does not branch on `docara.navigation` or a provider
namespace. The default presentation remains backward compatible.

## Project shell contribution

A project-owned Section/Block/Smart may target an admitted capability through
project design and Smart providers. Its definitions and props are data-only and
must pass the existing root, namespace, duplicate, symlink, schema and final
Smart validation gates. The outer page and `<head>` are not replaceable.

## Fail-closed policy

The build rejects duplicate IDs/namespace owners, unsupported capabilities,
binding-owned prop collisions, unknown bindings, invalid output/final props,
traversal, symlink escape, case collisions and executable configuration keys or
values. Preview cannot bypass these checks or create a production receipt.

## Icon resources

The permanent shell uses a package-owned `sf.icon_subset.v1` packet generated
from the exact Material Symbols source by `ui-builder`. The first frame
preloads the content-addressed subset, not the 3.96 MB full outlined font. Its
receipt records the sorted icon names, source hash, subset font hash and size,
manifest hash, packet hash and fallback mode.

Docara does not call `icons.simai.io`. An icon outside the admitted shell set
causes one exact local full font to be loaded lazily, after which the outlined
family is replaced atomically. Rounded and sharp families remain separate and
are loaded only when their selectors occur. The full fonts stay package-owned
for offline correctness and are never addressed through `@latest`.

`verify-static` reconstructs the same Framework asset plan and therefore
rejects a missing subset, changed WOFF2, stale manifest, incorrect preload or
network-based moving fallback. No new project setting or lock file is created.

## Non-goals

Goal A does not migrate search, breadcrumbs, pager or the full publisher chrome;
does not create public documentation IA; and does not add another parser,
renderer, Gateway, registry of the same responsibility, composer, PageBuilder
or preview engine.
