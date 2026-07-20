# Схемы JSON

Portable Docara использует JSON Schema Draft 2020-12 и fail closed отклоняет
unknown fields, неверные типы и unsafe paths.

| Schema ID | Source/output | Назначение |
| --- | --- | --- |
| `docara.site.v1` | `docara.json` | Site contract |
| `docara.section.v1` | `section.json` | Наследуемый section contract |
| `docara.page.v1` | `<page>.page.json` | Page contract |
| `docara.redirects.v1` | Файл из `redirects_file` | Декларативные внутренние redirects |
| `docara.redirect_receipt.v1` | `.docara/redirects.json` | Проверяемый результат публикации redirects |
| `docara.component_call.v1` | Нормализованный вызов | Форма ID/props; admission определяет runtime |
| `docara.framework_lock.v1` | `simai-framework.lock.json` | Immutable runtime/manifests/assets |
| `https://schemas.simai.io/docara/component-catalog-entry/v1` | Package source record | Одна запись component catalog |
| `https://schemas.simai.io/docara/typed-component-definition/v1` | `resources/component-catalog/typed/*.json` | Typed owner record |
| `docara.effective_component_catalog.v1` | `_docara/component-catalog.json` | Производный catalog exact build |
| `docara.search_index.v1` | `_docara/search-index.json` | Локальный search index |

## Исполняемые примеры schema

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

## Scope

| Поле | Site | Section | Page |
| --- | :---: | :---: | :---: |
| `preset`, `title`, `locale` | ✓ | ✓ | ✓ |
| `branding`, `layout`, `settings` | ✓ | ✓ | ✓ |
| `navigation`, `search`, `reading` | ✓ | ✓ | ✓ |
| `framework_lock`, `content_root`, `base_url`, `default_locale` | ✓ | — | — |
| `documentation_version`, `redirects_file` | ✓ | — | — |
| `description`, `slug` | — | — | ✓ |

`schema` обязателен. Optional `version`, если присутствует, равен `1` и
обозначает версию schema, а не версию документации.

## Presentation branches

- `branding`: non-empty title/label/brand asset settings;
- `layout.key`: registered value `docara.docs`;
- `layout.max_width`: `compact`, `normal`, `wide`, `full`;
- `layout.regions.<name>.enabled`: boolean;
- `layout.regions.<name>.sections`: ordered registered
  Section -> Block -> Smart calls;
- `settings.theme`: `system`, `light`, `dark`;
- `navigation.hidden`: boolean;
- `navigation.order`: integer `0..2147483647`;
- `search.enabled`, `search.indexed`: boolean;
- `reading.breadcrumbs`, `reading.toc`, `reading.previous_next`: boolean;
- `reading.toc_depth`: integer `2..6`.

Branch должен содержать хотя бы одно поле. `{}` и `[]` отклоняются. Явная
очистка записывается как `{"$reset": true}`; reset можно совместить с новыми
ключами этой же ветки.

## Defaults

Resolver устанавливает:

```json
{
  "content_root": "content",
  "layout": {
    "key": "docara.docs",
    "regions": {
      "header": { "enabled": true },
      "sidebar": { "enabled": true },
      "main": { "enabled": true },
      "outline": { "enabled": true },
      "footer": { "enabled": false }
    }
  },
  "search": {
    "enabled": false,
    "indexed": true
  },
  "reading": {
    "breadcrumbs": true,
    "toc": true,
    "toc_depth": 3,
    "previous_next": true
  }
}
```

Структурные defaults областей восстанавливаются после branch reset. `main`
является обязательной областью и не может быть выключен. Допустимые
section/block/Smart IDs и фиксированные data bindings дополнительно
проверяются runtime resolver.

Значения starter, например включённый search UI, приходят из поставляемого
`docara.json`, а не из скрытого default.

## Effective component catalog

Machine-readable catalog объединяет native Markdown, typed Docara и
Smart-компоненты, допущенные exact Framework lock. Requirement records
фиксируют недоступные потребности, но не callable syntax.

Root содержит immutable framework/provider identity, sorted entries,
`content_sha256` и bounded nonclaims. Exact параметры, states, limitations и
examples принадлежат source records/fixtures и показываются в
[generated catalog](/components/catalog/), а не повторяются здесь.

`_docara/component-catalog.json` является output. Автор не редактирует его и не
может через него, Markdown или presentation JSON расширить Smart surface.

## Runtime checks beyond schema

Schema подтверждает форму, но builder дополнительно проверяет:

- существование и confinement paths;
- symlink и reserved destinations;
- brand asset type/size/hash;
- Framework lock semantics и immutable references;
- manifest, props, dependencies и asset projection;
- единственный locale сборки и отдельную `documentation_version`;
- redirect target existence, collision, chain/cycle и confinement внутри
  `base_url`;
- output collisions.

Поэтому schema-valid JSON ещё не гарантирует успешную сборку, если внешний
файл или runtime contract нарушен.
