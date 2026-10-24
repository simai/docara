---
title: SDK для разработчика и AI-агента
description: Безопасный путь inspect, scaffold, validate, preview и test для LEGO-артефактов Docara.
tags: [development, sdk, smart, design, mcp, ai, safety]
---

# SDK для разработчика и AI-агента

SDK помогает добавить Smart-компонент или композицию оформления, не меняя
движок. Он не создаёт нового рендерера: все проверки и предпросмотр используют
те же реестры, `SmartComponentGateway`, `LayoutComposer` и `PageBuilder`, что и
обычная сборка.

## Короткий LEGO-путь

Запускайте команды из корня инициализированного проекта:

```bash
docara capabilities --json
docara doctor
docara list smart
docara inspect smart ui.alert --json
docara schema smart --json

PLAN_SHA256="$(docara scaffold smart project.notice-card --dry-run --json | php -r '$r=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); echo $r["data"]["plan_id"];')"
docara scaffold --apply="$PLAN_SHA256" --json
docara validate smart project.notice-card
docara preview smart --page=/ru/project-demos/ --selector=ui.dropdown
docara test smart ui.dropdown --page=/ru/project-demos/ --json
```

`capabilities` — первая команда для любого AI-агента. Она сообщает
операции, параметры, схемы и статусы возможностей именно той версии, которая
установлена в этом проекте. Агент не должен переносить предположения из другого
проекта или из более новой Docara.

`--dry-run` возвращает список создаваемых файлов, SHA-256 их содержимого,
текущие хеши входных файлов и `plan_id`. Применение (`--apply`) принимает
только этот план и ещё раз проверяет пространство имён, настройки, пути
назначения и хеши. Если что-либо изменилось, команда завершится ошибкой и
ничего не запишет частично.

## Путь разработчика и AI-агента

Все шаги ниже выполняют одни и те же внутренние службы Docara. У командной
строки, вывода `--json` и необязательного MCP нет отдельных правил проверки
или вывода HTML.

### 1. Найдите возможность

```bash
docara doctor --json
docara list smart --json
docara atlas --json
docara inspect smart project.install-builder --json
docara schema smart --json
```

Команды чтения ничего не меняют. Результат содержит постоянные операцию,
предмет, код и код завершения, владельца, безопасный путь и место в файле,
происхождение значения и подсказку.

### 2. Составьте план и посмотрите результат

```bash
PLAN_SHA256="$(docara scaffold smart project.notice-card --dry-run --json | php -r '$r=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); echo $r["data"]["plan_id"];')"
docara preview smart --page=/ru/project-demos/ --selector=ui.dropdown --json
docara test smart ui.dropdown --page=/ru/project-demos/ --json
```

Пробный запуск (`--dry-run`) возвращает точные пути и содержимое файлов, хеши
входных файлов и `plan_id`. Предпросмотр вызывает тот же
`PreviewKernel/PageBuilder`, что и рабочая сборка, публикуется отдельно и не
проходит проверку рабочей сборки.

### 3. Примените план по хешу (hash-bound apply)

```bash
docara scaffold --apply="$PLAN_SHA256" --json
docara validate smart project.notice-card --json
```

Применение разрешено только после явного пробного запуска и повторной
проверки всех хешей. Устаревший план, изменённый входной файл, уже
существующий файл или файл, отличающийся только регистром, выход за каталог
через `..` или ссылку, запись в файлы движка, lock, результаты сборки или
внешние каталоги завершают команду с ошибкой. Ни один файл при этом не
меняется.

### CLI, JSON и MCP

| Действие | Human/JSON operation | Optional MCP tool | Один service |
| --- | --- | --- | --- |
| Atlas | `docara atlas [--json]` | `docara_atlas` | `DesignAtlasService` |
| Inspect | `docara inspect … [--json]` | inspect tool | discovery/SDK service |
| Schema | `docara schema … [--json]` | schema tool | schema service |
| Scaffold plan/apply | `docara scaffold …` | plan/apply tool | `ScaffoldService` |
| Validate/test/QA | `docara validate|test|qa …` | matching tools | accepted validators/PreviewKernel |

Адаптер MCP запускается отдельно и по умолчанию только читает. Ключ
`--allow-writes` открывает то же применение плана по хешу; защита корня
проекта и диагностика при этом работают. Node.js 20+ нужен для `build`,
браузер — только для необязательной визуальной проверки, а `init` и
`verify-static` выполняются через PHP.

### Если операция отклонена

```text
SDK_WRITE_PATH_UNSAFE
source: .docara-preview
pointer: /operations/preview/output
suggestion: remove the unsafe symlink and repeat dry-run
```

Не правьте план, отчёт или квитанцию. Устраните причину в исходных файлах
проекта, повторите поиск, проверку и пробный запуск и получите новый план.

## Что можно посмотреть

`list`, `inspect` и `schema` работают для Smart, layout, view, section, block,
provider, fixture, state и schema. Human и `--json` — два представления одного
`docara.operation_result.v1`: у diagnostics стабильны code, severity, owner,
provenance, source location и suggestion.

`schema smart --json` возвращает byte-exact Framework-owned схему переносимого
manifest, который создаёт `scaffold smart`: `smart.manifest.schema.json` из
зафиксированной owner revision. После неё применяется отдельно названная
Docara policy `docara.smart_admission.v1`; это не второй формат. В результате
также есть машинная identity контракта:
`sf.smart_artifact_abi` / `1.0.0` /
`sf-smart-artifact-abi-v1`. Поэтому manifest из dry-run можно проверить этой
схемой напрямую, без конвертации в package-specific формат.

`inspect smart` показывает эту же neutral identity для Framework-, Docara- и
project-owned компонентов. Поля `provider_adapter` и `template_abi` отдельно
объясняют, как конкретный provider подключил artifact. Историческое имя
`sf5.smart.artifact.v1` публикуется только как
`storage_compatibility_alias`, а не как второй Smart-контракт.

## Границы записи

`scaffold` создаёт только новые файлы проекта в `smart/` или `design/`. Он не
перезаписывает существующий идентификатор и не пишет в `src/`, `resources/`,
`content/`, `assets/`, lock-файл, результат сборки или внешний репозиторий.
Выход за каталог через `..`, символические и жёсткие ссылки, повтор
пространства имён и устаревший план отклоняются.

Smart scaffold следует portable SIMAI Framework 5 Smart ABI v1. Design scaffold
создаёт связанный docs-shell-compatible layout, layout View Tree, section,
section View Tree и block. Результат сразу проходит registry validation и может
быть выбран в JSON-настройках страницы. Их можно доработать только в пределах
проверяемых JSON-схем.

## Structured QA

```bash
docara qa smart ui.dropdown \
  --page=/ru/project-demos/ --dry-run --json
```

Команда сначала публикует isolated production-path preview, затем создаёт
hash-bound draft-план для desktop/mobile, light/dark и LTR/RTL. План явно
фиксирует проверяемую поверхность и locator: сам Smart, выбранный region или
весь layout. Browser runner остаётся необязательным development-инструментом и
сначала записывает reference draft. Затем PHP-команда
`docara qa --finalize-reference=<draft-plan-id>` проверяет все PNG и создаёт
новый immutable finalized plan. Его `reference_id` охватывает полный manifest:
target, страницу, artifact, упорядоченные scenario ID, screenshot paths и
SHA-256 каждого изображения.

Только finalized plan передаётся browser runner для сравнения и команде
`docara qa --verify=<finalized-plan-id>`. Verification повторно вычисляет
полную identity и manifest seal, проверяет реальные candidate/reference bytes
и не доверяет одному полю `visual_diff_pixels=0`. Report принимается только
при полном наборе screenshots и нулевых a11y, console, overflow и visual-diff
дефектах. `init` и `verify-static` выполняются через PHP. Команда `build`
дополнительно использует Node.js 20+ и точную поставку Framework для сборки
Recipe. Подготовка описана в [быстром старте](/ru/start/quick-start/).

Для `test layout` и `qa layout` указанный route обязан фактически выбирать
этот layout. Несовпадение останавливается кодом
`SDK_TEST_LAYOUT_CONTEXT_MISMATCH` или `QA_LAYOUT_CONTEXT_MISMATCH`: команда не
подменяет проверяемый artifact похожей страницей.

## Optional MCP

Локальный stdio adapter запускается отдельно:

```bash
php tools/mcp-docara/server.php
```

Он публикует те же capabilities/doctor/list/inspect/schema/plan/validate/test/QA operations и
не содержит собственной логики validation или rendering. По умолчанию apply
запрещён. После ручного просмотра plan можно запустить локальный процесс с
`--allow-writes`; root всё равно фиксируется текущим project directory, а apply
принимает только точный `plan_id`. MCP не входит в normal static runtime и не
добавляет Node либо daemon dependency.

## Когда нужна полная сборка

Изменение существующей Markdown-страницы можно проверить сборкой одной
страницы. Добавление, переименование или удаление страницы, правка общих
настроек или `lang.json` и изменение общих реестров требуют полной сборки,
чтобы вместе обновились меню, поиск, навигация и квитанции.
