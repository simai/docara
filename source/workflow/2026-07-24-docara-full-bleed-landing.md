# Workflow: Docara full-bleed landing correction

Date: 2026-07-24
Status: completed
Owner: `docara`
Companions: `sf5`, `ux`, `tester`
Track: `docara-consolidation`

## Goal

Исправить декларативную компоновку landing preset так, чтобы зарегистрированные
полноширинные блоки доходили до краёв viewport, а обычное содержимое сохраняло
ограниченную ширину и Framework spacing.

## Done When

- main-region wrapper не добавляет внешний gutter полноширинным блокам;
- Hero имеет `x = 0` и ширину viewport на desktop и mobile;
- внутреннее содержимое Hero сохраняет адаптивный Framework padding;
- обычные блоки по-прежнему ограничены выбранной шириной;
- тесты, production build, static verification и browser smoke проходят;
- exact build опубликован локально на `docara.test` с rollback.

## Batch

1. Исправить landing composition CSS и regression assertions.
2. Пересобрать, проверить и опубликовать exact local build с backup.
3. Проверить desktop/mobile bounds и отсутствие horizontal overflow.

## Boundaries

- не менять Markdown-содержимое и изображения;
- не добавлять произвольные author classes или второй layout runtime;
- не выполнять commit, push, merge, release или публичную публикацию.

## Evidence

- `source/workflow/evidence/2026-07-24-docara-full-bleed-landing/acceptance.md`;
- PHPUnit, Pint, build, verify-static, deployed-tree diff;
- browser bounds and screenshots at desktop/mobile.

## Result

PASS. The registered full-width Hero now reaches both viewport edges on desktop
and mobile. Ordinary content remains bounded, the exact verified build is
published locally, and rollback is available at
`/Users/rim/Sites/docara.test/.docara-backups/full-bleed-20260724-002827`.

Evidence:
`source/workflow/evidence/2026-07-24-docara-full-bleed-landing/acceptance.md`.
