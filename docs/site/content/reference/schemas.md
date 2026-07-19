# Схемы JSON

Portable Docara использует JSON Schema Draft 2020-12.

| Schema ID | Файл | Назначение |
| --- | --- | --- |
| `docara.site.v1` | `docara.json` | Сайт и общие defaults |
| `docara.section.v1` | `_section.json` | Наследуемые настройки раздела |
| `docara.page.v1` | `<page>.page.json` | Настройки одной страницы |
| `docara.component_call.v1` | Нормализованный вызов | Грамматика ID и props; допуск определяет lock/runtime |
| `docara.framework_lock.v1` | `simai-framework.lock.json` | Exact runtime, manifests и assets |
| `https://schemas.simai.io/docara/component-catalog-entry/v1` | Package source record | Общая форма записи эффективного каталога |
| `https://schemas.simai.io/docara/typed-component-definition/v1` | `resources/component-catalog/typed/*.json` | Определение typed-компонента Docara |
| `docara.effective_component_catalog.v1` (`$id`: `https://schemas.simai.io/docara/effective-component-catalog/v1`) | `_docara/component-catalog.json` | Производная проекция компонентов конкретной сборки |
| `docara.search_index.v1` | `_docara/search-index.json` | Детерминированный локальный поисковый индекс |

## Эффективный каталог компонентов

`docara.effective_component_catalog.v1` объединяет native Markdown profile,
typed-компоненты Docara и Smart-компоненты, допущенные точным Simai Framework
lock. Дополнительные записи `requirement` фиксируют известные потребности, но
не являются исполняемыми компонентами.

Корневой объект содержит:

- `framework_pair` и immutable `provider_revision`;
- отсортированный по `id` массив `entries`;
- `content_sha256` канонического массива записей;
- обязательные `nonclaims`, запрещающие трактовать проекцию как канонический
  реестр Simai Framework, готовность всех компонентов, production readiness
  или public-release readiness.

Lifecycle записи принимает одно из четырёх значений: `supported`,
`admission_pending`, `framework_gap`, `deferred`. Для `supported` обязательны
renderer, tests, docs и example evidence. Для остальных состояний обязательны
owner, reason, fallback и admission condition.

Для Smart-записи `authoring.parameters[].required` относится только к явному
вводу автора. Значение `false` разрешает пропустить параметр, но само по себе
не обещает default. Поле `default` публикуется только при наличии точного
базового значения в `atlas.example_props`; иначе свойство остаётся
отсутствующим до preset или явного ввода. Поле `validation` сохраняет границы
длины, pattern и числовой диапазон, а `mirrors` — условное заполнение другого
свойства из явного авторского ввода. Необязательный `preset` и точные
`authoring.constraints.allowed_combinations`/`requires` также выводятся из
manifest и не редактируются вручную.

Путь `_docara/component-catalog.json` относится к output сборки; публичный URL
равен `<base_url>_docara/component-catalog.json`. Автор сайта не редактирует
этот файл и не может через него, Markdown или presentation JSON добавить
Smart-компонент: допуск определяется точным
`simai-framework.lock.json` и совпадающим bundled manifest.

## Общие presentation-поля

- `preset`: `docs` или `landing`;
- `branding.title`, `branding.label`;
- `branding.logo`, `branding.logo_dark`, `branding.favicon`: безопасные пути
  от корня проекта; `logo_dark` требует основной `logo`;
- `layout.max_width`: `compact`, `normal`, `wide`, `full`;
- `settings.theme`: `system`, `light`, `dark`;
- `navigation.hidden`: boolean;
- `navigation.order`: целое число от 0 до 2147483647; отсутствие значения означает
  «после всех страниц с явно заданным порядком».
- `search.enabled`: boolean, выводить локальный поиск в текущей области;
- `search.indexed`: boolean, включать страницу в поисковый индекс.
- `reading.breadcrumbs`: boolean, показывать путь до текущей страницы;
- `reading.toc`: boolean, показывать оглавление страницы;
- `reading.toc_depth`: целое число от 2 до 6, последний уровень заголовка в
  оглавлении;
- `reading.previous_next`: boolean, показывать соседние страницы документации.

Значения `reading` по умолчанию: `breadcrumbs: true`, `toc: true`,
`toc_depth: 3`, `previous_next: true`. Допустимая глубина — только целое число
2–6. Значения `1`, `7`, строка `"3"`, строка вместо boolean, пустой объект `{}`
и неизвестное поле отклоняются schema до записи результата.

```json
{
  "schema": "docara.page.v1",
  "reading": { "toc_depth": 7 }
}
```

Этот пример намеренно невалиден: Docara не исправляет опечатки или диапазоны
молча.

Page schema дополнительно поддерживает `description` и безопасный `slug`.
Ветка `branding`, как `layout`, `settings`, `navigation`, `search` и `reading`, поддерживает
только непустой объект настроек: пустые `{}` и `[]` отклоняются как no-op.
Для очистки наследуемой ветки используйте `{"$reset": true}`; reset можно
совместить с новыми значениями той же ветки. Внешние файлы branding
дополнительно проверяются во время сборки: тип, размер, существование, symlink
и зарезервированный путь.
`additionalProperties: false` означает, что опечатка не становится скрытым
no-op: сборка завершается ошибкой.
