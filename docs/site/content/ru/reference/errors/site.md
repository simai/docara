---
description: Коды ошибок настроек сайта — перенаправления, языки и переводы, строки lang.json, поиск, фоновые эффекты, страница 404, адрес сайта, тема, бренд и портал — с причиной и способом исправления.
---

# Настройки сайта

Эти ошибки относятся к `docara.json`, `redirects.json`, `lang.json` и
настройкам, которые действуют на весь сайт. Поля описаны в справочнике
[настроек сайта](/settings/site/).

## Перенаправления

Перенаправления задаются в файле из поля `redirects_file`, обычно
`redirects.json`. Каждая запись — `{"from": "старый/адрес", "to": "новый/адрес"}`
без ведущего `/`. Подробнее — в разделе
[Перенаправления](/authoring/redirects/).

| Код | Причина и что сделать |
| --- | --- |
| `PORTABLE_REDIRECT_SOURCE_INVALID` | Путь в `redirects_file` небезопасный: абсолютный или с `..`. **Что сделать:** Укажите файл внутри проекта, например `redirects.json`. |
| `PORTABLE_REDIRECT_SOURCE_UNSAFE`, `PORTABLE_REDIRECT_SOURCE_READ_FAILED` | Файл не найден или не читается. **Что сделать:** Проверьте имя файла и права на чтение. |
| `PORTABLE_REDIRECT_SOURCE_SYMLINK_FORBIDDEN` | Путь к файлу проходит через символическую ссылку. **Что сделать:** Замените ссылку настоящим файлом. |
| `PORTABLE_REDIRECT_JSON_INVALID` | Файл не является корректным JSON. **Что сделать:** Исправьте синтаксис. |
| `PORTABLE_REDIRECT_DOCUMENT_INVALID` | В файле не объект. **Что сделать:** Начните файл с `{"schema": "docara.redirects.v1", "version": 1, "redirects": [...]}`. |
| `PORTABLE_REDIRECT_RECORD_INVALID` | Запись в `redirects` — не объект. **Что сделать:** Запишите каждую запись как `{"from": "...", "to": "..."}`. |
| `PORTABLE_REDIRECT_SOURCE_DUPLICATED` | Один старый адрес указан дважды. **Что сделать:** Оставьте одну запись. |
| `PORTABLE_REDIRECT_CHAIN_FORBIDDEN` | Перенаправление ведёт на себя или на адрес, который сам перенаправляется. **Что сделать:** Ведите каждое перенаправление сразу на конечную страницу. |
| `PORTABLE_REDIRECT_TARGET_NOT_FOUND` | Новый адрес не совпадает ни с одной страницей сайта. **Что сделать:** Укажите адрес существующей страницы, например `ru/start/quick-start`. Для языков с префиксом он входит в адрес. |
| `PORTABLE_REDIRECT_TARGET_IDENTITY_INVALID` | Новый адрес похож на страницу, но не совпадает с ней точно. **Что сделать:** Скопируйте адрес страницы из собранного сайта без `/` в начале и в конце. |
| `PORTABLE_REDIRECT_PAGE_COLLISION` | По старому адресу всё ещё есть страница. **Что сделать:** Удалите или переименуйте старую страницу либо уберите перенаправление. |
| `PORTABLE_REDIRECT_ASSET_COLLISION` | Старый адрес совпадает с файлом из `assets/` или содержимого. **Что сделать:** Выберите другой адрес или переместите файл. |
| `PORTABLE_REDIRECT_PUBLICATION_FAILED`, `PORTABLE_REDIRECT_RECEIPT_PUBLICATION_FAILED` | Сборка не смогла записать страницу перенаправления. **Что сделать:** Проверьте место на диске и права на каталог сборки, затем повторите сборку. |
| `PORTABLE_REDIRECT_PAGE_IDENTITY_INVALID` | Внутренняя ошибка сборки: у страниц нет точных адресов, когда строятся перенаправления. **Что сделать:** Пересоберите сайт целиком. Если ошибка повторяется, сообщите о ней в репозиторий Docara. |

## Языки

Языки задаются в `locales`, адреса языков — в `locale_routing`. Подробнее —
в разделе [Локали и маршрутизация](/ru/authoring/multilingual-site/).

| Код | Причина и что сделать |
| --- | --- |
| `LOCALE_TAG_INVALID` | Код языка записан неверно. **Что сделать:** Используйте код BCP 47: `ru`, `en`, `fr-CA`. |
| `LOCALE_REGISTRY_EMPTY` | `locales` пустой. **Что сделать:** Добавьте хотя бы один язык. |
| `LOCALE_DEFINITION_INVALID` | Описание языка — не объект. **Что сделать:** Запишите язык как объект с `label`, `direction` и `content_root`. |
| `LOCALE_NOT_CONFIGURED` | Страница или команда ссылается на язык, которого нет в `locales`. **Что сделать:** Добавьте язык в `locales` или исправьте код языка. |
| `LOCALE_DUPLICATE` | Один язык указан дважды в разном написании, например `en-us` и `en-US`. **Что сделать:** Оставьте один ключ. |
| `LOCALE_DIRECTION_INVALID` | `direction` не равен `ltr` или `rtl`. **Что сделать:** Укажите `ltr` или `rtl`. |
| `LOCALE_CONTENT_ROOT_INVALID` | У языка пустой `content_root`. **Что сделать:** Укажите каталог, например `content/ru`. |
| `LOCALE_CONTENT_ROOT_OVERLAP` | Каталоги двух языков вложены друг в друга. **Что сделать:** Дайте каждому языку отдельный каталог, например `content/ru` и `content/en`. |
| `LOCALE_PUBLIC_PREFIX_INVALID` | Префикс адреса содержит недопустимые символы. **Что сделать:** Используйте строчные латинские буквы, цифры и дефис. |
| `LOCALE_PUBLIC_PREFIX_DUPLICATE` | У двух языков одинаковый префикс. **Что сделать:** Сделайте префиксы разными. |
| `LOCALE_FALLBACK_INVALID` | Запасной язык записан не строкой. **Что сделать:** Перечислите коды языков строками: `["en"]`. |
| `LOCALE_FALLBACK_DUPLICATE` | Запасной язык указан дважды. **Что сделать:** Удалите повтор. |
| `LOCALE_FALLBACK_NOT_CONFIGURED` | Запасного языка нет в `locales`. **Что сделать:** Добавьте его в `locales` или уберите из `fallbacks`. |
| `LOCALE_FALLBACK_CYCLE` | Языки ссылаются друг на друга по кругу, например `ru` → `en` → `ru`. **Что сделать:** Разорвите круг: у одного языка `fallbacks` должен быть пустым. |
| `DEFAULT_LOCALE_NOT_CONFIGURED` | `default_locale` нет в `locales`. **Что сделать:** Добавьте этот язык в `locales` или исправьте `default_locale`. |
| `DEFAULT_LOCALE_MUST_BE_UNPREFIXED` | При `strategy: default_unprefixed` у основного языка есть префикс. Без явного `public_prefix` префиксом становится код языка. **Что сделать:** Укажите у основного языка `"public_prefix": ""`. |
| `DEFAULT_LOCALE_ROOT_COLLISION` | `root: default_locale`, но основной язык открывается не от корня. **Что сделать:** Укажите у основного языка `"public_prefix": ""` или `root: redirect`. |
| `LOCALE_PREFIX_REQUIRED` | При `strategy: prefixed` у языка нет префикса. **Что сделать:** Задайте `public_prefix` каждому языку. |
| `LOCALE_ROOT_REDIRECT_REQUIRED` | При `strategy: prefixed` не указан `root: redirect`. **Что сделать:** Укажите `"root": "redirect"`. |
| `LOCALE_ROOT_REDIRECT_TARGET_INVALID` | `root: redirect`, но у основного языка пустой префикс. **Что сделать:** Задайте основному языку префикс. |
| `LOCALE_ROUTING_INVALID`, `LOCALE_ROUTING_STRATEGY_INVALID`, `LOCALE_ROOT_ROUTE_INVALID` | `locale_routing` записан не объектом или содержит неизвестное значение. **Что сделать:** Сверьте поля с [настройками сайта](/settings/site/#языки-и-переводы). |
| `LEGACY_LOCALE_REDIRECT_COLLISION` | Старый адрес без префикса, на который нужно поставить перенаправление, занят страницей. **Что сделать:** Переименуйте страницу или выключите `legacy_unprefixed_redirects`. |
| `LEGACY_UNPREFIXED_REDIRECTS_REQUIRE_PREFIXED_ROUTING` | `legacy_unprefixed_redirects: true` без `strategy: prefixed`. **Что сделать:** Включайте эти перенаправления только при `prefixed` или уберите поле. |
| `LOCALE_MISSING_PAGE_POLICY_INVALID` | `locales.missing_page_policy` не равен `skip` или `error`. **Что сделать:** Укажите `skip` или `error`. |
| `LOCALE_PAGE_MISSING` | Страницы нет на одном из языков, а `missing_page_policy` равен `error`. В сообщении есть ожидаемый путь. **Что сделать:** Создайте страницу по этому пути или укажите `missing_page_policy: skip`. |
| `PAGE_OUTSIDE_LOCALE_CONTENT_ROOTS` | Страница лежит вне каталогов всех языков. **Что сделать:** Перенесите страницу в каталог языка. |
| `PAGE_LOCALE_CONTENT_ROOT_MISMATCH` | `locale` в настройках страницы или раздела не совпадает с языком каталога. **Что сделать:** Удалите `locale` или исправьте его. |
| `PORTABLE_LOCALE_CONTENT_EMPTY` | В каталоге языка нет ни одной страницы Markdown. **Что сделать:** Добавьте страницы или уберите язык из `locales`. |
| `LOCALE_CANONICAL_URL_MISMATCH` | Адрес страницы не относится к её языку. Обычно это следствие другой ошибки настройки языков. **Что сделать:** Проверьте `public_prefix` и `content_root` языков. |
| `LOCALE_REDIRECT_OUTPUT_DUPLICATE`, `LOCALE_REDIRECT_PUBLICATION_FAILED`, `LOCALE_REDIRECT_RECEIPT_PUBLICATION_FAILED` | Сборка не смогла записать перенаправления между языками: два перенаправления на одно место или ошибка записи. **Что сделать:** Проверьте, что адреса языков не пересекаются со страницами, и повторите сборку. |

## Строки интерфейса (`lang.json`)

Список всех строк и их значения по умолчанию — на странице
[Общие подписи локали](/authoring/language-packs/#все-строки-интерфейса).

| Код | Причина и что сделать |
| --- | --- |
| `MESSAGE_NOT_FOUND` | В `lang.json` языка и его запасных языков нет обязательной строки. В сообщении есть её имя, например `search.placeholder`. **Что сделать:** Добавьте строку в `lang.json` или укажите запасной язык с этой строкой в `fallbacks`. |
| `MESSAGE_ID_INVALID`, `MESSAGE_PARAMETER_INVALID` | Внутренняя ошибка запроса строки. **Что сделать:** Сообщите о ней в репозиторий Docara. |
| `CONTENT_LANGUAGE_INVALID` | `lang.json` не является корректным JSON или не объект. **Что сделать:** Исправьте синтаксис. |
| `CONTENT_LANGUAGE_SOURCE_INVALID` | `lang.json` — символическая ссылка или лежит вне каталога языка. **Что сделать:** Положите настоящий файл в `content/<язык>/lang.json`. |
| `TARGET_LANG_NAMESPACE_FORBIDDEN` | В `lang.json` есть неизвестная группа, например `pages`. **Что сделать:** Оставьте только группы интерфейса из списка на странице [Общие подписи локали](/authoring/language-packs/). |
| `TARGET_LANG_VALUE_INVALID` | Строка пустая, длиннее 280 символов, многострочная или содержит HTML. **Что сделать:** Сократите строку и уберите теги. |
| `READER_PREFERENCES_COPY_MISSING` | Нет подписи для настройки в панели читателя. **Что сделать:** Добавьте строки группы `reader` в `lang.json` или запасной язык. |

## Переводы

Эти коды выдают команды `docara translations` и сборка при включённом
`translation_tracking`.

| Код | Причина и что сделать |
| --- | --- |
| `TRANSLATION_TRACKING_DISABLED` | Отслеживание переводов выключено. **Что сделать:** Включите `translation_tracking.enabled` в `docara.json`. |
| `TRANSLATION_CONFIG_INVALID` | `docara.json` не читается как JSON. **Что сделать:** Исправьте синтаксис. |
| `TRANSLATION_PROJECT_ROOT_INVALID` | Команда запущена не в каталоге проекта или каталог — ссылка. **Что сделать:** Запустите команду из каталога сайта. |
| `TRANSLATION_KIND_INVALID` | `--kind` не равен `page` или `lang`. **Что сделать:** Укажите `--kind=page` или `--kind=lang`. |
| `TRANSLATION_STATUS_FILTER_INVALID` | Неизвестный статус в фильтре. **Что сделать:** Используйте статусы из вывода `docara translations status`. |
| `TRANSLATION_TARGET_LOCALE_INVALID` | Язык перевода совпадает с языком оригинала. **Что сделать:** Укажите другой язык. |
| `TRANSLATION_REVIEW_INVALID` | Уровень проверки не равен `ai_verified` или `human_reviewed`. **Что сделать:** Укажите одно из двух значений. |
| `TRANSLATION_ACCEPT_SELECTION_REQUIRED` | Не указано, какие переводы принять. **Что сделать:** Назовите ключи, префикс ключа или статус. |
| `TRANSLATION_ACCEPT_SELECTION_EMPTY` | Под выбор не попал ни один непроверенный или устаревший перевод. **Что сделать:** Проверьте выбор: возможно, переводы уже приняты. |
| `TRANSLATION_ACCEPT_TARGET_INVALID` | Перевода с таким ключом нет или ключ неоднозначен. **Что сделать:** Проверьте ключ в отчёте `docara translations status`. |
| `TRANSLATION_EXCLUSION_REASON_REQUIRED` | Перевод исключается без причины. **Что сделать:** Укажите причину. |
| `TRANSLATION_LOCK_PATH_INVALID` | `translation_tracking.lock_file` — не имя файла в корне проекта. **Что сделать:** Укажите имя файла, например `translations.lock.json`. |
| `TRANSLATION_LOCK_INVALID` | Файл принятых переводов повреждён. **Что сделать:** Восстановите файл из системы контроля версий. |
| `TRANSLATION_LOCK_SOURCE_LOCALE_MISMATCH` | Язык оригинала в файле принятых переводов отличается от `docara.json`. **Что сделать:** Верните прежний `source_locale` или примите переводы заново. |
| `TRANSLATION_LANG_INVALID`, `TRANSLATION_PAGE_SOURCE_INVALID` | `lang.json` или страница не читаются как UTF-8 или лежат вне проекта. **Что сделать:** Сохраните файл в UTF-8 внутри каталога языка. |
| `TRANSLATION_PLAN_ID_INVALID`, `TRANSLATION_PLAN_INVALID`, `TRANSLATION_PLAN_HASH_MISMATCH` | Идентификатор плана не тот, что выдал `--dry-run`, или план изменён. **Что сделать:** Запустите `--dry-run` заново и примените выданный идентификатор. |
| `TRANSLATION_PLAN_STALE` | Файлы изменились после `--dry-run`. **Что сделать:** Запустите `--dry-run` заново. |
| `TRANSLATION_PLAN_PATH_UNSAFE`, `TRANSLATION_PLAN_WRITE_FAILED`, `TRANSLATION_WRITE_FAILED` | Команда не смогла записать файл: каталог — ссылка или нет прав. **Что сделать:** Проверьте права на каталог проекта и `.docara/`. |
| `TRANSLATION_WRITE_COLLISION` | Файл перевода уже существует. **Что сделать:** Удалите или переименуйте существующий файл. |
| `TRANSLATION_GATE_REPORT_INVALID` | Режим `publish-current` включён без отчёта о переводах. **Что сделать:** Включите `translation_tracking.enabled`. |
| `TRANSLATION_DUPLICATE_KEY_BLOCKS_PUBLICATION` | Один `translation_key` у нескольких страниц одного языка. **Что сделать:** Дайте каждой странице свой ключ. |

## Поиск

| Код | Причина и что сделать |
| --- | --- |
| `SEARCH_INDEX_LOCALE_EMPTY` | Поиск включён, но на этом языке все страницы исключены из поиска. **Что сделать:** Включите `search.indexed` хотя бы для одной страницы или выключите поиск для языка. |
| `SEARCH_DOCUMENT_OUTSIDE_BASE` | Адрес страницы лежит вне `base_url`. **Что сделать:** Проверьте `base_url` и `slug` страницы. |
| `SEARCH_TEXT_INVALID_UTF8`, `SEARCH_TEXT_PARSE_FAILED` | Текст страницы нельзя разобрать для поиска. **Что сделать:** Сохраните страницу в UTF-8 и исправьте ошибку разметки рядом с местом из сообщения. |
| `SEARCH_RUNTIME_MISSING`, `SEARCH_RUNTIME_INVALID_UTF8` | Файл поиска в пакете Docara повреждён или отсутствует. **Что сделать:** Переустановите Docara: `composer install`. |
| `SEARCH_COMPONENT_CALL_INVALID` | Внутренние данные компонента для поиска повреждены. **Что сделать:** Сообщите об ошибке в репозиторий Docara. |

## Фоновые эффекты

Эффекты подключаются полем `effects` в `docara.json` и параметром `effect`
блока [hero](/components/hero/).

| Код | Причина и что сделать |
| --- | --- |
| `EFFECTS_INVALID` | `effects` — не объект. **Что сделать:** Запишите `"effects": {"catalogue": "https://.../"}`. |
| `EFFECTS_CATALOGUE_INVALID` | Адрес каталога не `https://`, не заканчивается на `/` или не закреплён на коммите. **Что сделать:** Укажите адрес вида `https://cdn.jsdelivr.net/gh/simai/ui-effects@<коммит>/`. |
| `EFFECTS_CATALOGUE_REQUIRED` | Страница использует эффект, а `effects.catalogue` не задан. **Что сделать:** Добавьте `effects.catalogue` в `docara.json`. |
| `EFFECTS_OUTSIDE_CATALOGUE` | Эффект указан адресом вне каталога из `effects.catalogue`. **Что сделать:** Берите эффекты только из подключённого каталога. |
| `EFFECTS_INDEX_INVALID` | `effects.index` — не относительный путь к `.json`. **Что сделать:** Укажите путь вида `catalogue/index.json`. |
| `EFFECTS_INDEX_SHA256_INVALID` | `effects.index_sha256` — не 64 строчных шестнадцатеричных символа. **Что сделать:** Скопируйте контрольную сумму SHA-256 файла целиком. |
| `EFFECTS_BOOT_INVALID` | Скрипт запуска эффектов изменён после сборки. **Что сделать:** Пересоберите сайт и не правьте файлы в каталоге сборки. |

Коды параметров эффекта в самом блоке описаны в разделе
[Hero](/reference/errors/content/#hero).

## Страница 404

Docara создаёт `404.html` в корне сайта, если `not_found.enabled` не равен
`false`. Текст берётся из `404.md` основного языка или из строк
`error.not_found.*` в `lang.json`.

| Код | Причина и что сделать |
| --- | --- |
| `PORTAL_NOT_FOUND_PAGE_FORBIDDEN` | В сборке для портала указано `not_found.enabled: true`. Страницей 404 владеет портал. **Что сделать:** Уберите `not_found` из `docara.json`. |
| `PORTAL_FILE_COLLISION` | Сборка для портала публикует файл, которым владеет портал: `404.html`, `robots.txt`, `sitemap.xml`, `CNAME`, `.nojekyll`. **Что сделать:** Удалите этот файл из содержимого. |

## Адрес сайта и `llms.txt`

| Код | Причина и что сделать |
| --- | --- |
| `SITE_URL_INVALID` | `site_url` — не полный адрес `http(s)://`, содержит `?`, `#`, логин или `..`. **Что сделать:** Укажите адрес вида `https://example.com/docs/`. |
| `SITE_URL_BASE_MISMATCH` | Путь в `site_url` не совпадает с `base_url`. **Что сделать:** Задайте оба значения из одного адреса публикации. |
| `DISCOVERY_FILE_COLLISION` | Содержимое уже публикует `sitemap.xml`, `robots.txt` или `llms.txt`. **Что сделать:** Удалите свой файл или уберите `site_url`. |
| `DISCOVERY_LINK_INVALID` | Путь в `discovery.llms_links` небезопасный. **Что сделать:** Укажите путь относительно `base_url` без `..`. |
| `DISCOVERY_URL_OUTSIDE_BASE` | Адрес в `llms.txt` получился вне `base_url`. **Что сделать:** Проверьте `base_url`, `site_url` и пути ссылок. |

## Тема и бренд

| Код | Причина и что сделать |
| --- | --- |
| `THEME_INVALID` | `theme` — не объект с `color`. **Что сделать:** Запишите `"theme": {"color": "#0a7d4f"}`. |
| `THEME_COLOR_INVALID` | Цвет не в виде `#rgb` или `#rrggbb`. **Что сделать:** Укажите цвет шестнадцатеричным кодом. Названия цветов не принимаются. |
| `THEME_HUE_INVALID`, `THEME_CHROMA_INVALID` | `hue` вне 0–360 или `chroma` вне 0–150. **Что сделать:** Укажите целое число в диапазоне или удалите поле. |
| `THEME_FRAMEWORK_UNSUPPORTED` | Закреплённая версия SIMAI Framework не умеет строить палитру. **Что сделать:** Обновите `simai-framework.lock.json` до версии с генератором темы или уберите `theme`. |
| `THEME_PALETTE_FAILED`, `THEME_PALETTE_RESULT_INVALID`, `THEME_PALETTE_RUNTIME_UNAVAILABLE` | Генератор палитры не запустился или вернул неожиданный результат. **Что сделать:** Проверьте, что Node.js доступен сборке, и повторите сборку. |
| `BRANDING_CREDIT_SITE_ONLY` | `branding.credit` указан в `section.json` или `<page>.page.json`. **Что сделать:** Перенесите его в `docara.json`. |
| `BRAND_ASSET_NOT_FOUND` | Файл логотипа, значка или картинки для соцсетей не найден. **Что сделать:** Проверьте путь от корня проекта. |
| `BRAND_ASSET_PATH_INVALID`, `BRAND_ASSET_SYMLINK_FORBIDDEN` | Путь небезопасный или проходит через ссылку. **Что сделать:** Положите файл внутрь проекта и укажите относительный путь. |
| `BRAND_ASSET_TYPE_FORBIDDEN` | Формат файла не поддерживается. **Что сделать:** Используйте SVG, PNG, JPEG, WebP или ICO. |
| `BRAND_SHARE_IMAGE_TYPE_FORBIDDEN` | Картинка для соцсетей не PNG, JPEG или WebP. **Что сделать:** Сохраните её в одном из этих форматов. |
| `BRAND_ASSET_TOO_LARGE` | Файл больше 2 МБ. **Что сделать:** Уменьшите файл. |
| `BRAND_DARK_LOGO_REQUIRES_LOGO` | Задан `logo_dark` без `logo`. **Что сделать:** Добавьте основной `logo`. |
| `BRAND_ASSET_READ_FAILED`, `BRAND_ASSET_PUBLICATION_FAILED` | Файл не удалось прочитать или скопировать в сборку. **Что сделать:** Проверьте права на файл и место на диске. |

## Меню в шапке и панель читателя

| Код | Причина и что сделать |
| --- | --- |
| `DECLARATIVE_HEADER_NAVIGATION_ITEMS_REQUIRED` | `header_navigation.enabled: true` без `items`. **Что сделать:** Добавьте ссылки в `items` или выключите меню. |
| `DECLARATIVE_HEADER_NAVIGATION_LIMIT_EXCEEDED` | Ссылок больше восьми. **Что сделать:** Оставьте не больше восьми. |
| `DECLARATIVE_HEADER_NAVIGATION_INVALID` | Ссылка записана с ошибкой или `id` повторяется. **Что сделать:** Проверьте `id`, `label` и `href` каждой ссылки. |
| `READER_PREFERENCES_FIELD_UNKNOWN`, `READER_PREFERENCES_FIELD_INVALID` | Неизвестная настройка панели читателя. **Что сделать:** Используйте `appearance.theme`, `appearance.font_size` или `appearance.content_width`. |
| `READER_PREFERENCES_FIELD_DUPLICATE`, `READER_PREFERENCES_GROUP_DUPLICATE` | Настройка или группа указана дважды. **Что сделать:** Удалите повтор. |
| `READER_PREFERENCES_FIELD_GROUP_MISMATCH` | Настройка стоит не в своей группе. **Что сделать:** Перенесите её в группу `appearance`. |
| `READER_PREFERENCES_FIELD_HIDDEN_CONFLICT` | Настройка одновременно в группе и в `hidden_fields`. **Что сделать:** Оставьте её в одном месте. |
| `READER_PREFERENCES_GROUP_INVALID`, `READER_PREFERENCES_HIDDEN_FIELDS_INVALID`, `READER_PREFERENCES_VIEW_INVALID` | Группа, список скрытых настроек или `view` записаны неверно. **Что сделать:** Сверьте `reader_preferences` с [настройками сайта](/settings/site/#меню-чтение-и-поиск). |
| `READER_PREFERENCES_AUTHORED_VALUE_INVALID` | Значение по умолчанию для настройки читателя не из списка допустимых. **Что сделать:** Укажите одно из значений этой настройки. |

## Сборка для портала

Подробнее — в разделе [Сборка для портала](/build/portal/).

| Код | Причина и что сделать |
| --- | --- |
| `PORTAL_INVALID` | `portal` — не объект. **Что сделать:** Запишите `portal` объектом с `product` и `home_url`. |
| `PORTAL_PRODUCT_INVALID` | `portal.product` записан неверно. **Что сделать:** Используйте строчные латинские буквы, цифры и дефис, начиная с буквы. |
| `PORTAL_HOME_URL_INVALID`, `PORTAL_HOME_INVALID` | `home_url` — не адрес `https://` или содержит `?` и `#`. **Что сделать:** Укажите адрес вида `https://simai.io/ru/`. |
| `PORTAL_HOME_LABEL_INVALID` | `home_label` пустой или длиннее 80 символов. **Что сделать:** Сократите подпись. |
| `PORTAL_SITE_URL_REQUIRED` | Нет `site_url`. **Что сделать:** Укажите полный адрес сайта на портале. |
| `PORTAL_SINGLE_LOCALE_REQUIRED` | В сборке для портала больше одного языка. **Что сделать:** Собирайте каждый язык отдельно. |
| `PORTAL_UNPREFIXED_ROUTING_REQUIRED` | `locale_routing.strategy` не равен `default_unprefixed`. **Что сделать:** Укажите `default_unprefixed`: язык входит в путь портала. |
| `PORTAL_PROFILE_MISSING`, `PORTAL_SITE_MISSING` | `docara portal-artifact` запущена для сайта без `portal` или без `docara.json`. **Что сделать:** Запустите команду в каталоге сайта с настройкой `portal`. |
| `PORTAL_BUILD_MISSING`, `PORTAL_INDEX_MISSING` | Нет каталога сборки или в нём нет `index.html`. **Что сделать:** Сначала выполните `docara build`. |
| `PORTAL_BUILD_LINK`, `PORTAL_BUILD_UNREADABLE`, `PORTAL_PATH_TOO_LONG` | В сборке есть ссылка, нечитаемый файл или слишком длинное имя. **Что сделать:** Пересоберите сайт; сократите длинные пути. |
| `PORTAL_PAGE_INVALID`, `PORTAL_FRAMEWORK_PAIR_MISSING` | Страница сборки не похожа на собранную Docara для этого портала. **Что сделать:** Пересоберите сайт и не правьте файлы сборки. |
| `PORTAL_OUTPUT_INVALID`, `PORTAL_RELEASE_INVALID`, `PORTAL_DOCARA_VERSION_MISSING` | Архив не удалось создать или установка Docara повреждена. **Что сделать:** Проверьте каталог для архива и переустановите Docara. |
