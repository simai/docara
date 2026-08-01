# M3.3 batch 10: details and backlinks

Date: 2026-08-01

Verdict: PASS

Parent SHA: `dc15cc7`

Candidate SHA: commit containing this evidence

## Ownership and content

- `/ru/components/details/` ->
  `docs/site/content/ru/components/details.md`;
- `/ru/components/backlinks/` ->
  `docs/site/content/ru/components/backlinks.md`;
- equivalent starter owners live under `stubs/portable/content/ru/components/`;
- both pages contain purpose, a working general example, parameters, variants,
  a call and only useful limitations.

The two Russian language-pack prose records and localized catalog examples are
removed. Typed catalog definitions keep structural parameters and now point
their `docs_ref` at the physical Markdown owner.

## Generic IR and one PageBuilder result

Known typed Markdown directives now compile to one `typed_directive` SourceNode
with canonical component id, renderer id, sorted props and exact source range.
The same node covers non-empty Details and empty Backlinks blocks, including
directives inside generic example fences. Unknown directives still fail closed
with physical file, line and column. Alert/Badge content components continue
through the existing single Smart gateway; no Details- or Backlinks-specific
pipeline, IR type or renderer was added.

Declarative layout composition now receives the already rendered PageBuilder
main region for authored and projected pages and hash-checks that composition
does not change it. This removes the second final Markdown content render that
previously replaced hydrated Backlinks with an empty placeholder.

## Derived backlink projection and isolated parity

Full build creates the disposable derived projection
`.docara/backlinks.json` from PageBuilder page URLs, titles and rendered links.
It contains no page prose owner and is hash-bound. An isolated build reads the
accepted complete-build projection and hydrates only its selected route; it
does not compile other pages or catalog/example projections. Relative links
are resolved using URL-directory semantics and are regression tested.

After hydration, outline is refreshed from the same PageBuilder HTML before
layout composition. Russian Backlinks heading/empty copy is read from
`docs/site/content/ru/lang.json` through the content-language overlay; package
language packs remain only a compatibility fallback for non-migrated locales.

## Legacy reduction and rollback

- generated-route allowlist: `37 -> 35`;
- Russian language-pack records/max: `35 -> 33`;
- Russian generated component-detail receipt: `22 -> 20`;
- physical component ownership: `9/32 -> 11/32`;
- retired localized examples after zero-reference proof:
  - `docara.details.ru.md`, old SHA-256
    `115274e419056f3ea8fb25a7e4506d5eab99f8026aa2c98d1f0e033e74e9bf45`;
  - `docara.backlinks.ru.md`, old SHA-256
    `b4c120dc1c946f1c8eb25ad1c19d06a60533a62503cc25c2cdfcd4da1f58f767`.

Rollback is a revert of this checkpoint commit. The parent restores both
examples, pack records, generated routes and the pre-projection layout path.

## Build, static and test evidence

```text
php ../../docara build m3-details-backlinks-final2-full
PASS — 103 pages

php ../../docara build m3-details-final2-single \
  --page=/ru/components/details/
PASS — 1 selected page; full/single tree diff empty

php ../../docara build m3-backlinks-final2-single \
  --page=/ru/components/backlinks/
PASS — 1 selected page; full/single tree diff empty

php ../../docara verify-static build_m3-details-backlinks-final2-full
PASS — 206 HTML pages, 18,917 local references, 0 broken
```

- content-addressed full tree SHA-256:
  `9c86c0b55533d6593f52964e479a4a5f156f69ab921aa03a079753e9dd8ec96c`;
- Details HTML SHA-256:
  `937b6d376f3620bb51bf4100ed3edcd9cadec5f0929d1263e15ed186ebab0535`;
- Backlinks HTML SHA-256:
  `8f699f87cfb3dcbdb2b4b19099d67ae93a56fa5148c764512b32532e8b613289`;
- backlink receipt SHA-256:
  `85ec834ee95e7e0f0f26978e39c7a83f7651f7932d02768dbd7c26338ab1919f`;
- focused compiler/PageBuilder/catalog/allowlist/static/locale tests: PASS;
- full PHPUnit: 374 tests, 7,048 assertions, 2 inherited warnings, PASS;
- changed-file Pint, PHP lint, JSON and `git diff --check`: PASS;
- project-graph validator: PASS, 0 warnings and 0 blockers.

## Browser evidence

Playwright Chromium verified desktop light (1440 x 1000) and mobile dark
(390 x 844). Details has three disclosure examples; Enter toggles the focused
summary from closed to open. Both Backlinks surfaces are hydrated, each links
to the Details owner, uses the Russian content-owned heading and has no global
overflow. Console warnings/errors: zero.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| details | desktop-light | `3707cfbc05fde54dde6022e675bca94458055160f07c065d35e8878c3263ee65` |
| details | mobile-dark | `ec4718567358962d848dbe79f7dc73df64142e5d2ca9cae716cfc3642fc7bc4a` |
| backlinks | desktop-light | `ae967a08eb73524fc6e099c05e8694798ad9b8ada37c0291b768bf3ad23622e2` |
| backlinks | mobile-dark | `7a5969afa3382b966ead433d32d915c6bd709711a09c44540cfdc5772b331f4c` |

Screenshots remain disposable evidence, not source of truth.

## Readiness boundary

Batch 10 is complete; overall M3 is not. Eleven of 32 component routes are
physical and 21 remain generated. Batch 11 migrates Banner and Download.
