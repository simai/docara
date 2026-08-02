# Проектный граф Docara

`graph/` — машинно-читаемый источник цели, архитектурных решений, этапов,
ограничений и доказательств Docara. Человекочитаемая проекция находится в
[`docs/specification`](../docs/specification/README.md).

## Правило источника истины

- `graph/specs` хранит принятые объекты управления и архитектурные связи;
- `docs/specification` объясняет те же решения людям;
- `source/workflow` хранит исполняемые задания и свидетельства конкретных
  попыток;
- код и тесты подтверждают реализацию;
- `graph/generated/ai-context/docara-unified.json` — детерминированная проекция
  текущего canonical state и никогда не переопределяет цель.

Если описание и граф расходятся, сначала принимается новое decision-объект в
`graph/specs/decisions`, затем синхронизируются ТЗ и реализация.

## Структура

- `dna/project-dna.json` — назначение и неизменяемые принципы;
- `specs/goals` — пользовательский итог;
- `specs/stages` — последовательные вехи M0–M5;
- `specs/batches` — ограниченные исполняемые пакеты;
- `specs/features` — целевые возможности продукта;
- `specs/requirements` — проверяемые требования;
- `specs/decisions` — принятые архитектурные решения;
- `specs/risks` — известные риски и меры сдерживания;
- `specs/gates` — условия перехода между этапами;
- `specs/implementation-mappings` — связь требований с кодом, тестами и
  evidence;
- `specs/metrics` — показатели прогресса без ложного приравнивания к
  production readiness;
- `generated/ai-context` — производный компактный пакет для нового task.

## Регенерация и freshness gate

Из корня репозитория:

```bash
php scripts/project-context.php generate
php scripts/project-context.php check
```

`generate` читает `graph/graph.json` и разрешённые из него current goal/stage/
batch specs, записывает компактный context атомарно и без текущего времени.
`check` требует byte-identical проекцию и дополнительно сверяет
`START.md`, `STATUS.yaml`, `ACTIVE.md`, `NEXT.md`, `RESULT.md` и LEGO-roadmap.
Изменение current stage, batch, candidate, next action или evidence без
регенерации и handoff-синхронизации завершает проверку ошибкой.

Generated context нельзя править как самостоятельное решение. Сначала меняют
canonical graph/specs, затем handoff и только после этого запускают `generate`
и `check`.

## Как работать

1. Прочитать `docs/specification/README.md`.
2. Прочитать `source/handoff/docara-unified-architecture/START.md`.
3. Проверить active goal/stage/batch/next action в canonical graph.
4. Запустить `php scripts/project-context.php check`.
5. Выполнять только разрешённый current gate и записать evidence до перехода.

История прежнего task — полезное свидетельство, но не runtime-инструкция.
