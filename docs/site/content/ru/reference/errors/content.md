---
description: Коды ошибок в страницах Markdown — front matter, блоки, компоненты и их параметры, картинки, ссылки и оглавление — с причиной и способом исправления.
---

# Ошибки страниц и Markdown

Эти ошибки относятся к одной странице. В пояснении после кода обычно есть путь
к файлу и номер строки. Как пишутся сами блоки, показано на страницах
[Markdown](/authoring/markdown/) и [компонентов](/components/).

## Заголовок страницы (front matter)

Front matter — блок между двумя строками `---` в начале страницы. Docara
принимает в нём только ключи `title`, `description`, `tags`, `draft`,
`noindex`, `translation_key`, `profile` и `composition_recipe`, по одному на
строку.

| Код | Причина и что сделать |
| --- | --- |
| `FRONT_MATTER_UNTERMINATED` | Блок начинается строкой `---`, но не закрыт. **Что сделать:** Добавьте закрывающую строку `---`. |
| `FRONT_MATTER_SYNTAX_INVALID` | Строка не похожа на `ключ: значение`, например ключ с заглавными буквами или многострочное значение. **Что сделать:** Пишите одну пару `ключ: значение` на строку. |
| `FRONT_MATTER_KEY_UNKNOWN` | Ключ не входит в список поддерживаемых. **Что сделать:** Удалите ключ или перенесите настройку в `<page>.page.json`. |
| `FRONT_MATTER_KEY_DUPLICATE` | Ключ указан дважды. **Что сделать:** Оставьте один. |
| `FRONT_MATTER_VALUE_EMPTY` | У ключа нет значения или строка в кавычках пустая. **Что сделать:** Укажите значение или удалите ключ. |
| `FRONT_MATTER_STRING_INVALID` | Текст в двойных кавычках не является корректной строкой JSON, или текст без кавычек содержит `[`, `]`, `{`, `}`. **Что сделать:** Возьмите такой текст в двойные кавычки: `title: "Поле [beta]"`. |
| `FRONT_MATTER_DRAFT_INVALID` | `draft` имеет значение, отличное от `true` или `false`. **Что сделать:** Укажите `draft: true` или `draft: false`. |
| `FRONT_MATTER_NOINDEX_INVALID` | `noindex` имеет значение, отличное от `true` или `false`. **Что сделать:** Укажите `noindex: true` или `noindex: false`. |
| `FRONT_MATTER_TAGS_INVALID` | `tags` записан не списком в квадратных скобках. **Что сделать:** Пишите `tags: [ui, status]`. |
| `FRONT_MATTER_TAG_INVALID` | Метка содержит заглавные буквы, пробелы или другие символы. **Что сделать:** Используйте строчные буквы любого алфавита, цифры, `.`, `_` и `-`, например `tags: [деплой, i18n]`. |
| `FRONT_MATTER_TAG_DUPLICATE` | Метка повторяется. **Что сделать:** Удалите повтор. |
| `FRONT_MATTER_TRANSLATION_KEY_INVALID` | `translation_key` не является строчным идентификатором. **Что сделать:** Используйте строчные латинские буквы, цифры, `.`, `_` и `-`, например `start.install`. |
| `FRONT_MATTER_PROFILE_INVALID` | Неизвестный профиль страницы. **Что сделать:** Укажите один из профилей: `landing`, `article`, `tutorial`, `how_to`, `reference`, `explanation`. |
| `FRONT_MATTER_COMPOSITION_RECIPE_INVALID` | Путь в `composition_recipe` не является безопасным относительным путём к `.json`. **Что сделать:** Укажите путь от корня проекта без `..` и `//`. |

## Общие правила блоков

Блоки Markdown начинаются строкой `:::имя` и заканчиваются строкой `:::`.

| Код | Причина и что сделать |
| --- | --- |
| `MARKDOWN_BLOCK_UNCLOSED` | У блока нет закрывающей строки `:::`. **Что сделать:** Добавьте `:::` после содержимого блока. Номер строки начала есть в сообщении. |
| `MARKDOWN_BLOCK_EMPTY` | Блок открыт и сразу закрыт. **Что сделать:** Добавьте содержимое или удалите блок. |
| `MARKDOWN_BLOCK_NESTING_UNSUPPORTED` | Внутри блока стоит другой блок Docara или Smart-блок. **Что сделать:** Вынесите внутренний блок наружу. Внутри можно использовать обычный Markdown. Исключения — `grid` с `card` и `columns`. |
| `MARKDOWN_BLOCK_INDENTATION_UNSUPPORTED` | Блок начинается с отступа, например внутри списка или цитаты. **Что сделать:** Начинайте строку `:::имя` без отступа, на верхнем уровне страницы. |
| `MARKDOWN_BLOCK_LIMIT_EXCEEDED`, `FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED` | На странице больше 64 строк-открытий блоков и компонентов. **Что сделать:** Разделите страницу на несколько. |
| `MARKDOWN_BLOCK_INPUT_INVALID`, `MARKDOWN_INLINE_INPUT_INVALID` | Файл не в кодировке UTF-8. **Что сделать:** Сохраните страницу в UTF-8. |
| `MARKDOWN_COMPONENT_ATTRIBUTES_INVALID` | Атрибуты в `{...}` после имени блока записаны с ошибкой. **Что сделать:** Пишите `{имя=значение}`; значение с пробелами берите в двойные кавычки. |
| `MARKDOWN_COMPONENT_ATTRIBUTE_DUPLICATE` | Атрибут указан дважды. **Что сделать:** Оставьте один. |
| `MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN` | Блок не поддерживает такой атрибут. **Что сделать:** Проверьте список атрибутов на странице блока в [каталоге компонентов](/components/). |
| `MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID` | Значение атрибута не из списка допустимых. **Что сделать:** Возьмите значение из описания блока. |
| `MARKDOWN_RAW_HTML_FORBIDDEN` | В Markdown есть HTML-теги. Docara не пропускает HTML в страницу. **Что сделать:** Перепишите фрагмент на Markdown или поместите код в блок [html](/components/html/) или [example](/components/example/), которые показывают его в изолированной рамке. |
| `MARKDOWN_BLOCK_PLACEHOLDER_CARDINALITY_INVALID`, `MARKDOWN_INLINE_PLACEHOLDER_CARDINALITY_INVALID`, `FRAMEWORK_PLACEHOLDER_CARDINALITY_INVALID` | Текст страницы случайно совпал со служебной меткой, которой Docara отмечает место блока. **Что сделать:** Измените текст рядом с блоком. Если ошибка повторяется, сообщите о ней в репозиторий Docara. |

## Ссылки и картинки

| Код | Причина и что сделать |
| --- | --- |
| `MARKDOWN_LINK_URL_UNSAFE`, `MARKDOWN_IMAGE_URL_UNSAFE` | Ссылка или картинка использует опасную схему, например `javascript:` или `data:`. **Что сделать:** Используйте `https://`, `mailto:` или путь внутри сайта. |
| `MARKDOWN_LOCAL_IMAGE_UNSAFE` | Картинка с путём `/assets/...` не найдена, лежит вне каталога `assets/` сайта, проходит через ссылку или имеет неподдерживаемый формат. **Что сделать:** Положите файл в `assets/` рядом с `docara.json`. Подходят AVIF, GIF, JPEG, PNG, SVG и WebP. |
| `MARKDOWN_LOCAL_IMAGE_CASE_MISMATCH` | Регистр букв в пути не совпадает с именем файла, например `Logo.png` и `logo.png`. **Что сделать:** Исправьте путь. На сервере с Linux такая картинка не откроется. |
| `MARKDOWN_LOCAL_IMAGE_CONTEXT_INVALID`, `MARKDOWN_LOCAL_IMAGE_RECEIPT_INVALID` | Картинку из `/assets/` использует файл вне каталога содержимого языка. **Что сделать:** Используйте такие картинки только на страницах в `content/<язык>/`. |

## Блоки страницы

| Код | Причина и что сделать |
| --- | --- |
| `MARKDOWN_ALERT_HEADING_REQUIRED` | [Alert](/components/alert/) не начинается с заголовка уровня 2–5. **Что сделать:** Первой строкой блока поставьте заголовок, например `## Внимание`. |
| `MARKDOWN_ALERT_CONTENT_REQUIRED` | В alert нет текста после заголовка. **Что сделать:** Добавьте поясняющий текст. |
| `MARKDOWN_BANNER_CONTENT_REQUIRED` | [Banner](/components/banner/) пустой. **Что сделать:** Добавьте текст. |
| `MARKDOWN_DETAILS_HEADING_REQUIRED` | [Details](/components/details/) не начинается с заголовка уровня 2–4. **Что сделать:** Первой строкой поставьте заголовок, он станет подписью раскрывающегося блока. |
| `MARKDOWN_DETAILS_CONTENT_REQUIRED` | В details нет текста после заголовка. **Что сделать:** Добавьте содержимое. |
| `MARKDOWN_STEPS_ORDERED_LIST_REQUIRED` | [Steps](/components/steps/) не содержит ровно один нумерованный список. **Что сделать:** Оставьте в блоке один список `1.`, `2.`, `3.`. |
| `MARKDOWN_TABS_ITEMS_REQUIRED` | В [tabs](/components/tabs/) меньше двух вкладок. Вкладка — заголовок уровня 3 с текстом под ним. **Что сделать:** Добавьте не меньше двух заголовков `###` с содержимым. |
| `MARKDOWN_TABS_ITEM_INVALID` | У вкладки нет подписи или содержимого. **Что сделать:** Дайте каждому заголовку `###` текст под ним. |
| `MARKDOWN_TREE_LIST_REQUIRED` | [Tree](/components/tree/) не содержит один вложенный маркированный список. **Что сделать:** Оставьте в блоке один список `-` с вложенными пунктами. |
| `MARKDOWN_FIGURE_IMAGE_REQUIRED` | [Figure](/components/figure/) содержит не одну картинку или лишний текст. **Что сделать:** Оставьте одну картинку и, при желании, один абзац подписи. |
| `MARKDOWN_MEDIA_CONTENT_REQUIRED` | В [media](/components/media/) нет картинки или текста. **Что сделать:** Добавьте одну картинку и текст рядом с ней. |
| `MARKDOWN_GRID_CARD_REQUIRED` | В [grid](/components/grid/) есть что-то кроме блоков `card`. **Что сделать:** Поместите содержимое в блоки [card](/components/card/). |
| `MARKDOWN_DOWNLOAD_LINK_REQUIRED` | [Download](/components/download/) содержит не одну ссылку. **Что сделать:** Оставьте одну ссылку на файл. |
| `MARKDOWN_QUOTE_ATTRIBUTION_REQUIRED` | У цитаты с подписью нет автора или источника. **Что сделать:** Укажите автора или источник. |
| `MARKDOWN_MATH_SOURCE_REQUIRED` | [Math](/components/math/) пустой. **Что сделать:** Добавьте формулу в записи TeX. |
| `MARKDOWN_DIAGRAM_SOURCE_REQUIRED` | [Diagram](/components/diagram/) пустой. **Что сделать:** Добавьте описание диаграммы. |
| `MARKDOWN_HTML_FENCE_REQUIRED` | [Html](/components/html/) содержит не один блок кода HTML. **Что сделать:** Оставьте один блок кода с языком `html`. |
| `MARKDOWN_BACKLINKS_LIMIT_INVALID` | `limit` у [backlinks](/components/backlinks/) вне диапазона 1–50. **Что сделать:** Укажите число от 1 до 50. |
| `MARKDOWN_SCHEMA_REFERENCE_FIELDS_INVALID` | `fields` у `schema_reference` называет поле, которого нет в схеме, или записан с пробелами. **Что сделать:** Перечислите поля верхнего уровня через запятую без пробелов: `fields=reading,search`. |
| `MARKDOWN_INLINE_COMPONENT_LABEL_REQUIRED` | Встроенный в текст компонент, например `:badge[...]` или `:kbd[...]`, пустой. **Что сделать:** Напишите текст в квадратных скобках: `:kbd[Esc]`. |
| `MARKDOWN_ATLAS_FILTER_INVALID` | Фильтр атласа содержит недопустимые символы. **Что сделать:** Перечислите идентификаторы через запятую: строчные латинские буквы, цифры, `.`, `_`, `-`. |

## Код из файла

| Код | Причина и что сделать |
| --- | --- |
| `MARKDOWN_CODE_SOURCE_REQUIRED` | У блока [кода из файла](/components/code-from-file/) нет `src`. **Что сделать:** Укажите путь к файлу в `src`. |
| `MARKDOWN_CODE_SOURCE_UNSAFE` | Путь в `src` абсолютный, ведёт за пределы проекта или на символическую ссылку, либо файла нет. **Что сделать:** Укажите путь к существующему файлу относительно страницы. |
| `MARKDOWN_CODE_SOURCE_INVALID` | Файл больше 1 МиБ или не в кодировке UTF-8. **Что сделать:** Возьмите файл меньше или нужный фрагмент через `lines`. |
| `MARKDOWN_CODE_LINES_INVALID` | `lines` записан не как `N` или `N-M`. **Что сделать:** Пишите `lines=5` или `lines=5-12`. |
| `MARKDOWN_CODE_LANGUAGE_INVALID` | Язык подсветки записан с недопустимыми символами. **Что сделать:** Укажите язык строчными буквами, например `php` или `json`. |

## Встраивание и предпросмотр

| Код | Причина и что сделать |
| --- | --- |
| `MARKDOWN_EMBED_LINK_REQUIRED` | [Embed](/components/embed/) содержит не одну ссылку. **Что сделать:** Оставьте одну ссылку на встраиваемый материал. |
| `MARKDOWN_EMBED_LABEL_REQUIRED` | У ссылки в embed нет текста. **Что сделать:** Напишите текст ссылки: его увидят до загрузки. |
| `MARKDOWN_EMBED_URL_UNSAFE` | Ссылка в embed использует опасную схему. **Что сделать:** Используйте адрес `https://`. |
| `MARKDOWN_EMBED_ORIGIN_FORBIDDEN` | Адрес не подходит выбранному провайдеру или режиму согласия. **Что сделать:** Используйте адрес того сервиса, который указан в `provider`. Список адресов — на странице [embed](/components/embed/). |
| `MARKDOWN_EMBED_ID_INVALID` | `id` у embed содержит недопустимые символы. **Что сделать:** Используйте строчные латинские буквы, цифры и дефис. |
| `MARKDOWN_INTERNAL_PREVIEW_LINK_REQUIRED` | Предпросмотр страницы сайта содержит не одну ссылку. **Что сделать:** Оставьте одну ссылку. |
| `MARKDOWN_INTERNAL_PREVIEW_TITLE_REQUIRED` | У предпросмотра нет заголовка рамки. **Что сделать:** Добавьте заголовок: его прочитает программа экранного доступа. |
| `MARKDOWN_INTERNAL_PREVIEW_URL_UNSAFE` | Ссылка ведёт на другой сайт или записана не от корня. **Что сделать:** Укажите путь этого же сайта, начинающийся с `/`. |

## Примеры кода

| Код | Причина и что сделать |
| --- | --- |
| `MARKDOWN_EXAMPLE_FENCED_SOURCE_REQUIRED` | В [example](/components/example/) нет блока кода. **Что сделать:** Добавьте блок кода Markdown или HTML, CSS и JavaScript. |
| `MARKDOWN_EXAMPLE_SOURCE_ONLY` | В example есть текст вне блоков кода. **Что сделать:** Вынесите пояснение за пределы блока. |
| `MARKDOWN_EXAMPLE_HTML_REQUIRED` | Есть CSS или JavaScript, но нет HTML. **Что сделать:** Добавьте блок кода `html`. |
| `MARKDOWN_EXAMPLE_SOURCE_COMBINATION_INVALID` | Пример на Markdown смешан с HTML, CSS или JavaScript. **Что сделать:** Сделайте два отдельных примера. |
| `MARKDOWN_EXAMPLE_SOURCE_DUPLICATE` | Два блока кода одного языка. **Что сделать:** Объедините их в один. |
| `MARKDOWN_EXAMPLE_LABEL_INVALID` | Подпись примера пустая. **Что сделать:** Напишите подпись или удалите атрибут. |
| `MARKDOWN_EXAMPLE_PREVIEW_INVALID` | `preview` не равен `auto`, `inline` или `sandbox`. **Что сделать:** Укажите одно из трёх значений. |
| `MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED` | Указан `preview=inline`, но пример запускает скрипт или стили, которым нужна изолированная рамка. **Что сделать:** Уберите `preview=inline` или укажите `preview=sandbox`. |
| `MARKDOWN_EXAMPLE_ID_INVALID` | `id` примера из каталога `examples/` записан неверно. **Что сделать:** Укажите путь строчными буквами через `/` внутри `examples/`. |
| `MARKDOWN_EXAMPLE_ID_BODY_CONFLICT` | У примера одновременно `id` и код внутри блока. **Что сделать:** Оставьте что-то одно. |
| `MARKDOWN_EXAMPLE_PROJECT_CONTEXT_REQUIRED` | Пример по `id` используется вне сборки проекта. **Что сделать:** Собирайте страницу командой `docara build` в каталоге сайта. |
| `MARKDOWN_EXAMPLE_INLINE_ID_DUPLICATE` | Два примера на странице получили один идентификатор. **Что сделать:** Дайте примерам разные `id` или подписи. |
| `MARKDOWN_EXAMPLE_INLINE_ID_CHECK_FAILED` | Страницу не удалось разобрать для проверки примеров. **Что сделать:** Исправьте ошибку разметки рядом с примером, о которой сообщает сборка. |

## Блоки лендинга

### Hero

| Код | Причина и что сделать |
| --- | --- |
| `MARKDOWN_HERO_H1_REQUIRED` | [Hero](/components/hero/) не начинается с заголовка первого уровня. **Что сделать:** Первой строкой поставьте `# Заголовок`. |
| `MARKDOWN_HERO_DESCRIPTION_REQUIRED` | Нет одного или двух абзацев описания. **Что сделать:** Добавьте один-два абзаца после заголовка. |
| `MARKDOWN_HERO_STRUCTURE_INVALID` | В hero есть что-то кроме заголовка, абзацев, ссылок-кнопок и картинки. **Что сделать:** Уберите списки, таблицы и другие блоки. |
| `MARKDOWN_HERO_LINK_REQUIRED` | У кнопки нет текста. **Что сделать:** Напишите текст ссылки. |
| `MARKDOWN_HERO_LINK_UNSAFE`, `MARKDOWN_HERO_IMAGE_UNSAFE` | Ссылка или картинка использует опасную схему. **Что сделать:** Используйте `https://` или путь сайта. |
| `MARKDOWN_HERO_MEDIA_IMAGE_COUNT_INVALID` | Больше одной картинки. **Что сделать:** Оставьте одну. |
| `MARKDOWN_HERO_MEDIA_IMAGE_REQUIRED` | `media=side` или `media=background` без картинки. **Что сделать:** Добавьте одну картинку. |
| `MARKDOWN_HERO_MEDIA_IMAGE_FORBIDDEN` | `media=none`, но в hero есть картинка. **Что сделать:** Уберите картинку или укажите `media=side`. |
| `MARKDOWN_HERO_MEDIA_VARIANT_INCOMPATIBLE` | `media=side` без `variant=split`. **Что сделать:** Добавьте `variant=split`. |
| `MARKDOWN_HERO_SIDE_ALT_REQUIRED` | У боковой картинки пустой alt. **Что сделать:** Опишите картинку в `![описание](...)`. |
| `MARKDOWN_HERO_BACKGROUND_ALT_FORBIDDEN` | У фоновой картинки есть alt. Фон не несёт смысла, и программа экранного доступа не должна его читать. **Что сделать:** Оставьте alt пустым: `![](...)`. |
| `MARKDOWN_HERO_BACKGROUND_MODE_REQUIRED` | Настройки фона указаны без `media=background`. **Что сделать:** Добавьте `media=background` или уберите настройки фона. |
| `MARKDOWN_HERO_SIDE_IMAGE_UNSAFE`, `MARKDOWN_HERO_BACKGROUND_UNSAFE` | Картинка не найдена в `assets/` сайта или имеет неподдерживаемый формат. **Что сделать:** Положите файл в `assets/` и укажите путь `/assets/...`. |
| `MARKDOWN_HERO_SIDE_IMAGE_CASE_MISMATCH`, `MARKDOWN_HERO_BACKGROUND_CASE_MISMATCH` | Регистр букв в пути не совпадает с именем файла. **Что сделать:** Исправьте путь. |
| `MARKDOWN_HERO_SIDE_IMAGE_CONTEXT_INVALID`, `MARKDOWN_HERO_BACKGROUND_CONTEXT_INVALID` | Картинку из `/assets/` использует файл вне `content/<язык>/`. **Что сделать:** Используйте hero на страницах в каталоге языка. |
| `MARKDOWN_HERO_EFFECT_INVALID` | `effect` — не имя эффекта из каталога и не адрес `https://.../effect.json`. **Что сделать:** Укажите имя эффекта, например `aurora-curtain`. |
| `MARKDOWN_HERO_EFFECT_REQUIRED` | `effect_theme` или `effect_settings` указаны без `effect`. **Что сделать:** Добавьте `effect` или уберите эти атрибуты. |
| `MARKDOWN_HERO_EFFECT_SETTINGS_INVALID` | `effect_settings` не является объектом JSON. **Что сделать:** Запишите настройки как `{"speed": 0.5}`. |
| `MARKDOWN_HERO_EFFECT_MEDIA_CONFLICT` | Одновременно фоновый эффект и фоновая картинка. **Что сделать:** Оставьте что-то одно. |

Коды фонового эффекта, которые зависят от `docara.json`, описаны в разделе
[Фоновые эффекты](/reference/errors/site/#фоновые-эффекты).

### Features, logos, CTA, showcase, promo, columns, surface

| Код | Причина и что сделать |
| --- | --- |
| `MARKDOWN_FEATURES_UNORDERED_LIST_REQUIRED` | В features нет одного плоского маркированного списка. **Что сделать:** Оставьте один список `-` без вложенных пунктов. |
| `MARKDOWN_FEATURES_ITEM_COUNT_INVALID` | Пунктов меньше двух или больше шести. **Что сделать:** Оставьте от двух до шести. |
| `MARKDOWN_FEATURES_ITEM_CONTENT_INVALID` | В пункте больше одного абзаца или неподдерживаемый элемент. **Что сделать:** Оставьте в пункте один абзац текста. |
| `MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED` | Пункт без видимого текста. **Что сделать:** Добавьте текст. |
| `MARKDOWN_FEATURES_IMAGE_UNSAFE` | Картинка в пункте использует опасную схему. **Что сделать:** Используйте `https://` или путь сайта. |
| `MARKDOWN_LOGOS_UNORDERED_LIST_REQUIRED` | В [logos](/components/logos/) нет одного плоского списка. **Что сделать:** Оставьте один список `-`. |
| `MARKDOWN_LOGOS_ITEM_COUNT_INVALID` | Логотипов меньше двух или больше двенадцати. **Что сделать:** Оставьте от двух до двенадцати. |
| `MARKDOWN_LOGOS_ITEM_CONTENT_INVALID` | В пункте не один абзац. **Что сделать:** Оставьте в пункте одну картинку или ссылку с картинкой. |
| `MARKDOWN_CTA_LINK_REQUIRED` | В cta не ровно одна ссылка с текстом. **Что сделать:** Оставьте одну ссылку-кнопку. |
| `MARKDOWN_CTA_LINK_UNSAFE` | Ссылка использует опасную схему. **Что сделать:** Используйте `https://` или путь сайта. |
| `MARKDOWN_SHOWCASE_HEADING_REQUIRED`, `MARKDOWN_PROMO_HEADING_REQUIRED` | Блок не начинается с заголовка нужного уровня. **Что сделать:** Первой строкой поставьте заголовок. |
| `MARKDOWN_SHOWCASE_STRUCTURE_INVALID`, `MARKDOWN_PROMO_STRUCTURE_INVALID` | В блоке есть что-то кроме абзацев, ссылок и одной картинки в конце. **Что сделать:** Уберите лишнее; картинку поставьте последней. |
| `MARKDOWN_SHOWCASE_DESCRIPTION_REQUIRED`, `MARKDOWN_PROMO_DESCRIPTION_REQUIRED` | Нет абзаца описания. **Что сделать:** Добавьте абзац после заголовка. |
| `MARKDOWN_SHOWCASE_IMAGE_REQUIRED`, `MARKDOWN_PROMO_IMAGE_REQUIRED` | Нет картинки. **Что сделать:** Добавьте картинку последней строкой блока. |
| `MARKDOWN_SHOWCASE_IMAGE_ALT_REQUIRED` | У картинки пустой alt. **Что сделать:** Опишите картинку. |
| `MARKDOWN_SHOWCASE_IMAGE_UNSAFE`, `MARKDOWN_PROMO_IMAGE_UNSAFE`, `MARKDOWN_SHOWCASE_LINK_UNSAFE`, `MARKDOWN_PROMO_LINK_UNSAFE` | Картинка или ссылка использует опасную схему. **Что сделать:** Используйте `https://` или путь сайта. |
| `MARKDOWN_SHOWCASE_LINK_REQUIRED`, `MARKDOWN_PROMO_LINK_REQUIRED` | У ссылки нет текста. **Что сделать:** Напишите текст ссылки. |
| `MARKDOWN_COLUMNS_REGION_COUNT_INVALID` | В columns меньше двух или больше четырёх колонок. **Что сделать:** Разделите содержимое на 2–4 части. |
| `MARKDOWN_COLUMNS_SEPARATOR_INVALID` | Колонки разделены не строкой `---` с пустыми строками до и после. **Что сделать:** Поставьте между колонками отдельную строку `---`, окружённую пустыми строками. |
| `MARKDOWN_COLUMNS_REGION_EMPTY` | Одна из колонок пустая. **Что сделать:** Добавьте в неё текст или уменьшите число колонок. |
| `MARKDOWN_SURFACE_CONTENT_REQUIRED` | [Surface](/components/surface/) пустой. **Что сделать:** Добавьте содержимое. |
| `MARKDOWN_SURFACE_BACKGROUND_REQUIRED` | Положение фона указано без `background_image`. **Что сделать:** Добавьте `background_image` или уберите настройки положения. |
| `MARKDOWN_SURFACE_OVERLAY_REQUIRED` | `overlay_strength` указан при `overlay=none`. **Что сделать:** Укажите `overlay=light` или `overlay=dark`. |
| `MARKDOWN_SURFACE_BACKGROUND_UNSAFE`, `MARKDOWN_SURFACE_BACKGROUND_CASE_MISMATCH`, `MARKDOWN_SURFACE_BACKGROUND_CONTEXT_INVALID` | Фоновая картинка не найдена в `assets/`, путь отличается регистром или страница лежит вне `content/<язык>/`. **Что сделать:** Положите файл в `assets/` и укажите точный путь `/assets/...`. |

## Строчные компоненты: значок, бейдж, кнопка, клавиша

Строчные компоненты пишутся внутри абзаца: `:icon[имя]{...}`,
`:badge[текст]{...}`, `:button[текст]{...}`, `:kbd[клавиша]`. Параметры каждого
описаны на его странице: [значок](/ru/components/icon/),
[бейдж](/ru/components/badge/), [кнопка-ссылка](/ru/components/button/),
[клавиша](/ru/components/kbd/).

| Код | Причина и что сделать |
| --- | --- |
| `CONTENT_COMPONENT_ICON_UNKNOWN` | Значка с таким именем нет в наборе Material Symbols, который поставляется с Docara. Чаще всего это опечатка: `:icon[setings]` вместо `:icon[settings]`. **Что сделать:** Найдите имя в [списке всех значков](/ru/components/icon/#все-значки) и исправьте его. |
| `CONTENT_COMPONENT_SLOT_INVALID` | Имя значка содержит заглавные буквы, пробелы или другие символы, например `:icon[Search]`. **Что сделать:** Пишите имя строчными латинскими буквами, цифрами и `_`, например `check_circle`. |
| `CONTENT_COMPONENT_PROP_UNKNOWN` | У компонента нет такого параметра, например `:kbd[Esc]{size=1}`. **Что сделать:** Удалите параметр или исправьте его имя по странице компонента. |
| `CONTENT_COMPONENT_PROP_INVALID` | Значение параметра не из списка допустимых, например `:icon[search]{weight=bold}`, или у кнопки нет безопасного `href`. **Что сделать:** Возьмите значение со страницы компонента. |
| `CONTENT_COMPONENT_PROP_COMBINATION_INVALID` | Параметры по отдельности верны, но вместе не работают, например `:icon[search]{variant=main}` без контейнера. **Что сделать:** Добавьте `container=square` или `container=circle` либо уберите `variant`. |
| `CONTENT_COMPONENT_ICON_CATALOG_INVALID` | Файлы набора значков в установленной Docara повреждены или не совпадают друг с другом. **Что сделать:** Переустановите Docara через Composer. Если ошибка повторяется, сообщите о ней в репозиторий Docara. |

## Компоненты и их параметры

Компоненты SIMAI Framework вызываются блоком с параметрами в JSON. Список
параметров каждого компонента есть на его странице в
[каталоге компонентов](/components/).

| Код | Причина и что сделать |
| --- | --- |
| `FRAMEWORK_COMPONENT_UNSUPPORTED` | Компонента нет в каталоге этой версии Docara. **Что сделать:** Возьмите компонент из каталога. Не заменяйте его похожим HTML-элементом. |
| `FRAMEWORK_COMPONENT_ID_INVALID` | Имя компонента записано с ошибкой. **Что сделать:** Скопируйте имя со страницы компонента. |
| `FRAMEWORK_DIRECTIVE_UNCLOSED` | Блок компонента не закрыт. **Что сделать:** Добавьте закрывающую строку. |
| `FRAMEWORK_DIRECTIVE_JSON_INVALID` | Параметры компонента не являются корректным JSON. **Что сделать:** Проверьте кавычки и запятые. |
| `FRAMEWORK_DIRECTIVE_PROPS_INVALID` | Параметры записаны не объектом `{...}`. **Что сделать:** Оберните параметры в фигурные скобки. |
| `FRAMEWORK_DIRECTIVE_NESTING_UNSUPPORTED` | Компонент вложен в другой компонент или блок. **Что сделать:** Вынесите его на верхний уровень страницы. |
| `FRAMEWORK_DIRECTIVE_INDENTATION_UNSUPPORTED` | Блок компонента начинается с отступа. **Что сделать:** Уберите отступ. |
| `FRAMEWORK_DIRECTIVE_MARKDOWN_INVALID` | Страница не в кодировке UTF-8. **Что сделать:** Сохраните файл в UTF-8. |
| `FRAMEWORK_PROP_UNKNOWN` | У компонента нет такого параметра. В сообщении указано `компонент:параметр`. **Что сделать:** Удалите параметр или исправьте его имя. |
| `FRAMEWORK_PROP_REQUIRED` | Не задан обязательный параметр. **Что сделать:** Добавьте его. |
| `FRAMEWORK_PROP_MANAGED` | Параметр задаёт сама Docara, например идентификатор. **Что сделать:** Удалите его из вызова. |
| `FRAMEWORK_PROP_TYPE_INVALID` | Значение неверного типа, например строка вместо числа. **Что сделать:** Исправьте тип. В сообщении есть ожидаемый тип. |
| `FRAMEWORK_PROP_ENUM_INVALID` | Значения нет в списке допустимых. **Что сделать:** Возьмите значение со страницы компонента. |
| `FRAMEWORK_PROP_MINIMUM_INVALID`, `FRAMEWORK_PROP_MAXIMUM_INVALID` | Число меньше минимума или больше максимума. **Что сделать:** Укажите число в допустимом диапазоне. |
| `FRAMEWORK_PROP_MIN_LENGTH_INVALID`, `FRAMEWORK_PROP_MAX_LENGTH_INVALID` | Строка короче или длиннее допустимого. **Что сделать:** Сократите или дополните текст. |
| `FRAMEWORK_PROP_PATTERN_INVALID` | Строка не подходит под формат параметра. **Что сделать:** Проверьте формат на странице компонента. |
| `FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME` | Значение параметра запрещено в `simai-framework.lock.json` сайта: статический сайт его не поддерживает, например `"closable": true` или `"type": "success"` у `ui.alert`. В сообщении указано `компонент:параметр`. **Что сделать:** Выберите другое значение; список запретов — на странице [Smart-компоненты ui.*](/ru/components/smart-ui/). |
| `SMART_PROPS_INVALID` | Параметры Smart-компонента не прошли проверку. В сообщении есть путь к полю. **Что сделать:** Сверьте параметры с описанием Smart-компонента. |
| `TYPED_COMPONENT_UNSUPPORTED` | Такого блока Docara нет. **Что сделать:** Проверьте имя блока в [каталоге компонентов](/components/). |

## Оглавление и поиск по странице

| Код | Причина и что сделать |
| --- | --- |
| `DOCUMENT_OUTLINE_DEPTH_INVALID` | `reading.toc_depth` вне диапазона 2–6. **Что сделать:** Укажите число от 2 до 6. |
| `DOCUMENT_OUTLINE_HEADING_TEXT_REQUIRED` | Заголовок без текста, например из одной картинки без alt. **Что сделать:** Добавьте текст или alt картинки. |
| `DOCUMENT_OUTLINE_INVALID_UTF8`, `SEARCH_TEXT_INVALID_UTF8` | Страница содержит байты не в UTF-8. **Что сделать:** Сохраните файл в UTF-8. |
