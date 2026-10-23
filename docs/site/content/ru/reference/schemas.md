---
description: "Схемы JSON файлов настроек и служебных файлов Docara: какой файл какой схеме соответствует и что сборка проверяет сверх схемы."
---

# Схемы JSON

Portable Docara использует JSON Schema Draft 2020-12 и fail closed отклоняет
unknown fields, неверные типы и unsafe paths.

| Schema ID | Source/output | Назначение |
| --- | --- | --- |
| `docara.site.v1` | `docara.json` | Site contract |
| `docara.section.v1` | `section.json` | Наследуемый section contract |
| `docara.page.v1` | `<page>.page.json` | Page contract |
| `docara.redirects.v1` | Файл из `redirects_file` | Декларативные внутренние redirects |
| `docara.redirect_receipt.v1` | `build_<environment>.docara/redirects.json` | Проверяемый результат публикации redirects |
| `docara.component_call.v1` | Нормализованный вызов | Форма ID/props; admission определяет runtime |
| `docara.framework_lock.v1` | `simai-framework.lock.json` | Immutable runtime/manifests/assets |
| `https://schemas.simai.io/docara/component-catalog-entry/v1` | Package source record | Одна запись component catalog |
| `https://schemas.simai.io/docara/typed-component-definition/v1` | `resources/component-catalog/typed/*.json` | Typed owner record |
| `docara.effective_component_catalog.v1` | `_docara/component-catalog.json` | Производный catalog exact build |
| `docara.search_index.v1` | `_docara/search-index.json` | Локальный search index |
| `docara.lang.v1` | `content/<locale>/lang.json` | Только общие UI-подписи локали |
| `docara.translation_lock.v1` | `translations.lock.json` | Принятые hashes переводов, уровень проверки и исключения |
| `docara.translation_status.v1` | `build_<environment>.docara/translation-status.json` | Детерминированный отчёт о страницах и ключах локали |
| `docara.documentation_source.v1` | Source-owned JSON или встроенный provider | Нейтральный публичный контракт документируемых сущностей |
| `docara.documentation_lock.v1` | `documentation.lock.json` | Принятые связи страниц, примеров и отпечатков исходного API |
| `docara.documentation_status.v1` | `build_<environment>.docara/documentation-status.json` | Производный отчёт актуальности документации по исходному коду |
| `docara.authoring.v1` | `docara.authoring.json` | [Профили страниц](/ru/authoring/page-profiles/) для `docara validate page` |

Ещё один файл проекта не имеет схемы Docara: `page-weight.json` задаёт
бюджеты веса страниц для скрипта `verify-page-weight.php` — см.
[«Проверка сборки»](/ru/build/verify/). Формат `redirects.json` описан в
разделе [«Перенаправления»](/ru/authoring/redirects/), `lang.json` — в
справочнике [«Подписи интерфейса lang.json»](/ru/reference/lang-json/).

## Проверяемые примеры

Valid minimal site:

<!-- docara-example valid schema=site.schema.json -->
```json
{
  "schema": "docara.site.v1",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json"
}
```

Invalid site с unknown field:

<!-- docara-example invalid schema=site.schema.json error=SCHEMA_VALIDATION_FAILED -->
```json
{
  "schema": "docara.site.v1",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json",
  "unknown_setting": true
}
```

Invalid-пример документирует ожидаемую ошибку; не копируйте его в проект.

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

Machine-readable catalog объединяет native Markdown, typed Docara и
Smart-компоненты, допущенные exact Framework lock. Requirement records
фиксируют недоступные потребности, но не callable syntax.

Root содержит immutable framework/provider identity, sorted entries,
`content_sha256` и bounded nonclaims. Exact параметры, states, limitations и
examples принадлежат source records/fixtures и показываются в
[справочнике компонентов](/components/), а не повторяются здесь.

`_docara/component-catalog.json` является output. Автор не редактирует его и не
может через него, Markdown или presentation JSON расширить Smart surface.

## План страницы после сборки

Для каждой страницы Docara создаёт объяснимый план (resolved page plan) до записи HTML.

Файл `build_<environment>.docara/resolved-page-plans.json` (квитанция сборки
рядом с результатом, а не внутри него) содержит массив страниц со следующими
данными:

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

## Проверки сверх схемы

Schema подтверждает форму, но builder дополнительно проверяет:

- существование и confinement paths;
- symlink и reserved destinations;
- brand asset type/size/hash;
- Framework lock semantics и immutable references;
- manifest, props, dependencies и asset projection;
- принадлежность каждой страницы объявленному locale, BCP 47 tags,
  непересекающиеся content roots, fallback-циклы,
  `locales.missing_page_policy` и отдельную `documentation_version`;
- redirect target existence, collision, chain/cycle и confinement внутри
  `base_url`;
- output collisions.

Поэтому schema-valid JSON ещё не гарантирует успешную сборку, если внешний
файл или runtime contract нарушен.
