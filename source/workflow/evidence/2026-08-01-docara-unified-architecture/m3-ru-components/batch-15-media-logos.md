# M3.3 batch 15: Media and Logos

Date: 2026-08-01

Verdict: PASS

Parent SHA: `835df6db99ef214cb8427ac1dccb1a067ef0c45b`

Candidate SHA: commit containing this evidence

## Ownership and runtime

- `/ru/components/media/` -> `docs/site/content/ru/components/media.md`;
- `/ru/components/logos/` -> `docs/site/content/ru/components/logos.md`;
- matching portable starters live under `stubs/portable/content/ru/components/`.

Both routes reuse generic `typed_directive` IR and one renderer registry/
PageBuilder. Media covers left/right/stacked layout, ratios and one accessible
image. Logos covers text, links, content-owned images and normal/muted tone.
No family-specific compiler, IR, registry, gateway or pipeline was added.

## Legacy reduction and rollback

- generated-route allowlist: `26 -> 24`;
- Russian language-pack records/max: `24 -> 22`;
- Russian generated component-detail receipt: `11 -> 9`;
- physical component ownership: `20/32 -> 22/32`;
- zero-reference localized examples retired:
  - `docara.media.ru.md`, old SHA-256
    `b2a3bfa14d8a6b8414a72f06723861ac314b38c1f0fb436d18cd1f6ffde8209b`;
  - `docara.logos.ru.md`, old SHA-256
    `ad1da4359e5e27b07b046ce88b0be6e59f7b4532ddd2f10146f0ac2e13753886`.

Accepted Logos capability checks now read the physical Markdown owner, and
generated-verifier mutation fixtures moved to still-generated Steps. Revert of
this checkpoint restores exact pack, route and example state.

## Verification

- full build: 103 pages;
- Media/Logos isolated builds: one selected page each;
- full/isolated tree SHA-256:
  `0a9fb7fe52c78f0659a1f30f41facabcc050150a48121b7c63e4a7768c3ad462`;
- static: 206 HTML, 18,937 local references, 0 broken;
- Media HTML:
  `2fe34c2a411923240e45d1e82041a6f4494dc6455a576c352d65f995fe8cf9b0`;
- Logos HTML:
  `af9f220cf76bcb78e60c3b5b7e8d8458b80e0b9bb5b1eda259dcf821c4a9b3a8`;
- focused suite: PASS, 100 tests and 4,783 assertions, two inherited warnings;
- full PHPUnit: PASS, 374 tests and 6,569 assertions with two inherited
  warnings; lint, JSON, graph and diff checks: PASS.

## Browser evidence

Desktop-light Media renders left/right variants with loaded images. Mobile-dark
Logos renders three groups, four links and two loaded images. Both have zero
overflow and zero console warnings/errors.

| Route | Mode | Screenshot SHA-256 |
| --- | --- | --- |
| media | desktop-light | `03017463684040b373971bc38eae09927efe65d3311aabbaaf881a3563c00cdb` |
| logos | mobile-dark | `e77516cca346d61af9ea6265deb09ac8c84b17a6bc03e6cf20517c326cc0ac45` |

## Readiness boundary

Batch 15 is complete; overall M3 is not. Twenty-two of 32 component routes are
physical and 10 remain generated. Batch 16 migrates Diagram and Math.
