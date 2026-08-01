# M3.3 batch 08: native links/images and table

Date: 2026-08-01

Verdict: PASS

Parent SHA: `50cb91d79af0f6bb435e3575c79e1f71964ef45f`

Candidate SHA: commit containing this evidence

## Ownership and scope

- `/ru/components/links-and-images/` ->
  `docs/site/content/ru/components/links-and-images.md`;
- `/ru/components/table/` ->
  `docs/site/content/ru/components/table.md`;
- equivalent portable-starter owners live below
  `stubs/portable/content/ru/components/`;
- no other component route, locale, dependency or Framework lock changed.

Both pages contain reader-facing purpose, one tabbed general example,
practical parameters/variants, a call and useful limits. Page prose is absent
from config and the Russian language-pack records removed by this batch.

## Shared runtime change

`image` is one generic `SourceNode` type with physical source location and is
handled by the existing `SourceDocumentNodeRenderer`. No image/table-specific
PageBuilder, registry or gateway was added.

Browser acceptance exposed two generic example-rendering defects: the copy
button contained an extra quote, and table decoration was not idempotent when
an example preview passed through the Markdown renderer twice. A table preview
therefore emitted an unmatched `</div>` and the outline DOM parser discarded
all later page nodes. `PortableExampleRenderer` now emits the valid attribute;
native table decoration wraps only undecorated `<table>` pairs. Regression
tests prove balanced example markup and preserve the headings/content after a
table example.

## Legacy reduction and rollback

- generated-route allowlist: `41 -> 39` total;
- Russian language-pack maximum and records: `39 -> 37`;
- Russian generated component-detail receipt: `26 -> 24`;
- physical component ownership: `5/32 -> 7/32`;
- zero-reference localized examples retired:
  - `native.links_and_images.ru.md`, old SHA-256
    `77c45893623b263399a1667d7d93f42ce98c2cd1b5723214a92fe7e1d6873bab`;
  - `native.table.ru.md`, old SHA-256
    `1cde90f01907f1a18ebef30cc09ce1c68615594915c813ccfedf9ef09d0f7dc1`.

The generated receipt contains neither migrated route. Rollback is a revert of
this checkpoint commit; its parent restores both exact projections, pack
records and allowlist entries.

## Build and static verification

```text
php ../../docara build m3-native-media-table-reduced-full
PASS — 103 pages, 321 files

php ../../docara build m3-native-media-table-reduced-links4 \
  --page=/ru/components/links-and-images/
PASS — 1 selected page; full/single tree diff empty

php ../../docara build m3-native-media-table-reduced-table4 \
  --page=/ru/components/table/
PASS — 1 selected page; full/single tree diff empty

php ../../docara verify-static build_m3-native-media-table-reduced-full
PASS — 206 HTML pages, 18,884 local references, 0 broken
```

- content-addressed full tree SHA-256:
  `300c52d5a2bc600c6d47746e935d204176a07099a9571604f8d8308cbbca0ef4`;
- links/images HTML SHA-256:
  `10bb54f75beb247ab46b230461d3810bc5942a4125e1b5a1656eac15e9910327`;
- table HTML SHA-256:
  `829166782e265ba73af57c4c6a75d3bca6ef3f19c9462c14b7c9389ab31a1779`;
- both diagnostics report `authored_markdown` and the exact physical owner;
- focused compiler/PageBuilder/renderer/catalog/allowlist/static/build tests:
  PASS;
- full PHPUnit: 371 tests, 7,202 assertions, 2 inherited warnings, PASS;
- Pint, PHP lint, graph/resource JSON and `git diff --check`: PASS;
- official project-graph validator: PASS (1 goal, 6 stages, 8 batches,
  4 metrics, 6 mappings, 0 warnings, 0 blockers).

## Browser verification

Playwright 1.52 verified desktop-light (1440 x 1000) and mobile-dark
(390 x 844) for both routes. Images completed with non-zero natural width;
general example/Markdown tabs and copy state work; the table route retains all
sections after the example and exposes two responsive table wrappers; there is
no document-level horizontal overflow or console warning/error.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| links/images | desktop-light | `f6f6b00a37a40b581058e284201acbe931d0fd778f85ca2b027208c72b24a9bb` |
| links/images | mobile-dark | `776f936a0aca045c6f189129823c70eeebdffcc0937fbd69617a6187fbe98fa3` |
| table | desktop-light | `8e5fd077c2f478a206cd4cb0d164f15fa7546814f8efc411c8d5d70f9890156e` |
| table | mobile-dark | `2ff07d240976e9b55931959c1e88594bf68c4f6070b78b6dfb72e4a1e21d32d5` |

Screenshots are disposable evidence only.

## Test deviations resolved

The first full suite correctly found eight failures: seven synthetic/static
fixtures did not publish the new starter content image, and one legacy test
still declared `table.md` forbidden. The portable starter now owns its content
asset, links are locale/base-url relative, and the retired-page list was
reduced only for the migrated route. The exact ten formerly failing tests pass
with 198 assertions.

## Readiness boundary

Batch 08 is complete; overall M3 is not. Seven of 32 component routes are
physical and 25 remain generated. Batch 09 migrates native code and
footnotes/sources.
