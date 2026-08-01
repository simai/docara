# M3.3 batch 14: Grid and Figure

Date: 2026-08-01

Verdict: PASS

Parent SHA: `5cb1a5112711e9ea08d07841b06fceffee86a574`

Candidate SHA: commit containing this evidence

## Ownership and runtime

- `/ru/components/grid/` -> `docs/site/content/ru/components/grid.md`;
- `/ru/components/figure/` -> `docs/site/content/ru/components/figure.md`;
- matching portable starters live under `stubs/portable/content/ru/components/`.

Both routes use the existing generic `typed_directive` IR and one renderer
registry/PageBuilder. Grid documents responsive columns/gaps with Card
composition. Figure uses physical content assets, mandatory alt text, captions,
ratios and cover/contain behavior. No family-specific compiler, IR type,
registry, gateway or page pipeline was added.

## Legacy reduction and rollback

- generated-route allowlist: `28 -> 26`;
- Russian language-pack records/max: `26 -> 24`;
- Russian generated component-detail receipt: `13 -> 11`;
- physical component ownership: `18/32 -> 20/32`;
- zero-reference localized examples retired:
  - `docara.grid.ru.md`, old SHA-256
    `f4444d18164db88b5a917463ab0e5a8d604ac5ede91121f6b3aa44b10ddc3052`;
  - `docara.figure.ru.md`, old SHA-256
    `a3ac74d28c014993ec5188462385daef8459f7d4abe90f6efb35258fa7348764`.

Generated-projection verifier fixtures moved from newly physical Grid to the
still-generated Logos route. Repository-wide search found no active reference
to either retired localized fixture. Reverting the checkpoint restores exact
owners, pack records, routes and examples.

## Verification

- full build: 103 pages;
- Grid/Figure isolated builds: one selected page each;
- full/isolated trees: exact, SHA-256
  `b360264f5cbaf706e3d8f0f0bcd60fab9488ebb7a87e24a0899a378edabb59ec`;
- static verifier: 206 HTML pages, 18,925 local references, 0 broken;
- Grid HTML SHA-256:
  `6e4d0ed74642b4d4d6e5b95ed351763ef974d7c8305b1acf732c5c4db10c8723`;
- Figure HTML SHA-256:
  `975c70e81575967f18e557ee5b77b53e6b7114c2298cceab7ae7bb0bb9d36a94`;
- focused projector/catalog/PageBuilder/allowlist/documentation/static suite
  plus corrected projection verifier: PASS;
- full PHPUnit: PASS, 374 tests and 6,652 assertions with two inherited
  warnings; lint, JSON, graph and `git diff --check`: PASS.

## Browser evidence

Playwright Chromium verified Grid at desktop light: two responsive grids, five
cards and zero overflow. Figure at mobile dark renders two loaded content
images with meaningful alt text and zero overflow. Both routes report zero
console warnings/errors.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| grid | desktop-light | `cc59d4dd63399107ccf2006ac284fc32d35946885e8f2d639b0fc356bf8851e2` |
| figure | mobile-dark | `2f1a07f8e9f19aebe65e54803b00ddd6766500fb97f9490074562c9a5b9b2621` |

Screenshots remain disposable evidence only.

## Readiness boundary

Batch 14 is complete; overall M3 is not. Twenty of 32 component routes are
physical and 12 remain generated. Batch 15 migrates Media and Logos.
