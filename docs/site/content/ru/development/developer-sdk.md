---
title: Developer и AI SDK
description: Безопасный путь inspect, scaffold, validate, preview и test для LEGO-артефактов Docara.
tags: [development, sdk, smart, design]
---

# Developer и AI SDK

SDK помогает добавить Smart-компонент или design-композицию, не меняя движок.
Он не создаёт новый renderer: все проверки и preview используют те же
registries, `SmartComponentGateway`, `LayoutComposer` и `PageBuilder`, что и
обычная сборка.

## Короткий LEGO-путь

Запускайте команды из корня инициализированного проекта:

```bash
docara doctor
docara list smart
docara inspect smart ui.alert --json
docara schema smart --json

docara scaffold smart project.notice-card --dry-run --json
docara scaffold --apply=<plan_id> --json
docara validate smart project.notice-card
docara preview smart --page=/ru/example/ --selector=project.notice-card
docara test smart project.notice-card --page=/ru/example/ --json
```

`--dry-run` возвращает список создаваемых файлов, SHA-256 их содержимого,
текущие hashes входов и `plan_id`. Apply принимает только этот plan и ещё раз
проверяет namespace, config, target paths и hashes. Если что-либо изменилось,
команда завершится ошибкой, ничего не дописывая частично.

## Что можно посмотреть

`list`, `inspect` и `schema` работают для Smart, layout, view, section, block,
provider, fixture, state и schema. Human и `--json` — два представления одного
`docara.operation_result.v1`: у diagnostics стабильны code, severity, owner,
provenance, source location и suggestion.

## Границы записи

Scaffold создаёт только новые project-owned файлы под `smart/` или `design/`.
Он не перезаписывает существующий ID и не пишет в `src/`, `resources/`,
`content/`, `assets/`, lock, build output либо внешний repository. Traversal,
symlink, hardlink, duplicate namespace и stale plan отклоняются.

Smart scaffold следует portable SIMAI Framework 5 Smart ABI v1. Design scaffold
создаёт связанный docs-shell-compatible layout, layout View Tree, section,
section View Tree и block. Результат сразу проходит registry validation и может
быть выбран в JSON-настройках страницы. Их можно доработать только в пределах
проверяемых JSON-схем.

## Structured QA

```bash
docara qa smart project.notice-card \
  --page=/ru/example/ --dry-run --json
```

Команда сначала публикует isolated production-path preview, затем создаёт
hash-bound план для desktop/mobile, light/dark и LTR/RTL. План явно фиксирует
проверяемую поверхность и locator: сам Smart, выбранный region или весь layout.
Browser runner остаётся необязательным development-инструментом. Сначала он
записывает production reference, привязанный к target HTML и полной странице,
затем отдельно снимает target и сравнивает пиксели с этим reference. Повторный
снимок текущего frame считается только stability check и не заменяет visual
diff. Report принимается только при совпадении plan, target, artifact и
reference hashes, полном наборе screenshots и нулевых a11y, console, overflow
и visual-diff дефектах. Обычные `init`, `build` и `verify-static` остаются
PHP-only и не требуют Node.js.

Для `test layout` и `qa layout` указанный route обязан фактически выбирать
этот layout. Несовпадение останавливается кодом
`SDK_TEST_LAYOUT_CONTEXT_MISMATCH` или `QA_LAYOUT_CONTEXT_MISMATCH`: команда не
подменяет проверяемый artifact похожей страницей.

## Optional MCP

Локальный stdio adapter запускается отдельно:

```bash
php tools/mcp-docara/server.php
```

Он публикует те же doctor/list/inspect/schema/plan/validate/test/QA operations и
не содержит собственной логики validation или rendering. По умолчанию apply
запрещён. После ручного просмотра plan можно запустить локальный процесс с
`--allow-writes`; root всё равно фиксируется текущим project directory, а apply
принимает только точный `plan_id`. MCP не входит в normal static runtime и не
добавляет Node либо daemon dependency.

## Когда нужна полная сборка

Изменение существующей Markdown-страницы можно проверить single-page build.
Добавление, переименование или удаление route, глобальная config/lang-правка и
изменение общих registries требуют full build, чтобы синхронно обновились menu,
search, navigation и receipts.
