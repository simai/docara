# Next Step

## Where We Stopped

Track `docara-consolidation` is attached to workflow:

- `source/workflow/2026-07-18-docara-product-ui-restoration.md`
- workflow title: Workflow: Docara product UI restoration

## Next Meaningful Goal

Реализовать следующую продуктовую вертикаль Docara: локальный поиск, правую навигацию по заголовкам, хлебные крошки, переходы назад/вперёд и простые настройки чтения — с наследуемой JSON-конфигурацией, pinned Simai Framework, схемами, отрицательными тестами, публичной документацией, browser evidence и независимой приёмкой, но без публичного релиза или миграции default-веток.

## Stages

1. Restore current state from the workflow and project memory.
2. Execute the next safe batch from the workflow.
3. Update track memory after the batch.

## Next Safe Batch

Start Batch 3 from exact candidate `83d677c7...`: inventory pinned Framework
and old Docara primitives, define the inherited JSON contract and negative
tests first, then implement local search, right heading TOC, breadcrumbs,
previous/next and simple reading settings. Freeze and independently accept one
exact candidate. Do not start landing work, release integration, default-branch
migration or `docara-mix` retirement.

## Checks

- Read `source/workflow/2026-07-18-docara-product-ui-restoration.md`.
- Check route/gates before writes.
- Update this track memory after progress.
