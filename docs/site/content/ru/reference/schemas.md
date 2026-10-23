---
description: "Схемы JSON файлов настроек и служебных файлов Docara: какой файл какой схеме соответствует и что сборка проверяет сверх схемы."
---

# Схемы JSON

Схемы JSON описывают каждый файл настроек и служебный файл Docara. Docara
использует JSON Schema Draft 2020-12 и сразу отклоняет неизвестные поля,
неверные типы значений и небезопасные пути.

| Идентификатор схемы | Файл | Назначение |
| --- | --- | --- |
| `docara.site.v1` | `docara.json` | Настройки сайта |
| `docara.section.v1` | `section.json` | Наследуемые настройки раздела |
| `docara.page.v1` | `<page>.page.json` | Настройки одной страницы |
| `docara.redirects.v1` | Файл из `redirects_file` | Внутренние перенаправления |
| `docara.redirect_receipt.v1` | `build_<environment>.docara/redirects.json` | Проверяемый итог публикации перенаправлений |
| `docara.component_call.v1` | Нормализованный вызов компонента | Форма идентификатора и параметров; что можно вызвать, решает допуск компонента |
| `docara.framework_lock.v1` | `simai-framework.lock.json` | Закреплённые версии среды выполнения, описаний компонентов и ассетов |
| `https://schemas.simai.io/docara/component-catalog-entry/v1` | Запись в исходниках пакета | Одна запись каталога компонентов |
| `https://schemas.simai.io/docara/typed-component-definition/v1` | `resources/component-catalog/typed/*.json` | Описание типизированного компонента |
| `docara.effective_component_catalog.v1` | `_docara/component-catalog.json` | Каталог компонентов конкретной сборки |
| `docara.search_index.v1` | `_docara/search-index.json` | Локальный поисковый индекс |
| `docara.lang.v1` | `content/<locale>/lang.json` | Только общие UI-подписи локали |
| `docara.translation_lock.v1` | `translations.lock.json` | Принятые hashes переводов, уровень проверки и исключения |
| `docara.translation_status.v1` | `build_<environment>.docara/translation-status.json` | Детерминированный отчёт о страницах и ключах локали |
| `docara.documentation_source.v1` | JSON из исходников проекта или встроенный поставщик | Публичный контракт того, что описывает документация |
| `docara.documentation_lock.v1` | `documentation.lock.json` | Принятые связи страниц, примеров и отпечатков исходного API |
| `docara.documentation_status.v1` | `build_<environment>.docara/documentation-status.json` | Производный отчёт актуальности документации по исходному коду |
| `docara.authoring.v1` | `docara.authoring.json` | [Профили страниц](/ru/authoring/page-profiles/) для `docara validate page` |

Ещё один файл проекта не имеет схемы Docara: `page-weight.json` задаёт
бюджеты веса страниц для скрипта `verify-page-weight.php` — см.
[«Проверка сборки»](/ru/build/verify/). Формат `redirects.json` описан в
разделе [«Перенаправления»](/ru/authoring/redirects/), `lang.json` — в
справочнике [«Подписи интерфейса lang.json»](/ru/reference/lang-json/).

## Проверяемые примеры

Минимальный правильный сайт:

<!-- docara-example valid schema=site.schema.json -->
```json
{
  "schema": "docara.site.v1",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json"
}
```

Неправильный сайт с неизвестным полем:

<!-- docara-example invalid schema=site.schema.json error=SCHEMA_VALIDATION_FAILED -->
```json
{
  "schema": "docara.site.v1",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json",
  "unknown_setting": true
}
```

Неправильный пример показывает ожидаемую ошибку; не копируйте его в проект.

## Поля и уровни

Какие поля можно задать на уровне сайта, раздела и страницы, как они
наследуются и какие у них встроенные значения, описано в разделе
[«Сайт, раздел и страница»](/ru/settings/levels-and-inheritance/). Полные
таблицы полей с типами и правилами проверки — в справочнике
[docara.json](/ru/reference/docara-json/) и
[section.json и page.json](/ru/reference/section-and-page/).

Ветка оформления должна содержать хотя бы одно поле: `{}` и `[]`
отклоняются. Явная очистка записывается как `{"$reset": true}`; сброс можно
совместить с новыми ключами этой же ветки. Значения заготовки, например
включённый поиск, приходят из поставляемого `docara.json`, а не из скрытого
значения по умолчанию.

## Каталог компонентов сборки

Машиночитаемый каталог объединяет обычный Markdown, типизированные
компоненты Docara и Smart-компоненты, допущенные закреплённой версией
Framework. Записи о потребностях называют недоступные возможности, но не дают
синтаксиса для их вызова.

В корне каталога — неизменяемые сведения о версии Framework и поставщике,
отсортированные записи, `content_sha256` и короткий список того, чего каталог
не обещает. Точные параметры, состояния, ограничения и примеры хранятся в
исходных записях и эталонных примерах и показываются в
[справочнике компонентов](/ru/components/), а не повторяются здесь.

`_docara/component-catalog.json` — результат сборки. Автор его не редактирует
и не может ни через него, ни через Markdown или JSON оформления расширить набор
Smart-компонентов.

## План страницы после сборки

Для каждой страницы Docara создаёт объяснимый план (resolved page plan) до записи HTML.

Файл `build_<environment>.docara/resolved-page-plans.json` (квитанция сборки
рядом с результатом, а не внутри него) содержит массив страниц со следующими
данными:

| Поле | Смысл |
| --- | --- |
| `resolved_page_plan.configuration` | Итог после наследования |
| `trace` | Входные файлы, их роль, схема и SHA-256 |
| `provenance` | Какой файл установил каждое значение |
| `canonical_hash` | Хэш канонического плана |
| `component_runtime` | Вызовы компонентов и план ассетов |
| `output`, `url` | Физический и публичный маршруты |
| `declarative.plan.schema` | Версия полного `docara.resolved_render_plan.v2` |
| `declarative.plan.layout.view_tree` | Разрешённый безопасный каркас макета |
| `declarative.plan.regions.*[].slots` | Именованные слоты секции |
| `declarative.plan.regions.*[].blocks` | Вызовы блоков с постоянным идентификатором, слотом и Smart-компонентом |
| `declarative.plan.diagnostics` | Итог композиции и проверки View Tree |

План не является вторым источником для редактирования. Это генерируемый
диагностический и интеграционный артефакт. Изменяйте Markdown или JSON, затем
пересобирайте сайт.

Итоговые поля `/reading/breadcrumbs`, `/reading/toc`, `/reading/toc_depth` и
`/reading/previous_next` находятся в `configuration`, а их источник — в
`provenance`. Сами вычисленные хлебные крошки, оглавление и соседние ссылки в план
не записываются как редактируемые настройки: они детерминированно выводятся из
контента и канонической топологии при сборке.

## Проверки сверх схемы

Схема подтверждает форму файла, а сборка дополнительно проверяет:

- что пути существуют и не выходят за свои каталоги;
- символические ссылки и зарезервированные каталоги результата;
- тип, размер и хеш файлов бренда;
- смысл Framework lock и неизменяемость ссылок на версии;
- описания компонентов, параметры, зависимости и раскладку ассетов;
- что каждая страница принадлежит объявленному языку, теги BCP 47,
  непересекающиеся каталоги языков, циклы запасных языков,
  `locales.missing_page_policy` и отдельную `documentation_version`;
- что цель перенаправления существует, нет совпадений, цепочек и циклов, а
  адреса остаются внутри `base_url`;
- что две страницы не пишут в один файл.

Поэтому JSON, прошедший проверку схемой, ещё не гарантирует успешную сборку,
если внешний файл или правило сборки нарушены.
