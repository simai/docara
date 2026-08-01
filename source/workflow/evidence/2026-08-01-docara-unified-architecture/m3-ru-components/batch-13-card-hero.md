# M3.3 batch 13: Card and Hero

Date: 2026-08-01

Verdict: PASS

Parent SHA: `ef7cf80d7edd619f56f736535cefd5b780fc0821`

Candidate SHA: commit containing this evidence

## Ownership and runtime

- `/ru/components/card/` -> `docs/site/content/ru/components/card.md`;
- `/ru/components/hero/` -> `docs/site/content/ru/components/hero.md`;
- matching portable starters live under `stubs/portable/content/ru/components/`.

Both routes use the existing generic `typed_directive` IR node and its single
renderer-registry/PageBuilder path. Card documents decorated/plain surfaces.
Hero documents split/centered/compact layouts, bounded H1/description/action/
image structure and responsive behavior. No Card/Hero compiler, IR type,
renderer registry, gateway or page pipeline was added.

## Legacy reduction and rollback

- generated-route allowlist: `30 -> 28`;
- Russian language-pack records/max: `28 -> 26`;
- Russian generated component-detail receipt: `15 -> 13`;
- physical component ownership: `16/32 -> 18/32`;
- zero-reference localized examples retired:
  - `docara.card.ru.md`, old SHA-256
    `c19dda00019934b5d3cfe59d951334b4c96bae10d05de56f97bcdb3455d404ba`;
  - `docara.hero.ru.md`, old SHA-256
    `68d10414b429a84c674a5256a5711de50abbd0ad8c5897424220177b5e60532a`.

The accepted Hero capability check now reads its physical Markdown owner;
repository-wide search found no remaining runtime/test reference to either
localized fixture. Reverting this checkpoint restores the exact old files,
pack records and generated routes.

## Verification

```text
php ../../docara build m3-b13-full
PASS — 103 pages

cp -R build_m3-b13-full build_m3-b13-<route>
php ../../docara build m3-b13-<route> --page=/ru/components/<route>/
PASS — Card and Hero each build exactly 1 selected page

diff -qr build_m3-b13-full build_m3-b13-<route>
PASS — both full/isolated trees exact

php ../../docara verify-static build_m3-b13-full
PASS — 206 HTML pages, 18,922 local references, 0 broken
```

- full and both isolated tree SHA-256:
  `3a30e3fe36a650e1b0376a2d5c89c7aecdbdd59d6bf3cc1b90dc152b6720fcf8`;
- Card HTML SHA-256:
  `b4fe7ecd92ab1ef9e029f4c994390b1368a828804b17ead51bec2d21297f62a2`;
- Hero HTML SHA-256:
  `f24ab2f4cf2cc59bf82d8887f25b67bb675f4195892bfabd676f310e0d5b4c16`;
- focused catalog/projector/PageBuilder/allowlist/documentation/compiler suite:
  PASS, 91 tests and 4,805 assertions with two inherited warnings;
- full PHPUnit: PASS, 374 tests and 6,741 assertions with two inherited
  warnings; generated-projection verifier fixtures were moved from newly
  physical Card to still-generated Grid, and the locale boundary now asserts
  that removed Card prose cannot be resolved from the public UI overlay;
- changed PHP lint, JSON, graph and `git diff --check`: PASS.

## Browser evidence

Playwright Chromium verified Card at desktop light and Hero at mobile dark.
Card renders three cards across two example groups. Hero renders one each of
split, centered and compact layouts, one responsive image and two actions.
Both routes have zero global overflow and zero console warnings/errors.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| card | desktop-light | `fc3541a08808d90ecfd73cfb650872b0bfc99400bbf8ce195d9ca4cca8c677db` |
| hero | mobile-dark | `81558cc3b480a50b8838dd816b419a4aea2aa3fef6794a56bf3762e44bb0a8a9` |

Screenshots remain disposable evidence only.

## Readiness boundary

Batch 13 is complete; overall M3 is not. Eighteen of 32 component routes are
physical and 14 remain generated. Batch 14 migrates Grid and Figure.
