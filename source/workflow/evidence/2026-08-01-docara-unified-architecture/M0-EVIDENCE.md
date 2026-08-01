# M0 implementation inventory and baseline evidence

Date: 2026-08-01

Batch: `docara.batch.m0.mapping`

Candidate revision: `2928d68b81665dd4873cebeb87a6192343c28805`

Exact parent: `a3ba9a4d04429f1f2046b8415764fe7bc89962c7`

Branch: `codex/docara-unified-architecture`

## Workspace hygiene

- `pwd` -> `/Users/rim/Documents/GitHub/docara-unified`;
- branch and candidate revision match the delegated contract;
- the worktree was clean before M0 evidence writes;
- `HEAD^` is the exact architecture baseline recorded in the specification;
- no runtime, templates, assets, content, product config, dependency lock,
  build output, release or deployment surface was changed.

The handoff input names `2928d68...` as the expected starting commit, while
`START.md` and `STATUS.yaml` name `a3ba9a4...` as the baseline revision. There
is no code contradiction: `2928d68...` is the architecture commit and its
exact parent is `a3ba9a4...`.

## Runtime and dependency snapshot

The default `/opt/homebrew/bin/php` is not usable because it is linked against
missing `libicuio.73.dylib`. M0 used the working binary:

```text
/opt/homebrew/Cellar/php@8.3/8.3.31/bin/php
PHP 8.3.31
```

The repository has no `composer.lock` and the clean worktree has no `vendor/`.
To keep the assigned worktree unchanged, checks ran in a temporary `git
archive` of exact `HEAD`. Composer 2.7.1 resolved dependencies there only.
The resulting package snapshot is preserved in
`m0-dependency-snapshot.json`; this is evidence, not a product lock update.

## Repeatable inventory commands

```bash
git rev-parse HEAD HEAD^
find docs/site/content -type f \( -name '*.md' -o -name '*.markdown' \) | LC_ALL=C sort
find docs/site/content -type f -name 'section.json' | LC_ALL=C sort
find docs/site/content -type f -name '*.page.json' | LC_ALL=C sort
jq '.components | length' resources/language-packs/*.json
rg -n 'buildGenerated|trustedMainHtml|InlineComponentRenderer|SmartComponentGateway|FrameworkComponentRuntime::fromLock|resolveGeneratedBase' src tests
```

Observed inventory:

- 59 physical Markdown files, all under `content/ru`;
- 9 `section.json` files and 24 page sidecars;
- 103 logical public pages: 59 authored Markdown routes and 44 generated
  projections;
- generated projections: 30 component index/detail routes and 14 declarative
  example index/detail routes;
- language-pack component records: `ru=42`, `en=42`, `ar=8`, `fr-CA=8`,
  `zh-Hans=8`;
- component catalog sources: 4 inline definitions, 6 native definitions, 2
  Framework Smart definitions, 25 typed definitions and 75 example fragments;
- the active legacy concentration is 5,619 lines across
  `PortableSiteBuilder`, `PortableMarkdownRenderer`, both projectors,
  `InlineComponentRenderer` and `FrameworkComponentRuntime`.

The complete route inventory is `m0-route-inventory.json`.

## Current source to output map

```text
Console BuildCommand
  -> PortableSiteBuilder::build
     -> LocaleRegistry + markdownFiles
     -> PortableConfigurationLoader::resolve
        -> defaults -> docara.json -> section.json -> page sidecar
     -> FrameworkComponentRuntime::extract
     -> PortableMarkdownRenderer + outline + hydration
     -> authored page records
     -> EffectiveComponentCatalogBuilder
     -> PortableComponentCatalogProjector::project
     -> PortableDeclarativeExampleProjector::project
     -> navigation/search/redirect/asset planning for every page
     -> only then --page filtering
     -> DeclarativePipeline::build or buildGenerated(trustedMainHtml)
        -> DocumentParser (coarse MarkdownNode + limited SmartCallNode)
        -> DocumentNodeBlockRegistry
        -> Declarative SmartComponentGateway where reachable
        -> DocumentNodeRendererRegistry
        -> layout/view composition
     -> DeclarativePortablePagePublisher::render
     -> HTML, assets, indexes and .docara receipts
```

This is not the target one-page pipeline. Discovery, generated projections,
navigation, search and asset planning happen before the `--page` filter, and
generated pages enter `buildGenerated()` through `trustedMainHtml`.

## Badge trace

`/ru/components/badge/` is a positive source-ownership exception in the
current baseline:

- physical source: `content/ru/components/badge.md`;
- content SHA-256:
  `e490652875357f06228b62eee11873e67077d0232f6553ba8c1d91f9a943605b`;
- resolved configuration sources: `docara.json`, immutable Framework lock,
  `content/ru/section.json`, `content/ru/components/section.json`;
- output: `ru/components/badge/index.html`;
- HTML SHA-256:
  `faeb6c6a8e075bff9ad5602bcea4b1e019c700aeae74f696c0289e32fbb83f79`;
- 16 rendered badge instances in the example previews.

However, badge does not traverse the target component path. Its examples are
inside fenced Markdown passed to `PortableExampleRenderer`, then rendered by
the hard-coded `InlineComponentRenderer::badge`. The declarative receipt has
`normalized_calls=[]`; no typed badge component node reaches
`SmartComponentGateway`. Exact trace and asset keys are in
`m0-badge-trace.json`.

## Baseline checks

### Formatter

`php vendor/bin/pint --test` -> FAIL. Twelve inherited files require formatting,
including `PortableSiteBuilder.php`, `PortableComponentCatalogProjector.php`,
`DeclarativePageCompiler.php` and `FrameworkAssetPlanner.php`. M0 did not
rewrite them.

### PHPUnit

The suite contains 343 tests. File-by-file execution, with the two long files
also split by test method, produced 341 passing tests and two failures:

1. `PortableDocumentationSiteTest::real_documentation_build_matches_the_exact_product_matrix_and_static_verifier`;
2. `DocumentationContractTest::authored_documentation_covers_five_audience_paths_and_every_page_has_one_h1`.

Both failures have the same exact mismatch: expected authored Markdown count
58, actual 59. The runtime-oriented `PortableSiteBuilderTest` (37 methods) and
`StaticBuildVerifierTest` (21 methods) pass when executed method-by-method.

### Deterministic full and page builds

Two clean full builds both reported 103 logical pages. Their 321-file SHA-256
manifests are byte-identical:

```text
aea212c5b39f44411356b54841bdf89bde6c797f4c397cb25d629ea1be562b52
```

The complete first manifest is `m0-build-manifest.sha256`.

An isolated rebuild of `/ru/components/badge/` reported one page. Badge HTML
kept the same SHA-256, and the complete 321-file manifest remained identical
to the full-build manifest.

### Static verification

`verify-static build_production` -> PASS:

- 206 HTML documents;
- 18,866 local references checked;
- 0 broken references.

## 2026-07-30 claim audit

Demonstrably present:

- a physical authored badge page;
- a declarative `DocumentAst`, node/block registry, renderer registry and
  namespace-aware Smart gateway exist as partial implementation;
- deterministic full builds and byte-identical full/single badge output work;
- layout/region composition and source/config provenance are recorded;
- exact Framework revisions and projected asset hashes are validated.

Still plans or partial only:

- 44 public routes have no physical Markdown owner;
- language packs still own component presentation prose;
- native Markdown remains a coarse `MarkdownNode`, not a typed node tree;
- source spans have line ranges but no required column on all nodes;
- aliases and product components do not all pass one Smart gateway;
- badge specifically bypasses the gateway through hard-coded inline HTML;
- there is no `PageBuilder`; `PortableSiteBuilder` owns both site and page work;
- single-page filtering occurs after global projections and index planning;
- `buildGenerated()` and `trustedMainHtml` remain active;
- component and example projectors remain active;
- update has preserve/add-missing behavior but no accepted diff/dry-run and
  rollback package contract.

## Explicit divergences requiring a decision

1. `graph/specs/gates/source-ownership.json` blocks M2 while any public route
   depends on generated prose, but the accepted roadmap schedules bulk route
   migration for M3 after the M2 badge vertical slice. Both cannot be true at
   once. Recommended resolution: add a scoped `badge_source_ready` gate before
   M2 and retain the global source-ownership gate for M3 migration completion.
2. The repository contains no `composer.lock`, so a future `composer install`
   does not reproduce the same dependency tuple unless the release/update
   contract accepts and owns a package lock. M0 records the temporary tuple but
   does not decide product lock policy.
3. Root `README.md` links to absent
   `docs/site/content/ru/components.md`; the generated `/components/` index is
   not a valid source file replacement under the target architecture.
4. The 2026-07-30 workflow says M0 inventory was recorded, but the six
   implementation mappings were empty at M0 start. This batch supplies the
   missing exact mapping rather than accepting the historical status claim.

## Gate verdict

The mapping and reproducibility evidence is complete, but
`docara.gate.m0_baseline` is not promoted by this executor:

- the existing test/formatter baseline is red;
- the M1/M2 gate-order contradiction needs an accepted graph decision;
- gate ownership belongs to `tester`.

`STATUS.yaml` therefore remains unchanged and no readiness is claimed.
