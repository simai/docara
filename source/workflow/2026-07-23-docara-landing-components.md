# Workflow: Docara landing components

Date: 2026-07-23
Status: completed_local_candidate
Track: `docara-consolidation`
Goal: расширить Docara минимальным набором компонентов, достаточным для
сборки полноценного адаптивного лендинга из Markdown и Simai Framework.
Process model: `docara_documentation_site_publication`
Current Goal: расширить Docara минимальным набором typed-компонентов для полноценного адаптивного лендинга.
Done When: hero и logos реализованы, каталог и лендинг собраны, тесты и browser QA проходят.
Stages: research; implementation; documentation; integrated acceptance.
Batches: reference audit; typed components; catalog and demo; local verification.
Evidence Plan: automated tests; static verification; browser QA; reversible local publication.
Track Linkage: docara-consolidation.
quality_controls: human_centered_simplicity
simplicity_review: source/workflow/evidence/2026-07-23-docara-landing-components/human-centered-simplicity-review.json

## Current Goal

Расширить компоненты Docara для построения лендингов по изученным паттернам
Diplodoc.

## Final Outcome

Docara получает минимальный проверенный набор Markdown-блоков, из которого
автор собирает полноценный адаптивный лендинг без отдельного frontend runtime.

## Trajectory Context

Продолжает консолидацию Docara 2: продуктовые typed-компоненты остаются в
Docara, используют Simai Framework и становятся доступными через единый
generated catalog.

## Primary Scenario

Автор документации создаёт продуктовый лендинг в одном Markdown-файле:
объясняет продукт на первом экране, показывает преимущества и доверие,
описывает процесс и завершает страницу понятным действием.

## Done When

- исследование Diplodoc и точная карта `reference -> Docara` сохранены;
- существующие `features`, `columns`, `steps`, `card` и `cta` переиспользованы;
- добавлены только недостающие `hero` и `logos` с fail-closed Markdown
  контрактами;
- новые блоки используют семантический HTML и утилиты Simai Framework;
- generated catalog содержит описание, синтаксис и живые примеры;
- `/landing/` демонстрирует полный лендинг, а не отдельные несвязанные блоки;
- сборка, static verification, component tests и browser QA проходят на
  desktop и mobile, в светлой и тёмной теме.

## Reference Evidence

- public landing: `https://diplodoc.com/`;
- organization: `https://github.com/diplodoc-platform`;
- `docs` revision `466d82ac7377fd380cd03f1544304675b1b851f0`;
- `components` revision `43d4fe6b20552f550bb481ab09cc5e2f9676d038`;
- `page-constructor-extension` revision
  `93af2c30876e34d3657981c515d060c55d38f782`;
- `transform` revision `39885ba2586c519f4db461143a5c0bf5ffca814f`.

Diplodoc separates documentation syntax, client chrome and a YAML-driven Page
Constructor. Docara keeps its PHP-only model: native Markdown, bounded typed
directives, registered Smart components and immutable Framework assets.

## Component Map

| Diplodoc landing job | Docara decision |
| --- | --- |
| header/hero | add `docara.hero` |
| benefits grid | reuse `docara.features` |
| trusted users/logo cloud | add `docara.logos` |
| architecture/integration/deployment | reuse `docara.columns` and `docara.steps` |
| contribution and try-it sections | reuse `docara.card`, `docara.columns`, `docara.cta` |
| individual action | reuse native link or `docara.cta` |

## Simplest Complete Alternative

Do not port Gravity UI Page Constructor, React runtime or its entire block
catalogue. Two missing semantic primitives plus five existing Docara blocks
cover the reference landing jobs with less authoring and runtime surface.

## Batches

- [x] Reference audit and component map.
- [x] `hero` and `logos` contracts, renderers and negative tests.
- [x] Generated catalogue, language packs and complete landing example.
- [x] Build, static verification and browser acceptance.

## Stages

- [x] research and design-system alignment;
- [x] bounded component implementation;
- [x] documentation and demonstration;
- [x] integrated acceptance.

## Evidence Plan

- exact reference repository revisions and source excerpts;
- focused and full PHPUnit results;
- deterministic component catalogue and static verifier;
- desktop/mobile, light/dark screenshots and semantic DOM checks;
- local publication manifest and rollback path.

## Track Linkage

This goal follows the accepted Docara branding batch and remains inside the
`docara-consolidation` track. Public release, merge, push and production
publication are outside this goal.

## Constraints

- preserve the uncommitted branding batch and unrelated `output/`/`source/qa/`;
- no copied Diplodoc/Gravity UI implementation or visual identity;
- no arbitrary author HTML, CSS classes or template paths;
- no new frontend runtime dependency;
- user-facing strings stay in content or language packs;
- generated `build_*` is not an authoring surface.

## Personal Memory

Personal memory decision: skip

Personal memory reason: workflow and product documentation are the durable
source of truth; no personal-memory update was requested.

## Kaizen Review

`stable_reusable_lessons_or_skip_reason`: reference products should be mapped
to user jobs before components are added. Reusing five existing blocks and
adding only two missing primitives produced a complete landing vocabulary
without a second page-constructor runtime.

Browser acceptance also exposed a reusable integration rule: Framework
`.source` surfaces must include their ready state (`init`) in deterministic
static output. Relying on later hydration made code blocks occupy layout space
at `opacity: 0`.

## Result

- added `docara.hero` and `docara.logos` as product-owned typed components;
- added Russian and English descriptions plus executable examples;
- expanded the generated catalog from 17 to 19 entries and the supported
  surface from 12 to 14 entries;
- rebuilt `/ru/landing/` from `hero`, `features`, `logos`, `steps`, `columns`
  and `cta`;
- documented the complete landing vocabulary in
  `docs/site/content/ru/components/syntax.md`;
- made native code blocks visible without hydration by emitting
  `class="source init ..."`;
- published the exact verified build to `docara.test` with rollback backup
  `/Users/rim/Sites/docara.test/.docara-backups/20260723-174925`.

## Acceptance Evidence

- full PHPUnit: 316 tests, PASS;
- Pint: PASS;
- `git diff --check`: PASS;
- production build: 88 canonical pages, PASS;
- static verifier: 194 HTML pages, 10,640 local references, 0 broken;
- deployed tree equals `docs/site/build_production` (`diff -qr`: PASS);
- desktop: 1 H1, all landing blocks present, no horizontal overflow;
- mobile 390x844: one-column hero/features, two-column logos, no horizontal
  overflow;
- light and dark themes: PASS;
- `docara.hero` and `docara.logos` generated detail pages: live examples PASS;
- browser console errors/warnings: none.

## Nonclaims

- no public release, merge, commit or push was performed;
- no production publication was performed;
- no claim is made that Docara implements every Diplodoc/Page Constructor
  block; it implements the smallest complete landing vocabulary required by
  the audited reference page.
