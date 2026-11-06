---
description: The interface labels of a locale live in content/<locale>/lang.json — search, contents, copying, page links and accessibility labels. The full list of strings and their defaults.
tags: [lang-json, labels, translation, interface, i18n]
---

# lang.json interface labels

Each locale keeps its repeated interface labels in one file:
`content/<locale>/lang.json`. It holds search, the contents, the copy button,
page links and labels for screen readers. Page text and component
descriptions stay in Markdown.

## Create `lang.json`

For example, `content/fr-CA/lang.json`:

```json
{
  "schema": "docara.lang.v1",
  "version": 1,
  "search": {
    "placeholder": "Rechercher dans la documentation"
  },
  "code": {
    "copy": "Copier",
    "copied": "Copié"
  }
}
```

The root groups are limited to the shared UI vocabulary from
`resources/schemas/lang.schema.json`: `accessibility`, `atlas`, `blocks`,
`code`, `common`, `copy`, `effects`, `error`, `examples`, `language`,
`navigation`, `reader`, `redirect`, `search`, `shell`, `toc` and
`transitions`. The `examples` group holds only the interface labels of
examples, such as the responsive preview button; `blocks` and `atlas` hold
the labels of blocks and of the Design Atlas list that Docara writes at build
time. The groups `pages`, `components` and `catalog` are forbidden: page text
and component descriptions belong to Markdown.

## Declare the locale

In `docara.json`, give the content folder, the address prefix, the writing
direction and explicit fallback languages (`fallbacks`):

```json
{
  "label": "Français (Canada)",
  "direction": "ltr",
  "content_root": "content/fr-CA",
  "public_prefix": "fr-ca",
  "fallbacks": ["en"]
}
```

This is the content of the `locales.fr-CA` entry, not a separate
`docara.json`. If a shared label is missing from the French `lang.json`,
Docara can take it from the explicitly configured `content/en/lang.json`.
Markdown pages are never replaced this way.

## Check the site

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

The build checks the `lang.json` schema, BCP 47 tags, fallback language
chains and that the interface strings actually in use are present. Common
errors:

- `LOCALE_FALLBACK_NOT_CONFIGURED` — a fallback language is not declared in
  `locales`;
- `LOCALE_FALLBACK_CYCLE` — the fallback chain loops back on itself;
- `MESSAGE_NOT_FOUND` — a shared label is in neither the language nor its
  fallback languages.

Docara has no separate language pack, no `language_pack` field and no
`languages/` folder.

## All interface strings

Below are all the interface strings Docara knows. A key from the table is
written in `lang.json` as nested objects: `search.placeholder` is
`{"search": {"placeholder": "..."}}`. Docara replaces curly braces in the
text, for example `{count}`, with a value; keep them in the translation.

The tables are built from the Docara code by
`php scripts/capture-lang-reference.php`, so a new string is not lost.

<!-- docara:lang-reference:start -->

### Required strings

Set them in the `lang.json` of every language or in its fallback language from `fallbacks`; otherwise the build stops with `MESSAGE_NOT_FOUND`. They have no built-in text: `docara init` writes the English or Russian text from the columns on the right.

| Key and where it shows | English | Russian |
| --- | --- | --- |
| `shell.skip_to_content` — The keyboard "skip to content" link at the start of the page | Skip to content | К содержанию |
| `navigation.open` — The button that opens the menu on a phone | Open navigation | Открыть навигацию |
| `navigation.mobile_title` — The menu title on a phone | Navigation | Навигация |
| `navigation.primary` — The label of the top menu for screen readers | Primary | Главное |
| `navigation.sections` — The label of the section menu on the left | Documentation | Документация |
| `navigation.title` — The title of the section list | Sections | Разделы |
| `navigation.close` — The button that closes the menu on a phone | Close navigation | Закрыть навигацию |
| `navigation.breadcrumbs` — The label of the path to the page for screen readers | Breadcrumbs | Хлебные крошки |
| `navigation.breadcrumbs_expand` — The button that shows hidden levels of the path | Show hidden breadcrumb levels | Показать скрытые уровни навигационной цепочки |
| `navigation.outline` — The title of the page outline | Contents | Содержание |
| `navigation.outline_close` — The button that closes the outline on a phone | Close page outline | Закрыть содержание страницы |
| `navigation.previous_next` — The label of the "previous" and "next" link block | Page navigation | Переходы между страницами |
| `navigation.previous` — The link to the previous page | Previous | Предыдущая |
| `navigation.next` — The link to the next page | Next | Следующая |
| `navigation.expand` — The start of the label of the button that expands a menu section | «Expand: » | «Развернуть: » |
| `navigation.collapse` — The start of the label of the button that collapses a menu section | «Collapse: » | «Свернуть: » |
| `navigation.contains_current` — Added to the label of the section that holds the open page | , contains the current page | , содержит текущую страницу |
| `language.label` — The label of the language switcher | Language | Язык |
| `search.open` — The button that opens search | Open documentation search | Открыть поиск по документации |
| `search.label` — The label of the search button | Search | Поиск |
| `search.title` — The title of the search window | Search documentation | Поиск по документации |
| `search.close` — The button that closes the search window | Close search | Закрыть поиск |
| `search.query` — The label of the query field | Query | Запрос |
| `search.placeholder` — The hint in the empty search field | For example, inheritance | Например, наследование |
| `search.idle` — The text before a query is typed | Enter at least 2 characters | Введите минимум 2 символа |
| `search.loading` — The text while the search index loads | Loading search… | Загрузка поиска… |
| `search.found` — The number of results; `{count}` is the count | Found: {count} | Найдено: {count} |
| `search.empty` — The text when nothing is found | No results found | Ничего не найдено |
| `search.error` — The text when search did not load | Search is temporarily unavailable. Reload the page and try again. | Поиск временно недоступен. Обновите страницу и попробуйте снова. |
| `search.navigate` — Key hint: move through the results | navigate | переход |
| `search.open_result` — Key hint: open a result | open | открыть |
| `search.dismiss` — Key hint: close search | close | закрыть |
| `reader.open` — The button that opens the reader settings panel | Open reading settings | Открыть настройки чтения |
| `reader.close` — The button that closes the settings panel | Close reading settings | Закрыть настройки чтения |
| `reader.theme_title` — The name of the theme setting | Theme | Тема |
| `reader.theme_description` — The explanation of the theme setting | Choose a light or dark appearance. | Выберите светлое или тёмное оформление. |
| `reader.theme_system` — The "as in the system" theme option | Use system setting | Как в системе |
| `reader.theme_light` — The light theme option | Light | Светлая |
| `reader.theme_dark` — The dark theme option | Dark | Тёмная |
| `reader.saved` — The message about a saved setting; `{setting}` is the setting, `{value}` the value | Saved: {setting} — {value}. | Сохранено: {setting} — {value}. |
| `reader.applied_not_saved` — The message when the browser did not allow saving the setting | The setting was applied. The browser did not allow the choice to be saved. | Настройка применена. Браузер не разрешил сохранить выбор. |
| `reader.restored` — The message after the settings are reset | The site settings have been restored. | Восстановлены настройки сайта. |
| `redirect.title` — The title of the redirect page | Page moved | Страница перемещена |
| `redirect.message` — The text of the redirect page | The page address has changed. | Адрес страницы изменился. |
| `redirect.link` — The link to the new address on the redirect page | Open the current page | Перейти к актуальной странице |

### Optional strings

If a string is missing from `lang.json`, Docara takes the built-in text: Russian for the Russian language, English for the others.

| Key and where it shows | English | Russian |
| --- | --- | --- |
| `code.copy` — The copy code button | Copy | Скопировать |
| `code.copied` — The confirmation after copying | Copied | Скопировано |
| `code.wrap` — The button that turns on wrapping of long code lines | Wrap source lines | Переносить длинные строки |
| `code.unwrap` — The button that turns off wrapping of long code lines | Keep original source lines | Не переносить длинные строки |
| `code.added` — The word a screen reader says before an added code line | added | добавлено |
| `code.removed` — The word a screen reader says before a removed code line | removed | удалено |
| `navigation.repository` — The label of the repository icon in the header | Source repository | Репозиторий |
| `effects.pause` — The button that stops the hero background animation | Pause background animation | Остановить фоновую анимацию |
| `effects.play` — The button that starts the hero background animation | Play background animation | Запустить фоновую анимацию |
| `examples.example` — The label of a live example | Example | Пример |
| `examples.viewer` — The button that checks an example at different widths | Check responsive preview | Проверить адаптивность |
| `examples.viewer_exit` — The button that leaves the width check | Exit responsive preview | Выйти из режима просмотра |
| `examples.viewer_dialog` — The label of the width check window | Responsive example preview | Адаптивный просмотр примера |
| `examples.viewport_group` — The label of the width switcher | Preview width | Ширина примера |
| `examples.viewport_desktop` — The "desktop" width option | Desktop | Десктоп |
| `examples.viewport_tablet` — The "tablet" width option | Tablet | Планшет |
| `examples.viewport_mobile` — The "phone" width option | Mobile | Телефон |
| `reader.font_size_title` — The name of the font size setting | Font size | Размер шрифта |
| `reader.font_size_description` — The explanation of the font size setting | Changes the size of the whole documentation interface. | Меняет размер всего интерфейса документации. |
| `reader.font_size_small` — The small font option | Small | Мелкий |
| `reader.font_size_normal` — The normal font option | Normal | Обычный |
| `reader.font_size_large` — The large font option | Large | Крупный |
| `reader.content_width_title` — The name of the page width setting | Page width | Ширина страницы |
| `reader.content_width_description` — The explanation of the page width setting | Wide uses the full window and does not limit line length. | Широкий формат занимает всё окно и не ограничивает длину строк. |
| `reader.content_width_normal` — The normal width option | Normal | Обычная |
| `reader.content_width_wide` — The wide page option | Wide | Широкая |
| `reader.outline_hide` — The button that hides the page outline | Hide page contents | Скрыть содержание страницы |
| `reader.focus_mode` — The reading mode button | Reading mode | Режим чтения |
| `reader.focus_mode_exit` — The button that leaves reading mode | Exit reading mode (Esc) | Выйти из режима чтения (Esc) |
| `reader.reset_all` — The button that resets all reader settings | Reset settings | Сбросить настройки |
| `reader.panel_title` — The title of the settings panel | Settings | Настройки |
| `navigation.backlinks_heading` — The heading of the `:::backlinks` block | Referenced by | Referenced by (English text) |
| `navigation.backlinks_empty` — The text of the backlinks block when there are no links | No backlinks yet. | No backlinks yet. (English text) |

### Strings Docara writes at build time

You can also set them in `lang.json`, in the groups `error`, `language`, `blocks`, `atlas` and `examples`. Without them Docara writes the built-in text: Russian for the Russian language, English for the others.

| Key and where it shows | English | Russian |
| --- | --- | --- |
| `error.not_found.title` — The title of the 404 page | Page not found | Страница не найдена |
| `error.not_found.message` — The text of the 404 page | There is no page at this address. It may have moved, or the address has a typo. | По этому адресу страницы нет. Возможно, её перенесли или в адресе опечатка. |
| `error.not_found.home` — The home page link on the 404 page | Go to the home page | На главную |
| `error.not_found.search` — The search button on the 404 page | Search the documentation | Искать в документации |
| `language.fallback_notice` — The notice on a page whose translation is not ready yet; `{source}` is a link to the original | This page is being translated; the original is shown ({source}). | Эта страница переводится; показан оригинал ({source}). |
| `examples.preview_title` — The label of the frame with the result of an example | Example result | Результат примера |
| `blocks.diagram` — The label of a diagram without its own `title` | Diagram | Схема |
| `blocks.math` — The name of a formula for screen readers when it has no `label` | Mathematical formula | Математическая формула |
| `blocks.html` — The name of the frame of an HTML example | HTML example | Пример HTML |
| `atlas.empty` — The text of an empty Design Atlas list | The accepted Atlas has no entries for this filter. | В принятом Atlas нет элементов для этого фильтра. |
| `atlas.owner` — The owner label in the Design Atlas | Owner | Владелец |
| `atlas.namespace` — The namespace label in the Design Atlas | namespace | пространство имён |
| `atlas.origin` — The origin label in the Design Atlas | origin | источник |
| `atlas.provider` — The provider label in the Design Atlas | provider | поставщик |
| `atlas.authoring` — The label of the connection method in the Design Atlas | authoring | способ подключения |
| `atlas.capabilities` — The capabilities label in the Design Atlas | Capabilities | Возможности |
| `atlas.container` — The label of the container nesting rules in the Design Atlas | Container | Правила вложения |
| `set.version` — The component set's name and release number in the footer (`shell.set-passport`) | {title} {version} | {title} {version} |
| `set.build` — The set's name and short build code while the set has no release number | {title}, build {revision} | {title}, сборка {revision} |
| `set.framework_from` — Which SIMAI Framework the set needs, when a lower bound is given | For SIMAI Framework {version} and later | Для SIMAI Framework {version} и новее |
| `set.framework` — Which SIMAI Framework the set needs, for any other range | For SIMAI Framework {range} | Для SIMAI Framework {range} |
| `set.licence` — The set's licence in the footer | Licence: {licence} | Лицензия: {licence} |
| `set.source` — The link to the set's source code in the footer | Source code | Исходный код |
| `set.history` — The link to the version history in the footer | Version history | История версий |

### Built-in strings that do not change

Docara writes these strings at build time in English or Russian. You cannot change them through `lang.json`: the `schema` key in `lang.json` is taken by the schema of the file itself, and the "made with Docara" line belongs to the branding check.

| Key and where it shows | English | Russian |
| --- | --- | --- |
| `examples.result_label` — The result label in an example created by `docara scaffold` | Result | Результат |
| `schema.field` — The "Field" column header in the settings reference | Field | Поле |
| `schema.description` — The description column header in the settings reference | What it does | Что задаёт |
| `schema.default` — The default value label under a field description | Default | По умолчанию |
| `schema.required` — The mark of a required field | required | обязательное |
| `schema.details` — The label of the expandable technical details of a field | Technical details | Технические данные |
| `schema.type` — The field type label | Type | Тип |
| `schema.scope` — The settings level label | Level | Уровень |
| `schema.validation` — The validation rule label | Validation | Проверка |
| `schema.provenance` — The label of the place in the schema | Schema | Схема |
| `schema.description_missing` — The text when a field has no description | No description yet. | Описания пока нет. |
| `schema.inherited` — The value is inherited | inherited | наследуется |
| `schema.default_inherited` — The value is inherited, otherwise built-in; `{value}` is the built-in value | inherited; otherwise {value} | наследуется, иначе {value} |
| `schema.path` — The label of the exact path of the field | Path | Путь |
| `schema.key_region` — The region name in the field path | region | область |
| `schema.key_locale` — The language code in the field path | locale | язык |
| `schema.key_any` — An arbitrary key in the field path | key | ключ |
| `branding.credit` — The "Made with Docara" line; `{docara}` is a link | Made with {docara} | Сделано на {docara} |

### Keys that are not used

The `docara init` starters write `reader.appearance`, `reader.appearance_description` and `reader.help`, but the Docara 3.x interface does not show them now.

<!-- docara:lang-reference:end -->

## See also

- [Multilingual site](/en/authoring/multilingual-site/) — how languages and fallback languages work
- [lang.json error codes](/en/reference/errors/site/#lang-json-interface-strings)
- [Background effects](/components/effects/) — the labels of the pause button
