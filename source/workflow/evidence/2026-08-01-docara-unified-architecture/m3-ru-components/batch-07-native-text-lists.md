# Batch 07: native headings/text and lists/quotes

Date: 2026-08-01

Verdict: PASS

Parent SHA: `feb297bdafb4ecbb83b8875c9665d9c611fd71b7`

Candidate binding: the commit that first contains this evidence file.

## Routes and owners

| Route | Physical owner | HTML SHA-256 |
| --- | --- | --- |
| `/ru/components/headings-and-text/` | `docs/site/content/ru/components/headings-and-text.md` | `2445d9fa6f82298dabb80b75ffe0fecb69ab5e28a521e66925a45ac097595685` |
| `/ru/components/lists-and-quotes/` | `docs/site/content/ru/components/lists-and-quotes.md` | `85db963a7f7c87ab025824116fd4fc17026c318baa9ff4824cf836d347e1c47b` |

Both pages have a purpose, tabbed general example, practical forms, calls and
useful limits. They contain no catalog/process filler. Equivalent physical
owners are included in the portable starter so a Russian starter never needs
these page presentations from the package language pack.

## Typed IR and PageBuilder

All physical `content/(ru/)?components/<slug>.md` pages now enter the same
target `PageBuilder` pipeline by route pattern instead of a growing per-page
whitelist. Existing Alert, Badge and Syntax tests remain green.

The generic native IR adds `list` and `blockquote` source-node types with
physical ranges. They share `SourceDocumentNodeRenderer` and the one renderer
registry; no route-specific renderer exists. Nested list lines and quote
attribution lines remain one typed semantic node.

## Legacy reduction

- allowlist routes removed: `headings-and-text`, `lists-and-quotes`;
- Russian generated-route maximum: `41 -> 39`;
- Russian pack records removed:
  `native.headings_and_text`, `native.lists_and_quotes`;
- generated Russian detail receipt: `28 -> 26`;
- zero-reference examples retired:
  - `native.headings_and_text.ru.md`, old SHA-256
    `ad0c4ce3edae5c03e619596cf0cc7e15362da0ead46304592a88d4c042aab7da`;
  - `native.lists_and_quotes.ru.md`, old SHA-256
    `a950f3aee760bb6eb9cc034c9900b535393e3ab4398ec8392940ff3488414b66`.

Rollback: revert this checkpoint commit. The parent recovers both files and
their exact pack/allowlist records.

## Verification

```text
php ../../docara build m3-native-reduced-full
PASS — 103 pages, 321 files

php ../../docara build m3-native-reduced-headings \
  --page=/ru/components/headings-and-text/
PASS — 1 selected page; full/single tree diff empty

php ../../docara build m3-native-reduced-lists \
  --page=/ru/components/lists-and-quotes/
PASS — 1 selected page; full/single tree diff empty

php ../../docara verify-static m3-native-reduced-full
PASS — 206 HTML pages, 18,875 local references, 0 broken
```

- full tree manifest SHA-256:
  `d7def80ef0215028c2b834f1ace2e827eae2aec9d3a72358d11bbe96c29443ec`;
- both diagnostics use `authored_markdown` and their exact physical paths;
- generated receipt contains neither route;
- focused compiler/PageBuilder/catalog/allowlist/static/build tests: PASS;
- full PHPUnit: 370 tests, 7,270 assertions, 2 inherited warnings, PASS;
- Pint, PHP lint, JSON and `git diff --check`: PASS.

## Browser smoke

Playwright 1.52 verified desktop-light and mobile-dark for both routes:

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| headings/text | desktop-light | `71b42450e0466f06e8d48aafbe90335ed5d315f74d3574d5732f27c87240da3c` |
| headings/text | mobile-dark | `6c74e80c516bbd7c13ffc296ac6258c743645fceb5aa9a064e2d48087ebe0e09` |
| lists/quotes | desktop-light | `75995b694116659e01ceb59e873769c379b8e8d75b49ba5b4b65124f68d61633` |
| lists/quotes | mobile-dark | `8145b9321b6fd5f1d3089680301d5bbb424d1445637ba23d3747de49d586b491` |

All four checks pass title, example/source tabs, copy state, semantic
table/list/blockquote surface, first-Tab skip link, no horizontal overflow and
zero console/page errors or warnings. Screenshots remain disposable evidence.

## Readiness boundary

Batch 07 is complete; overall M3 is not. Five of 32 component routes are now
physical and 27 remain generated. Batch 08 migrates links/images and table.
