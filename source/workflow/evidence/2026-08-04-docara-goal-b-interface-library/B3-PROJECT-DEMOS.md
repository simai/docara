# B3 — Project content and shell demos

Date: 2026-08-04
Status: `pass`
Implementation commit: `399176c`

## Outcome

The initialized consumer now owns three useful portable Smart artifacts:

- `project.install-builder` formats and copies a Composer command locally;
- `project.product-configurator` calculates a local illustrative total;
- `project.footer-links` contributes bounded local links to the admitted
  footer region through project-owned Design Section/Block/View artifacts.

The underscore labels requested by the product plan remain registry aliases
(`project.install_builder`, `project.product_configurator`,
`project.footer_links`). Markdown uses the canonical hyphenated IDs required by
the exact accepted Framework-owned Portable Smart ABI. No schema fork or
alternate authoring dialect was introduced.

`PortableConfigurationLoader` now builds its structural
`DefinitionRepository` from the effective project Smart registry as well as
the project Design registry. This generic change lets registered project Smart
IDs participate in page-region validation; it contains no component ID list.

## Disposable consumer proof

Fresh `docara init` and `docara build demo` from the implementation produced:

```text
routes=39
html_pages=78
local_references_checked=3931
broken=[]
```

`/ru/project-demos/` contains all three markers:

```text
data-project-install-builder
data-project-product-configurator
data-project-footer-links
```

The five declared project CSS/JavaScript assets are present under
`_docara/smart/`. Region preview is extracted from the same production page,
reports `runtime=portable_site_builder`, and its dependency closure contains
the three project Design files plus `@project-tree:smart/project.footer-links`.

## Focused and security proof

```text
ProjectExtensionDemoTest + DesignAtlasTest
PASS — 7 tests, 48 assertions, warnings=0

ProjectExtensionDemoTest alone
PASS — 3 tests, 28 assertions

Pint changed surface
PASS

git diff --check
PASS
```

The permanent regression rejects browser-side `fetch`, `XMLHttpRequest`,
`WebSocket`, `EventSource`, `sendBeacon`, process/command and PaymentRequest
APIs in the interactive demo scripts. The components have no forms/actions,
backend, network, order or payment side effect.

## Rollback

Revert `399176c`. It adds only starter project-owned sources plus the generic
effective-registry validation connection; it does not migrate user data or
delete package runtime paths.
