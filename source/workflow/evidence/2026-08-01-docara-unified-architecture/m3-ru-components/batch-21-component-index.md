# M3.4 batch 21: physical component index and derived views

Date: 2026-08-02

Parent: `d243ec2ec8df3c5987b201cb4ccbdf277835680f`

Candidate: commit containing this record

Verdict: PASS; M3.3 and M3.4 are complete, the overall M3 Goal remains open.

## Ownership and architecture

- `/ru/components/` is physically owned by
  `docs/site/content/ru/components.md`;
- all 32 public Russian component routes are now `authored_markdown` pages;
- the index prose stays in Markdown, while its 31 links, titles and short
  descriptions are derived from PageBuilder page-result metadata;
- `:::component_index` compiles to the existing generic `typed_directive`
  Document IR node and resolves through the same typed renderer registry;
- `PortableComponentIndexHydrator` replaces only the typed placeholder after
  PageBuilder, binds its metadata projection to `.docara/component-index.json`
  and reuses that exact receipt for isolated builds;
- the Russian catalog projector now emits neither index nor detail pages. Its
  receipt is valid with `index: null` and `pages: []`.

Navigation, breadcrumbs, outline, previous/next and search continue to use the
same page-result topology. No second PageBuilder, renderer registry, Smart
gateway, route catalog or page-prose registry was added.

## Build and parity

```text
php ../../docara build m3-b21-full
PASS — 103 pages

php ../../docara build m3-b21-index --page=/ru/components/
PASS — 1 selected page; full/isolated HTML byte-identical

php ../../docara build m3-b21-badge --page=/ru/components/badge/
PASS — 1 selected page; full/isolated HTML byte-identical

php ../../docara verify-static build_m3-b21-full
PASS — 206 HTML pages, 18,942 local references, 0 broken
```

- component index HTML SHA-256:
  `38ef5ff864ade3bd043d9bb245a939205cb9698fd12e13791188afd889fae6b7`;
- representative Badge HTML SHA-256:
  `553aff3e1a0135fe244ee6f7d1c93f4bdf859eba550ebca41964fda558747f77`;
- component routes: 32; physical owners: 32; generated component pages: 0;
- derived index entries: 31;
- search documents under `/ru/components/`: 32, including the index;
- the physical index has one outline entry, breadcrumbs, previous/next and
  the same navigation links as the full page topology.

The static verifier independently reconstructs the component index from
authored resolved-page metadata, validates the receipt hash and checks the
rendered ordered links. The first focused verifier run exposed missing-receipt
compatibility for synthetic builds without a physical index; the contract was
corrected to require the receipt only when an authored index exists.

## Browser evidence

Playwright at desktop light and 390 px mobile confirms 31 rendered list items,
31 exact links, the authored outline, no horizontal overflow and no console
warnings/errors. Mobile dark retains the same list and zero overflow.

| Mode | Screenshot SHA-256 |
| --- | --- |
| desktop-light | `029f98f07c18589da53c749831a2589fac10abac1cdea9fd3bf9f3b04f6f1fa2` |
| mobile-light | `7271ccfbc6d37e447e47521131aabfb50ddd44a0f37f7694dc19bab0e9568634` |
| mobile-dark | `3a987ad1616111dddb16b3a66a183629cd96b86fa7c68deaa4a9cd935ab27a7e` |

## Tests and rollback

- focused typed catalog/compiler suite: 37 tests, 1,915 assertions, two
  inherited warnings;
- focused component-index/catalog/static/PageBuilder suite: PASS after the
  compatibility correction;
- focused static verifier: 21 tests, 243 assertions, PASS;
- complete PHPUnit: 377 tests, 6,192 assertions, two inherited warnings;
- PHP lint: 263 repository PHP files; JSON: 404 files; graph validation and
  `git diff --check`: PASS;
- reverting the checkpoint restores the generated component index and removes
  the physical owner plus its derived-view receipt. No content data migration
  or dependency change is involved.

Batch 25 removes the remaining Russian language-pack component prose and proves
that no catalog presentation data is needed when all Russian component routes
are authored. M3 completion and release readiness are not claimed.
