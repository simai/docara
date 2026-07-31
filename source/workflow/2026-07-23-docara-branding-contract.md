# Workflow: Docara branding contract

Date: 2026-07-23
Status: completed
Workflow ID: `2026-07-23-docara-branding-contract`
Parent track: `docara-consolidation`
Track ID: `docara-consolidation`
Owner: `docara`
Track: `docara-consolidation`
Goal: make Docara branding configurable, simple and Framework-native.
Process model: `docara_documentation_site_publication`
Launch record: `source/workflow/2026-07-23-docara-branding-contract.launch.yaml`

## Current Goal

Сделать брендирование Docara простым, предсказуемым и пригодным для разных
проектов: один логотип для обеих тем, отдельный favicon, возможность скрыть
видимый текст, контролируемые варианты компоновки и размеров без произвольных
CSS-классов в пользовательской конфигурации.

## Done when

- `branding.mode` выбирает `full`, `compact`, `logo` или `text`;
- `branding.size` выбирает `small`, `medium` или `large`;
- `logo` работает в обеих темах без обязательного `logo_dark`;
- `logo`-режим сохраняет доступное имя ссылки;
- длинные названия не ломают header;
- starter и документация объясняют контракт и наследование;
- схема, Smart manifest/views, unit tests и portable build проходят проверки;
- `/Users/rim/Sites/docara.test` пересобран и визуально проверен на desktop и mobile.

## Constraints

- Источник истины — `simai/docara`; продуктовые стили остаются внутри
  `docara.brand`, а размеры опираются на токены Simai Framework.
- Пользователь не передаёт HTML, template path или произвольный список классов.
- Если `mode` не задан, сохраняется обратная совместимость через `full`.
- Публикация релиза, merge и push не входят в этот batch.
- Не трогать несвязанные `output/` и `source/qa/` в рабочем дереве.

## Batches

1. Контракт и Smart views.
2. Starter assets и документация.
3. Автоматические проверки.
4. Локальная сборка и визуальная приёмка.

## Stages

- contract and registered Smart views;
- starter assets and user documentation;
- source/build verification;
- reversible local publication and browser acceptance.

## Evidence Plan

- automated test and formatter results;
- static build verification;
- exact served asset hashes;
- light/dark browser metrics and screenshots;
- local publication manifest and rollback path.

## Track Linkage

- Track: `docara-consolidation`;
- previous goal: Docara 2 audit corrections;
- scope: branding contract and local test-site publication only.

## Personal Memory

Personal memory decision: skip

Personal memory reason: repository workflow and evidence are the durable source
of truth; the user did not request a personal-memory update.

## Kaizen

The stable reusable lesson is encoded directly in product schema, Smart
manifest, starter and user documentation. No federation or personal-memory
proposal is needed. Transition evidence:
`stable_reusable_lessons_or_skip_reason`.

## Status

`completed`

## Result

- public config: `mode=full|compact|logo|text`,
  `size=small|medium|large`;
- default starter: one theme-independent SIMAI logo, compact one-line title,
  separate ICO favicon;
- product Smart: four registered views, accessible logo-only mode, Framework
  spacing/typography scale and overflow-safe title;
- documentation and starter explain the contract without exposing templates or
  arbitrary classes;
- source and served builds are byte-identical and retain a rollback copy.

Evidence: `source/workflow/evidence/2026-07-23-docara-branding-contract/acceptance.md`.

## Final Outcome

Docara supports four registered branding modes and three Framework-based
sizes, ships the supplied assets, documents the public contract and serves the
verified local build with a recorded rollback path.
