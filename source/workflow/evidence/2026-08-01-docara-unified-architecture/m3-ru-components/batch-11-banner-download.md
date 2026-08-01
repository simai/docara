# M3.3 batch 11: Banner and Download

Date: 2026-08-01

Verdict: PASS

Parent SHA: `b5cf224`

Candidate SHA: commit containing this evidence

## Ownership and shared runtime

- `/ru/components/banner/` -> `docs/site/content/ru/components/banner.md`;
- `/ru/components/download/` -> `docs/site/content/ru/components/download.md`;
- matching portable starters live under `stubs/portable/content/ru/components/`.

Both pages use the existing generic `typed_directive` IR node and the one
renderer-registry/PageBuilder path. No Banner- or Download-specific compiler,
IR node, registry or page renderer was added. Typed definitions retain only
structural parameters and now point `docs_ref` at their physical owner.

Banner documents all four semantic types with concise usage boundaries.
Download provides working download/open actions against the existing content
asset, explains optional metadata and uses the asset's real SHA-256 prefix and
suffix rather than invented evidence.

## Legacy reduction and rollback

- generated-route allowlist: `35 -> 33`;
- Russian language-pack records/max: `33 -> 31`;
- Russian generated component-detail receipt: `20 -> 18`;
- physical component ownership: `11/32 -> 13/32`;
- zero-reference localized examples retired:
  - `docara.banner.ru.md`, old SHA-256
    `9fdf4db2af4f7d15e455219ec5145246de4f040d2d19b0996cd0500e6908de9f`;
  - `docara.download.ru.md`, old SHA-256
    `87f122a271a95a5e431564f3dcd8fcaffe6a31b73785ade6cd3761abbc7ba6c8`.

Rollback is a revert of this checkpoint commit; the parent restores exact
localized examples, pack records and generated-route ownership.

## Verification

```text
php ../../docara build m3-banner-download-full
PASS — 103 pages

php ../../docara build m3-banner-single --page=/ru/components/banner/
PASS — 1 selected page; full/single tree diff empty

php ../../docara build m3-download-single --page=/ru/components/download/
PASS — 1 selected page; full/single tree diff empty

php ../../docara verify-static build_m3-banner-download-full
PASS — 206 HTML pages, 18,916 local references, 0 broken
```

- content-addressed full tree SHA-256:
  `b21af9503ccc9abd7c379344207cc89ed9c2059256d1def1aa652a8182806571`;
- Banner HTML SHA-256:
  `2643d690b72e55e5494d7b8f4336cbd8e3357a4721c9e95dff94e564d9f551ba`;
- Download HTML SHA-256:
  `111b90da0354da94f4aca9d7e274511b1a567beb481986ae12bffa4fb33f60d0`;
- focused compiler/PageBuilder/catalog/allowlist/contract tests: PASS,
  53 tests and 4,141 assertions with 2 inherited warnings;
- changed-file Pint, PHP lint, JSON, graph and `git diff --check`: PASS.

## Browser evidence

Playwright Chromium verified desktop light and mobile dark. Banner renders
info/success/warning/danger with `role=status`. Download renders three working
asset links: two have the native `download` attribute and `action=open` does
not. Both pages have zero global overflow and zero console warnings/errors.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| banner | desktop-light | `e543b357eb9adafaf8ab6deb6299421be52df28477f416caac3b052f0e4a2153` |
| banner | mobile-dark | `31df416f4adc60f162dee25e40c832b35eb9b22674a6cf878e60de2779de574c` |
| download | desktop-light | `32a200b34f159b19ac799f27260e489860114e355c4fd07a8e72b1216ec7574e` |
| download | mobile-dark | `c0677511999c581a0ce0abd012d9549adf87fdd9a0017ae761a125b85cec9221` |

Screenshots remain disposable evidence only.

## Readiness boundary

Batch 11 is complete; overall M3 is not. Thirteen of 32 component routes are
physical and 19 remain generated. Batch 12 migrates Button, Icon and Kbd.
