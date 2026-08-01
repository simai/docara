# M3.3 batch 12: Button, Icon and Kbd

Date: 2026-08-01

Verdict: PASS

Parent SHA: `0e16e544fd7300a071b8c6309f8efd06fed925a0`

Candidate SHA: commit containing this evidence

## Ownership and shared runtime

- `/ru/components/button/` -> `docs/site/content/ru/components/button.md`;
- `/ru/components/icon/` -> `docs/site/content/ru/components/icon.md`;
- `/ru/components/kbd/` -> `docs/site/content/ru/components/kbd.md`;
- matching portable starters live under `stubs/portable/content/ru/components/`.

All four inline authoring aliases (`badge`, `button`, `icon`, `kbd`) now come
from `ContentComponentRegistry` manifests. `InlineComponentRenderer` discovers
only registry entries with a `plain_text` slot and delegates every call to the
existing `ComponentNode` -> `DocumentRendererRegistry` ->
`SmartComponentGateway` path. Its former Button/Icon/Kbd methods and validation
branches are gone. Alert remains a document-slot block in the same registry and
cannot accidentally enter inline parsing.

The shared manifest contract now admits enum, string, identifier and safe URL
props, normalization, invalid combinations and an optional identifier slot
pattern. Unknown aliases/props, unsafe URLs, invalid icon names and invalid
combinations fail closed through the generic component renderer with physical
source location. No component-specific pipeline, IR type, renderer registry,
gateway or PageBuilder was added.

## Legacy reduction and rollback

- generated-route allowlist: `33 -> 30`;
- Russian language-pack records/max: `31 -> 28`;
- Russian generated component-detail receipt: `18 -> 15`;
- physical component ownership: `13/32 -> 16/32`;
- zero-reference localized examples retired:
  - `docara.button.ru.md`, old SHA-256
    `9552cf20ca0c3c00000323a29d192d1221dfd205e45f3df5a50c153375a9a796`;
  - `docara.icon.ru.md`, old SHA-256
    `2804422a7dca5a2835f2480a62fe173a88d975ba135cff66d2fe1f54015aa479`;
  - `docara.kbd.ru.md`, old SHA-256
    `7db2c7558e3bd98567ca96f2a38bcef6d9d8a800b942490aaed53a7b9d0f4d00`.

Repository-wide reference search found no active consumer of these localized
fixtures. Rollback is a revert of this checkpoint commit; the parent restores
the exact files, pack records, hard-coded inline adapter and generated routes.

## Verification

```text
php ../../docara build m3-b12-full
PASS — 103 pages

cp -R build_m3-b12-full build_m3-b12-<route>
php ../../docara build m3-b12-<route> --page=/ru/components/<route>/
PASS — Button, Icon and Kbd each build exactly 1 selected page

diff -qr build_m3-b12-full build_m3-b12-<route>
PASS — all three full/isolated output trees are exact

php ../../docara verify-static build_m3-b12-full
PASS — 206 HTML pages, 18,914 local references, 0 broken
```

- content-addressed full and all isolated tree SHA-256:
  `4dbf0ca9e688c28377b993c091b7df30c9b9e04149019318b888892a60a080fe`;
- Button HTML SHA-256:
  `f38570f842a212a3980cfe941c23437d84157fb7158b2c450c36ad51dbc426d9`;
- Icon HTML SHA-256:
  `674cb4bd69d4c6cc75abbbfa54113649a049631eef8af090239122fa7fc42726`;
- Kbd HTML SHA-256:
  `9a3b45b8cbf56a434620f521f03093eb8e6e7cd61a268cbf5c291afbbba973b0`;
- focused compiler, renderer, PageBuilder, catalog, allowlist, documentation
  and source-boundary tests: PASS, 110 tests and 4,362 assertions with two
  inherited warnings;
- the full suite initially found one stale navigation fixture after the three
  physical routes appeared; that inventory was corrected and its focused
  regression passes (1 test, 4 assertions). Final full suite: PASS, 374 tests
  and 6,830 assertions with two inherited warnings;
- changed PHP lint, JSON, graph and `git diff --check`: PASS.

## Browser evidence

Playwright Chromium verified Button at desktop light and Icon/Kbd at mobile
dark. Button renders eight action examples, switches its example/Markdown tab,
exposes copy controls and keeps keyboard focus. Icon renders eight symbols,
five meaningful accessibility labels and valid container variants. Kbd renders
four keyboard tokens. Every route has zero global overflow and zero console
warnings/errors.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| button | desktop-light | `bc2e1c5a491c0798bccf3cca18b459f03bbbced3a85f91f7e9b81522c06039fc` |
| icon | mobile-dark | `6a340094b42a830d49369bfb143003abfef206898d545834b203708234fe25c9` |

Screenshots remain disposable evidence only.

## Readiness boundary

Batch 12 is complete; overall M3 is not. Sixteen of 32 component routes are
physical and 16 remain generated. Batch 13 migrates Card and Hero.
