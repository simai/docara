---
description: Подписи интерфейса локали хранятся в content/<locale>/lang.json — поиск, содержание, копирование, переходы и подписи доступности. Полный список строк и их значения по умолчанию.
tags: [lang-json, подписи, перевод, интерфейс, i18n]
---

# Подписи интерфейса lang.json

Каждая локаль хранит повторяющиеся подписи интерфейса в одном файле:
`content/<locale>/lang.json`. Здесь находятся поиск, содержание, кнопка
копирования, переходы и подписи для экранного доступа. Текст страниц и описания
компонентов остаются в Markdown.

## Создайте `lang.json`

Например, `content/fr-CA/lang.json`:

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

Корневые группы ограничены общей UI-лексикой из
`resources/schemas/lang.schema.json`: `accessibility`, `code`, `common`,
`copy`, `effects`, `examples`, `language`, `navigation`, `reader`, `redirect`,
`search`, `shell`, `toc` и `transitions`. Группа `examples` содержит только
подписи интерфейса примеров, например кнопку адаптивного просмотра. Группы
`pages`, `components` и `catalog` запрещены: текст страниц и описания
компонентов принадлежат Markdown.

## Объявите локаль

В `docara.json` укажите каталог с текстами, префикс адреса, направление
письма и явные запасные языки (`fallbacks`):

```json
{
  "label": "Français (Canada)",
  "direction": "ltr",
  "content_root": "content/fr-CA",
  "public_prefix": "fr-ca",
  "fallbacks": ["en"]
}
```

Это содержимое записи `locales.fr-CA`, а не отдельный `docara.json`. Если общей
подписи нет во французском `lang.json`, Docara может взять её из явно
настроенного `content/en/lang.json`. Markdown-страницы таким способом никогда
не подменяются.

## Проверьте сайт

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

Сборка проверяет схему `lang.json`, теги BCP 47, цепочки запасных языков и
наличие строк интерфейса, которые реально используются. Частые ошибки:

- `LOCALE_FALLBACK_NOT_CONFIGURED` — запасной язык не объявлен в `locales`;
- `LOCALE_FALLBACK_CYCLE` — цепочка запасных языков замкнулась;
- `MESSAGE_NOT_FOUND` — общей подписи нет ни в языке, ни в его запасных языках.

Отдельного языкового пакета, поля `language_pack` и каталога `languages/` в
Docara нет.

## Все строки интерфейса

Ниже перечислены все строки интерфейса, которые знает Docara. Ключ в таблице
записывается в `lang.json` вложенными объектами: `search.placeholder` — это
`{"search": {"placeholder": "..."}}`. Фигурные скобки в тексте, например
`{count}`, Docara заменяет значением; оставьте их в переводе.

Таблицы строятся из кода Docara командой
`php scripts/capture-lang-reference.php`, поэтому новая строка не потеряется.

<!-- docara:lang-reference:start -->

### Обязательные строки

Их нужно задать в `lang.json` каждого языка или в его запасном языке из `fallbacks`; иначе сборка остановится с `MESSAGE_NOT_FOUND`. Встроенного текста у них нет: `docara init` записывает английский или русский текст из столбцов справа.

| Ключ и где видно | English | Русский |
| --- | --- | --- |
| `shell.skip_to_content` — Ссылка «к содержанию» для клавиатуры в начале страницы | Skip to content | К содержанию |
| `navigation.open` — Кнопка, открывающая меню на телефоне | Open navigation | Открыть навигацию |
| `navigation.mobile_title` — Заголовок меню на телефоне | Navigation | Навигация |
| `navigation.primary` — Подпись верхнего меню для программ экранного доступа | Primary | Главное |
| `navigation.sections` — Подпись меню разделов слева | Documentation | Документация |
| `navigation.title` — Заголовок списка разделов | Sections | Разделы |
| `navigation.close` — Кнопка, закрывающая меню на телефоне | Close navigation | Закрыть навигацию |
| `navigation.breadcrumbs` — Подпись пути до страницы для программ экранного доступа | Breadcrumbs | Хлебные крошки |
| `navigation.breadcrumbs_expand` — Кнопка, показывающая скрытые уровни пути | Show hidden breadcrumb levels | Показать скрытые уровни навигационной цепочки |
| `navigation.outline` — Заголовок оглавления страницы | Contents | Содержание |
| `navigation.outline_close` — Кнопка, закрывающая оглавление на телефоне | Close page outline | Закрыть содержание страницы |
| `navigation.previous_next` — Подпись блока ссылок «назад» и «вперёд» | Page navigation | Переходы между страницами |
| `navigation.previous` — Ссылка на предыдущую страницу | Previous | Предыдущая |
| `navigation.next` — Ссылка на следующую страницу | Next | Следующая |
| `navigation.expand` — Начало подписи кнопки, раскрывающей раздел меню | «Expand: » | «Развернуть: » |
| `navigation.collapse` — Начало подписи кнопки, сворачивающей раздел меню | «Collapse: » | «Свернуть: » |
| `navigation.contains_current` — Добавка к подписи раздела, в котором открыта страница | , contains the current page | , содержит текущую страницу |
| `language.label` — Подпись переключателя языков | Language | Язык |
| `search.open` — Кнопка, открывающая поиск | Open documentation search | Открыть поиск по документации |
| `search.label` — Подпись кнопки поиска | Search | Поиск |
| `search.title` — Заголовок окна поиска | Search documentation | Поиск по документации |
| `search.close` — Кнопка, закрывающая окно поиска | Close search | Закрыть поиск |
| `search.query` — Подпись поля ввода запроса | Query | Запрос |
| `search.placeholder` — Подсказка в пустом поле поиска | For example, inheritance | Например, наследование |
| `search.idle` — Текст до ввода запроса | Enter at least 2 characters | Введите минимум 2 символа |
| `search.loading` — Текст, пока загружается индекс поиска | Loading search… | Загрузка поиска… |
| `search.found` — Число результатов; `{count}` — количество | Found: {count} | Найдено: {count} |
| `search.empty` — Текст, когда ничего не найдено | No results found | Ничего не найдено |
| `search.error` — Текст, когда поиск не загрузился | Search is temporarily unavailable. Reload the page and try again. | Поиск временно недоступен. Обновите страницу и попробуйте снова. |
| `search.navigate` — Подсказка клавиш: перемещение по результатам | navigate | переход |
| `search.open_result` — Подсказка клавиш: открыть результат | open | открыть |
| `search.dismiss` — Подсказка клавиш: закрыть поиск | close | закрыть |
| `reader.open` — Кнопка, открывающая панель настроек читателя | Open reading settings | Открыть настройки чтения |
| `reader.close` — Кнопка, закрывающая панель настроек | Close reading settings | Закрыть настройки чтения |
| `reader.theme_title` — Название настройки темы | Theme | Тема |
| `reader.theme_description` — Пояснение к настройке темы | Choose a light or dark appearance. | Выберите светлое или тёмное оформление. |
| `reader.theme_system` — Вариант темы «как в системе» | Use system setting | Как в системе |
| `reader.theme_light` — Вариант светлой темы | Light | Светлая |
| `reader.theme_dark` — Вариант тёмной темы | Dark | Тёмная |
| `reader.saved` — Сообщение о сохранённой настройке; `{setting}` — настройка, `{value}` — значение | Saved: {setting} — {value}. | Сохранено: {setting} — {value}. |
| `reader.applied_not_saved` — Сообщение, когда браузер не дал сохранить настройку | The setting was applied. The browser did not allow the choice to be saved. | Настройка применена. Браузер не разрешил сохранить выбор. |
| `reader.restored` — Сообщение после сброса настроек | The site settings have been restored. | Восстановлены настройки сайта. |
| `redirect.title` — Заголовок страницы перенаправления | Page moved | Страница перемещена |
| `redirect.message` — Текст страницы перенаправления | The page address has changed. | Адрес страницы изменился. |
| `redirect.link` — Ссылка на новый адрес на странице перенаправления | Open the current page | Перейти к актуальной странице |

### Необязательные строки

Если строки нет в `lang.json`, Docara берёт встроенный текст: русский для русского языка, английский для остальных.

| Ключ и где видно | English | Русский |
| --- | --- | --- |
| `code.copy` — Кнопка копирования кода | Copy | Скопировать |
| `code.copied` — Подтверждение после копирования | Copied | Скопировано |
| `code.wrap` — Кнопка, включающая перенос длинных строк кода | Wrap source lines | Переносить длинные строки |
| `code.unwrap` — Кнопка, выключающая перенос длинных строк кода | Keep original source lines | Не переносить длинные строки |
| `code.added` — Слово, которое программа экранного доступа произносит перед добавленной строкой кода | added | добавлено |
| `code.removed` — Слово, которое программа экранного доступа произносит перед удалённой строкой кода | removed | удалено |
| `effects.pause` — Кнопка, останавливающая фоновую анимацию hero | Pause background animation | Остановить фоновую анимацию |
| `effects.play` — Кнопка, запускающая фоновую анимацию hero | Play background animation | Запустить фоновую анимацию |
| `examples.example` — Подпись живого примера | Example | Пример |
| `examples.viewer` — Кнопка проверки примера на разной ширине | Check responsive preview | Проверить адаптивность |
| `examples.viewer_exit` — Кнопка выхода из проверки ширины | Exit responsive preview | Выйти из режима просмотра |
| `examples.viewer_dialog` — Подпись окна проверки ширины | Responsive example preview | Адаптивный просмотр примера |
| `examples.viewport_group` — Подпись переключателя ширины | Preview width | Ширина примера |
| `examples.viewport_desktop` — Вариант ширины «компьютер» | Desktop | Десктоп |
| `examples.viewport_tablet` — Вариант ширины «планшет» | Tablet | Планшет |
| `examples.viewport_mobile` — Вариант ширины «телефон» | Mobile | Телефон |
| `reader.font_size_title` — Название настройки размера шрифта | Font size | Размер шрифта |
| `reader.font_size_description` — Пояснение к настройке размера шрифта | Changes the size of the whole documentation interface. | Меняет размер всего интерфейса документации. |
| `reader.font_size_small` — Вариант мелкого шрифта | Small | Мелкий |
| `reader.font_size_normal` — Вариант обычного шрифта | Normal | Обычный |
| `reader.font_size_large` — Вариант крупного шрифта | Large | Крупный |
| `reader.content_width_title` — Название настройки ширины страницы | Page width | Ширина страницы |
| `reader.content_width_description` — Пояснение к настройке ширины страницы | Wide uses the full window and does not limit line length. | Широкий формат занимает всё окно и не ограничивает длину строк. |
| `reader.content_width_normal` — Вариант обычной ширины | Normal | Обычная |
| `reader.content_width_wide` — Вариант широкой страницы | Wide | Широкая |
| `reader.outline_hide` — Кнопка, скрывающая оглавление страницы | Hide page contents | Скрыть содержание страницы |
| `reader.focus_mode` — Кнопка режима чтения | Reading mode | Режим чтения |
| `reader.focus_mode_exit` — Кнопка выхода из режима чтения | Exit reading mode (Esc) | Выйти из режима чтения (Esc) |
| `reader.reset_all` — Кнопка сброса всех настроек читателя | Reset settings | Сбросить настройки |
| `reader.panel_title` — Заголовок панели настроек | Settings | Настройки |
| `navigation.backlinks_heading` — Заголовок блока обратных ссылок `:::backlinks` | Referenced by | Referenced by (английский) |
| `navigation.backlinks_empty` — Текст блока обратных ссылок, когда ссылок нет | No backlinks yet. | No backlinks yet. (английский) |

### Страница 404 и уведомление о переводе

Эти строки тоже можно задать в `lang.json`. Без них Docara пишет встроенный текст при сборке.

| Ключ и где видно | English | Русский |
| --- | --- | --- |
| `error.not_found.title` — Заголовок страницы 404 | Page not found | Страница не найдена |
| `error.not_found.message` — Текст страницы 404 | There is no page at this address. It may have moved, or the address has a typo. | По этому адресу страницы нет. Возможно, её перенесли или в адресе опечатка. |
| `error.not_found.home` — Ссылка на главную на странице 404 | Go to the home page | На главную |
| `error.not_found.search` — Кнопка поиска на странице 404 | Search the documentation | Искать в документации |
| `language.fallback_notice` — Уведомление на странице, перевод которой ещё не готов; `{source}` — ссылка на оригинал | This page is being translated; the original is shown ({source}). | Эта страница переводится; показан оригинал ({source}). |

### Встроенные строки, которые не меняются

Docara пишет эти строки при сборке на английском или русском. Через `lang.json` их не изменить.

| Ключ и где видно | English | Русский |
| --- | --- | --- |
| `examples.preview_title` — Подпись рамки с результатом примера | Example result | Результат примера |
| `examples.result_label` — Подпись результата в примере, созданном `docara scaffold` | Result | Результат |
| `atlas.empty` — Текст пустого списка Design Atlas | The accepted Atlas has no entries for this filter. | В принятом Atlas нет элементов для этого фильтра. |
| `atlas.owner` — Подпись владельца в Design Atlas | Owner | Владелец |
| `atlas.namespace` — Подпись пространства имён в Design Atlas | namespace | пространство имён |
| `atlas.origin` — Подпись источника в Design Atlas | origin | источник |
| `atlas.provider` — Подпись поставщика в Design Atlas | provider | поставщик |
| `atlas.authoring` — Подпись способа подключения в Design Atlas | authoring | способ подключения |
| `atlas.capabilities` — Подпись возможностей в Design Atlas | Capabilities | Возможности |
| `atlas.container` — Подпись правил вложения контейнера в Design Atlas | Container | Правила вложения |
| `schema.field` — Заголовок столбца «Поле» в справочнике настроек | Field | Поле |
| `schema.description` — Заголовок столбца описания в справочнике настроек | What it does | Что задаёт |
| `schema.default` — Подпись значения по умолчанию под описанием поля | Default | По умолчанию |
| `schema.required` — Отметка обязательного поля | required | обязательное |
| `schema.details` — Подпись раскрывающихся технических данных поля | Technical details | Технические данные |
| `schema.type` — Подпись типа поля | Type | Тип |
| `schema.scope` — Подпись уровня настроек | Level | Уровень |
| `schema.validation` — Подпись правила проверки | Validation | Проверка |
| `schema.provenance` — Подпись места в схеме | Schema | Схема |
| `schema.description_missing` — Текст, когда у поля нет описания | No description yet. | Описания пока нет. |
| `schema.inherited` — Значение наследуется | inherited | наследуется |
| `schema.default_inherited` — Значение наследуется, иначе встроенное; `{value}` — встроенное | inherited; otherwise {value} | наследуется, иначе {value} |
| `schema.path` — Подпись точного пути поля | Path | Путь |
| `schema.key_region` — Имя области в пути поля | region | область |
| `schema.key_locale` — Код языка в пути поля | locale | язык |
| `schema.key_any` — Произвольный ключ в пути поля | key | ключ |
| `branding.credit` — Подпись «Сделано на Docara»; `{docara}` — ссылка | Made with {docara} | Сделано на {docara} |

### Ключи, которые не используются

Шаблоны `docara init` записывают `reader.appearance`, `reader.appearance_description`, `reader.help`, но интерфейс Docara 3.x их сейчас не показывает.

<!-- docara:lang-reference:end -->

## См. также

- [Мультиязычный сайт](/ru/authoring/multilingual-site/) — как устроены языки и запасные языки
- [Коды ошибок lang.json](/ru/reference/errors/site/#строки-интерфейса-lang-json)
- [Фоновые эффекты](/ru/components/effects/) — подписи кнопки паузы
