# Docara component detail editorial contract

Date: 2026-07-30
Status: completed

## Outcome

Every supported component page uses one concise, reader-first structure:

1. component name;
2. short explanation;
3. live example with source tabs, without a separate `Example` heading;
4. one section per parameter, with a human-readable heading;
5. a short explanation, a value table when the parameter has a finite set of
   values, and a focused live example when one is available.

Each explanation names the exact authoring parameter in its first sentence,
for example: `Параметр type определяет…`. Finite-value tables use the concise
headings `Значение` and `Результат` (`Value` and `Result` in English).

The page does not generate a generic `Parameters` wrapper heading or an
`Important` section. Technical parameter names remain available in source
examples and value tables, but are not appended to visible headings.

## Scope

- central component-detail projection;
- Russian and English generated pages;
- projector tests and static build evidence;
- no release, tag, merge or public deployment.

## Verification

- focused projector verification: `14` tests, `1,854` assertions;
- final projector and static-verifier rerun: `35` tests, `2,094` assertions;
- complete PHPUnit suite: `341` tests, `7,156` assertions;
- production build: `103` generated pages;
- static verification: `206` HTML documents, `18,866` local references,
  `0` broken references;
- generated-catalog audit: `30` component pages, `58` parameter sections,
  `37` finite-value tables, `20` parameter-focused examples and `52` example
  viewers;
- exact forbidden headings `Example`, `Parameters`, `Important` and
  `Variants`: `0` in generated component-page content;
- browser verification of badge, alert, button, table and figure pages;
- responsive badge inspection at `1440 x 1000` and `390 x 844`, without page
  overflow.

## Editorial decisions

- A separate `Example` heading is redundant: the live example follows the
  component description directly.
- Each parameter is a plain reader-facing section such as `Badge type`,
  `Colour scheme` or `Size`; raw identifiers stay in the source tab.
- Finite enums and booleans receive a compact value table followed by an
  example of those values.
- Free-form strings and numeric inputs do not receive invented value tables;
  they keep a concise explanation, required/default information and a source
  example where one exists.
- Generic `Parameters`, `Important`, `Variants`, source and limitation filler
  sections are not generated.

## Delivery

The exact local build was published to `https://docara.test/` after the local
action gate passed. The previous site build is preserved at:

`/Users/rim/Sites/docara.test/.docara-backups/component-parameter-editorial-20260730-144703/build_production`

Detailed verification is recorded in
`source/workflow/evidence/2026-07-30-docara-component-detail-editorial-contract/verification.md`.

No merge, tag, package publication or public deployment was performed.
