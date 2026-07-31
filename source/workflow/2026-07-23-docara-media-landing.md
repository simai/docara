# Workflow: Docara media landing

Date: 2026-07-23
Status: completed
Owner: `docara`
Companions: `sf5`, `ux`, `designer`, `dev`, `tester`
Track: `docara-consolidation`
Audit: `source/workflow/2026-07-23-docara-landing-visual-audit.md`

## Goal

Превратить демонстрационный landing Docara из текстовой документационной
страницы в выразительный, адаптивный и доказательный продуктовый лендинг,
сохранив простое Markdown-авторство, PHP-only сборку и Simai Framework как
единственный UI-контракт.

## Done When

- landing layout поддерживает полноширинные зарегистрированные блоки без
  отрицательных отступов и произвольных классов автора;
- обычный Markdown остаётся в ограниченном внутреннем контейнере;
- `hero`, `features` и `logos` получают согласованный media contract;
- добавлены только `showcase` и `promo`, без generic page constructor;
- демонстрационный landing использует реальные/готовые медиафайлы;
- catalog, документация и starter отражают новый контракт;
- PHPUnit, formatter, build, static verification и browser QA на desktop/mobile
  в light/dark проходят;
- exact build опубликован только на локальный `docara.test` с backup и rollback.

## Context

Предыдущий батч добавил `hero` и `logos`, но живой landing всё ещё содержит
только 32px brand mark. Поблочный аудит Diplodoc показал, что Docara не хватает
четырёх визуальных ролей: hero media, feature markers, product showcase и promo.

## Constraints And Risks

- сохранять текущие незакоммиченные branding/landing changes;
- не менять unrelated `output/` и `source/qa/`;
- не копировать Gravity UI/Page Constructor и не добавлять React runtime;
- не разрешать author-defined CSS classes, templates, arbitrary dimensions;
- изображения остаются project assets, renderer назначает безопасный preset;
- публичный release, merge, push и package publication запрещены;
- локальная публикация только после backup и exact-tree verification.

## Batch Plan

| Batch | Goal | Work | Verification | Status |
| --- | --- | --- | --- | --- |
| A | Media foundation | landing full-width contract, media presets, hero/features/logos | focused renderer + layout tests | completed |
| B | Product proof | `showcase`, `promo`, catalog, examples, language packs | catalog and negative fixtures | completed |
| C | Demonstration | media assets, complete landing, docs/starter | build + static verification | completed |
| D | Acceptance | local backup/publication, desktop/mobile light/dark QA | exact diff + browser evidence | completed |

## Allowed Files

- `docs/site/**`
- `resources/component-catalog/**`
- `resources/language-packs/**`
- `resources/portable/**`
- `resources/publisher/**`
- `src/ComponentCatalog/**`
- `src/PortableSite/**`
- `stubs/portable/**`
- `tests/**`
- this workflow and its evidence directory
- local `/Users/rim/Sites/docara.test/**` after the publication gate

## Forbidden Actions

- commit, push, merge, tag or release;
- delete or rewrite unrelated user changes;
- public or production publication;
- new frontend runtime dependency.

## Evidence Plan

- focused PHPUnit commands and full suite;
- Pint, Composer validation and `git diff --check`;
- deterministic build and `verify-static`;
- generated catalog counts and exact component pages;
- screenshots/DOM evidence at desktop and mobile, light and dark;
- local backup path and deployed-tree equality.

## Progress

### Batches A–D

- Status: completed.
- Done: full-width landing composition, bounded media presets, `showcase` and
  `promo`, catalog projection, documentation, generated assets, cache-versioned
  publisher shell, local publication and browser acceptance.
- Verification: 319 PHPUnit tests / 4559 assertions; Pint; Composer validation;
  90-page production build; 198 HTML pages / 10,908 checked local references /
  0 broken; exact deployed-tree equality; desktop/mobile light/dark browser QA.
- Rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/20260723-235042`.
- Evidence:
  `source/workflow/evidence/2026-07-23-docara-media-landing/acceptance.md`.

## Final Result

- Result: PASS for the local candidate and `docara.test`.
- Verification: complete; see the acceptance evidence.
- Remaining: no work inside this workflow.
- Follow-up: commit/push/release only in a separately authorized workflow.
