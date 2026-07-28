# Resolved page plan

Для каждой страницы Docara создаёт объяснимый план до записи HTML.

Файл `build_<environment>/.docara/resolved-page-plans.json` содержит массив
страниц со следующими данными:

| Поле | Смысл |
| --- | --- |
| `resolved_page_plan.configuration` | Итог после наследования |
| `trace` | Входные файлы, роль, schema и SHA-256 |
| `provenance` | Какой файл установил каждое значение |
| `canonical_hash` | Хэш канонического плана |
| `component_runtime` | Вызовы компонентов и asset plan |
| `output`, `url` | Физический и публичный маршруты |
| `declarative.plan.schema` | Версия полного `docara.resolved_render_plan.v2` |
| `declarative.plan.layout.view_tree` | Разрешённый безопасный каркас макета |
| `declarative.plan.regions.*[].slots` | Именованные слоты экземпляра Section |
| `declarative.plan.regions.*[].blocks` | Block calls со stable ID, slot и Smart |
| `declarative.plan.diagnostics` | Результат композиции и View Tree validation |

План не является вторым источником для редактирования. Это генерируемый
диагностический и интеграционный артефакт. Изменяйте Markdown или JSON, затем
пересобирайте сайт.

Итоговые поля `/reading/breadcrumbs`, `/reading/toc`, `/reading/toc_depth` и
`/reading/previous_next` находятся в `configuration`, а их источник — в
`provenance`. Сами вычисленные хлебные крошки, outline и соседние ссылки в план
не записываются как редактируемые настройки: они детерминированно выводятся из
контента и канонической топологии при сборке.
