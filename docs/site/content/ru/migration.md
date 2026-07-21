# Миграция

`docara init --portable` создаёт новый переносимый проект и намеренно не
переписывает существующий legacy-проект. Миграция — отдельная проверяемая
операция: сохраните исходники, перенесите поддерживаемые данные, соберите оба
варианта и сравните публичный результат.

:::ui.alert
{"type":"info","variant":"default","title":"Без скрытой конвертации","supporting-text":"Существующие config.php, .settings.php и source/_core остаются нетронутыми, пока владелец явно не запускает миграцию.","closable":false,"aria-label":"Переносимый режим не меняет legacy проект автоматически"}

:::

## Маршруты перехода

- [С legacy-сайта](/migration/legacy/)
- [С docara-template](/migration/template/)
- [С Mix на Vite](/migration/mix-to-vite/)
- [Диагностика частых проблем](/migration/troubleshooting/)

## Общий безопасный порядок

1. Зафиксируйте исходную revision, рабочую сборку и список публичных URL.
2. Создайте отдельный portable-каталог; не запускайте update поверх legacy.
3. Перенесите Markdown, ассеты и только поддерживаемые настройки.
4. Соберите старый и новый сайты, затем сравните содержание, ссылки и
   адаптивное поведение.
5. Подготовьте резервную копию опубликованного каталога и проверенный rollback.
6. Переключайте document root только после `verify-static` и browser-приёмки.

Portable output собирается PHP-командой и не требует Node.js. Vite нужен только
разработчику, который меняет исходные ассеты темы.

## Выведенные ручные маршруты компонентов

Компоненты теперь документируются одним сгенерированным каталогом. Старые URL
сохраняются декларативно в `redirects.json`, а не дублирующими Markdown-
страницами:

| Старый маршрут | Текущий источник |
| --- | --- |
| `/components/alert/` | [`ui.alert`](/components/catalog/ui.alert/) |
| `/components/button/` | [`ui.button`](/components/catalog/ui.button/) |
| `/components/card/` | [`docara.card`](/components/catalog/docara.card/) |
| `/components/code/` | [`native.code`](/components/catalog/native.code/) |
| `/components/cta/` | [`docara.cta`](/components/catalog/docara.cta/) |
| `/components/features/` | [`docara.features`](/components/catalog/docara.features/) |
| `/components/steps/` | [`docara.steps`](/components/catalog/docara.steps/) |
| `/components/table/` | [`native.table`](/components/catalog/native.table/) |
| `/components/tabs/` | [запись `ui.tabs` в каталоге](/components/catalog/) |

Starter уже содержит эти девять redirects. Builder проверяет targets и
collisions, создаёт статические fallback-страницы, а `verify-static` сверяет их
с `.docara/redirects.json`. Не возвращайте дублирующие страницы в Markdown и
не добавляйте второй hosting-only список без отдельной причины.

Пакет `docara-mix` не нужен новым проектам. Архивировать его можно только после
миграции всех активных потребителей, clean build/watch проверки и
подтверждённого zero-reference scan.
