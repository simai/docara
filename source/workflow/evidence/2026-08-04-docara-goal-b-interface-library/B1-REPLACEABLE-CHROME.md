# B1 — Replaceable chrome migration

Date: 2026-08-04
Status: `pass`
Parent: `8a63e3e` (B0 Design Atlas)
Frozen parity baseline: `8c04160ab50549b060fb933cf80f86193cd92113`

## Outcome

The replaceable publisher leaves are registered Smart artifacts:

- `docara.breadcrumbs`;
- `docara.pager`;
- `docara.search`.

`PublisherChromeRenderer` creates a typed `SmartCallNode`, resolves it through
the existing `SmartComponentGateway::content()` and renders it through the
existing `SmartRenderer`. Outer `publisher.docara.page` and
`publisher.docara.head` remain application-owned. Navigation, TOC and reader
preferences retain their accepted Smart/composition path.

The former trusted template IDs and files were removed only after exact HTML
parity and a zero-reference scan. Their Goal A source hashes were:

| Historical trusted leaf | SHA-256 |
| --- | --- |
| breadcrumbs | `f908baa2eb1e5f78c06967085cca2eb7975729d6a0b6f05a606edef3a4f39f2e` |
| pager | `255e717b0d8add9dd281b28b3110c9ef02452d8c49e670959a42605d4394c343` |
| search dialog | `baa68ce7a2a8f479d13b92eda72100a4037c83dafdeda2a4b2a0deec74210203` |

## Exact baseline parity

The frozen candidate was exported with `git archive 8c04160…`, built in a
disposable directory using the immutable Composer dependency set, and compared
with a clean current B1 build.

```text
baseline routes: 104
candidate routes: 104
baseline HTML files: 208
candidate HTML files: 208
byte-different HTML files: 0
HTML ledger (both): bd2cc9138556d7bd1a8e2ce0fba0d1472745c11b6df4649bdbb8c84dca07047c
```

Static verification of the B1 build:

```text
html_pages=208
local_references_checked=21844
broken=[]
```

## Focused checks

```text
php vendor/bin/phpunit --filter
  'SmartRegistryTest|FrameworkNativeSurfaceTest|DeveloperDiscoveryCommandTest|DesignAtlasTest'
PASS — 23 tests, 193 assertions

rg publisher.docara.(breadcrumbs|pager|search-dialog) src resources tests
no runtime references

rg resources/publisher/components/(breadcrumbs|pager|search-dialog) src resources tests
no runtime references

vendor/bin/pint changed-surface
PASS

git diff --check
PASS
```

## Security and architecture

- the three artifact directories are owned by the package Smart provider;
- manifests, props, views and templates pass the existing fail-closed registry
  validation before render;
- project configuration cannot choose PHP/template paths;
- no renderer, Gateway, registry, LayoutComposer, PageBuilder or preview engine
  was added;
- the package Smart assets request only the already accepted Framework core
  stylesheet, so no parallel asset publication path exists.

## Rollback

Revert the B1 commit. The historical trusted files can be recovered exactly
from frozen candidate `8c04160…`; no project-owned data changes are required.
