---
description: Сайт на нескольких языках — любое число локалей по тегам BCP 47, свой адрес и направление письма у каждой, переводы, их актуальность и подписи интерфейса.
tags: [i18n, l10n, перевод, переводы, локализация, мультиязычность, язык, языки]
---

# Мультиязычный сайт

Docara не ограничивает проект русским и английским языками. Сайт объявляет
любое количество локалей по тегам BCP 47, например `ar`, `zh-Hans`, `fr-CA`
или `sr-Latn-RS`. Для каждой локали задаются собственное дерево Markdown,
публичный префикс адреса, направление письма и явная цепочка fallback для общих
подписей интерфейса. Одна команда собирает все объявленные локали.

В примере ниже русский публикуется под `/ru/`, английский — под `/en/`,
арабский — под `/ar/`, а `/` стабильно перенаправляет на основную локаль.

## 1. Создайте одинаковую структуру контента

```text
content/
  ru/
    index.md
    guide/
      install.md
  en/
    index.md
    guide/
      install.md
  ar/
    index.md
    guide/
      install.md
```

Минимальная русская страница `content/ru/index.md`:

```markdown
# Документация

Начните с руководства по установке.
```

Для `en` и `ar` создайте переводы с теми же относительными путями. Именно
совпадение `index.md` и `guide/install.md` связывает страницы для `hreflang` и
переключателя языка; Docara не угадывает отсутствующий перевод.

## 2. Объявите локали

Замените `docara.json` полным рабочим реестром:

```json
{
  "schema": "docara.site.v1",
  "title": "Acme Docs",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json",
  "content_root": "content/ru",
  "default_locale": "ru",
  "documentation_version": "current",
  "base_url": "/",
  "locales": {
    "missing_page_policy": "skip",
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "public_prefix": "ru",
      "fallbacks": []
    },
    "en": {
      "label": "English",
      "direction": "ltr",
      "content_root": "content/en",
      "public_prefix": "en",
      "fallbacks": []
    },
    "ar": {
      "label": "العربية",
      "direction": "rtl",
      "content_root": "content/ar",
      "public_prefix": "ar",
      "fallbacks": ["en"]
    }
  },
  "locale_routing": {
    "strategy": "prefixed",
    "root": "redirect",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": true
  },
  "translation_tracking": {
    "enabled": true,
    "source_locale": "ru",
    "mode": "report",
    "lock_file": "translations.lock.json"
  },
  "branding": {
    "title": "Acme",
    "label": "Docs",
    "mode": "text",
    "size": "medium"
  },
  "search": {
    "enabled": true,
    "indexed": true
  }
}
```

Все locale из `fallbacks` обязаны присутствовать в этом же реестре. Арабский
`content/ar/lang.json` может дополняться общими английскими UI-подписями, но
Markdown-контент не переводится fallback-механизмом. Для RTL явно задаётся
`"direction": "rtl"`.

Каждая локаль также может иметь собственное верхнее меню. Например,
`content/ru/section.json` содержит русские подписи:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {"id": "start", "label": "Быстрый старт", "href": "/ru/start/quick-start/"},
      {"id": "github", "label": "GitHub", "href": "https://github.com/simai/docara"}
    ]
  }
}
```

А `content/en/section.json` независимо задаёт другой состав:

```json
{
  "schema": "docara.section.v1",
  "header_navigation": {
    "enabled": true,
    "items": [
      {"id": "docs", "label": "Documentation", "href": "/en/"},
      {"id": "components", "label": "Components", "href": "/en/components/"},
      {"id": "github", "label": "GitHub", "href": "https://github.com/simai/docara"}
    ]
  }
}
```

Docara не требует одинакового количества пунктов и не переводит подписи
автоматически.

## 3. Контролируйте актуальность переводов

`translation_tracking` не меняет routing, fallback или `missing_page_policy`.
Он сравнивает исходный Markdown и `lang.json` с базовым русским языком и
формирует отчёт, не останавливая сборку:

```bash
php vendor/bin/docara translations status
php vendor/bin/docara translations status --locale=en
php vendor/bin/docara translations status --status=stale --json
```

Страницы с одинаковым относительным путём связываются автоматически. Если пути
различаются, задайте обеим страницам одинаковый `translation_key`. Docara не
связывает страницы по похожим заголовкам. Намеренное исключение хранится в
`translations.lock.json` вместе с обязательной причиной.

После проверки перевода сначала создайте hash-bound план, затем примените
именно его идентификатор:

```bash
php vendor/bin/docara translations accept \
  --locale=en --key=guide/install.md --review=ai_verified --dry-run --json
php vendor/bin/docara translations accept --apply=<plan-sha256> --json
```

Проверенный набор принимается одним планом. Повторите `--key`, укажите
раздел префиксом ключа или примите результат отфильтрованного отчёта:

```bash
# два ключа
php vendor/bin/docara translations accept --locale=en \
  --key=guide/install.md --key=guide/update.md --review=human_reviewed --dry-run --json
# весь раздел guide/
php vendor/bin/docara translations accept --locale=en --prefix=guide/ --dry-run --json
# всё, что показывает translations status --locale=en --status=unverified
php vendor/bin/docara translations accept --locale=en --status=unverified --dry-run --json
# строки lang.json
php vendor/bin/docara translations accept --locale=en --kind=lang --status=stale --dry-run --json
```

В план набора попадают только `unverified` и `stale` пункты выборки, они
перечислены в `accepted`. Пункты в других статусах (`orphan`, `missing`,
`structure_mismatch` и т.д.) не меняются и перечислены в `skipped`. План
привязан к hashes всех прочитанных файлов и применяется той же командой
`--apply=<plan-sha256>`. Исключение (`--exclude-reason`) по-прежнему задаётся
для одного ключа.

Если Markdown, `lang.json`, конфигурация или lock-файл изменились между
командами, применение остановится. `ai_verified` означает технически и
содержательно проверенный ИИ перевод; `human_reviewed` выставляется после
отдельной редакционной проверки. Сборщик не вызывает ИИ, сеть и не изменяет
исходники.

## Строгий режим publish-current

В режиме `report` сборка публикует все переводы как есть. Чтобы читатель не
видел устаревший или непроверенный перевод, включите строгий режим:

```json
{
  "translation_tracking": {
    "enabled": true,
    "source_locale": "ru",
    "mode": "publish-current",
    "lock_file": "translations.lock.json"
  }
}
```

Правило для каждой страницы целевой локали:

| Статус перевода | Что публикуется по адресу перевода |
|---|---|
| `current`, `excluded` | перевод как есть |
| `stale`, `unverified`, `structure_mismatch` | исходная страница с уведомлением на языке перевода |
| `orphan` | ничего: у страницы нет источника |
| `missing` | как раньше, по `locales.missing_page_policy` |
| `duplicate_key` | сборка останавливается: неясно, какую страницу публиковать |

Почему исходная страница, а не пропуск: адрес перевода уже может стоять в
ссылках, закладках и в переключателе языка, а исходный текст точнее
устаревшего перевода. Отсутствующая страница (`missing`) так и остаётся
отсутствующей: правило `missing_page_policy` не меняется.

Страница с исходным текстом:

- собирается в меню, теме и интерфейсе целевой локали (`lang="en"`), а
  содержимое и заголовок берёт из исходной страницы;
- помечает исходный текст языком оригинала: `<article>` и пункты оглавления
  страницы получают `lang` исходной локали (и `dir`, если направление письма
  другое), поэтому экранный диктор читает их голосом этого языка;
  уведомление и интерфейс остаются на языке целевой локали;
- начинается с уведомления со ссылкой на оригинал (текст — ниже);
- получает `<meta name="robots" content="noindex">` и `canonical` на исходную
  страницу, не участвует в `hreflang` (ни своём, ни исходной страницы), в
  поиске сайта и в `sitemap.xml`.

Текст уведомления по умолчанию — «This page is being translated; the original
is shown (Русский).» для английского и аналогичный для русского. Его можно
задать в `content/<locale>/lang.json` ключом `language.fallback_notice`, где
`{source}` заменяется ссылкой на исходную страницу:

```json
{
  "schema": "docara.lang.v1",
  "version": 1,
  "language": {
    "fallback_notice": "This page is being translated; showing the Russian original ({source})."
  }
}
```

Строки интерфейса из `lang.json` в статусе `stale`, `unverified` или
`structure_mismatch` заменяются строкой исходной локали. Отсутствующая строка
по-прежнему берётся по правилам локали.

Сборка выводит, что было задержано:

```text
Translations held back (mode publish-current): 2 page(s) show the source text, 1 page(s) withheld, 1 string(s) show the source text.
- source_fallback  stale        guide/install.md [en] content/en/guide/install.md
- source_fallback  unverified   components.md [en] content/en/components.md
- withheld         orphan       legacy.md [en] content/en/legacy.md
- source_string    lang:search.placeholder [en]
```

Тот же список хранится в квитанции
`build_<environment>.docara/translation-publication.json`. Режим `report`
работает в точности как раньше.

## 4. Соберите и проверьте все языки

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --host=127.0.0.1 --port=8000 --no-build
```

Проверьте redirect `/`, затем `/ru/`, `/en/` и `/ar/`. На связанных страницах
должны работать переключатель языка в виде кнопки со значком, меню локалей и
`hreflang`; у арабской страницы корневой HTML должен
иметь `lang="ar"` и `dir="rtl"`. Поиск хранит locale каждой записи и не
смешивает одинаковые маршруты разных языков.

## Когда перевода нет

При `missing_page_policy: "skip"`, если `content/ar/guide/install.md`
отсутствует, арабская страница установки не создаётся и переключатель не ведёт
на выдуманный URL. Для строгой симметрии выберите `"error"`: сборка остановится
с точным ожидаемым путём. Не копируйте русский Markdown как скрытый fallback.
Если UI-подпись реально берётся из `fallbacks`, translation report показывает
локаль fallback отдельно и всё равно считает собственный перевод отсутствующим.

## Как устроены языки

### Разделение данных

- `docara.json`, `section.json`, `*.page.json` и каталог компонентов хранят
  структуру, ID, параметры и связи;
- Markdown хранит переводимый контент каждой локали;
- `content/<locale>/lang.json` хранит только повторяющиеся подписи интерфейса;
  текст страниц и параметры компонентов туда не переносятся;
- PHP, JavaScript и шаблоны обращаются к семантическим message ID и не проверяют
  конкретные значения `ru` или `en`.

Поэтому добавление языка не требует новой ветки генератора или копирования
PHP-кода.

### Реестр локалей и адреса

```json
{
  "default_locale": "ru",
  "locales": {
    "missing_page_policy": "skip",
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "public_prefix": "ru",
      "fallbacks": []
    },
    "en": {
      "label": "English",
      "direction": "ltr",
      "content_root": "content/en",
      "public_prefix": "en",
      "fallbacks": []
    },
    "ar": {
      "label": "العربية",
      "direction": "rtl",
      "content_root": "content/ar",
      "public_prefix": "ar",
      "fallbacks": ["en"]
    },
    "fr-CA": {
      "label": "Français (Canada)",
      "direction": "ltr",
      "content_root": "content/fr-CA",
      "public_prefix": "fr-ca",
      "fallbacks": ["en"]
    }
  },
  "locale_routing": {
    "strategy": "prefixed",
    "root": "redirect",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": true
  }
}
```

Ключ локали — канонический BCP 47 tag. `public_prefix` отвечает только за
адрес и не обязан повторять tag. `content_root` разных локалей не должны
пересекаться. Реестр `locales`, `default_locale` и `locale_routing` задаются
только в `docara.json`.

`locale_routing.strategy: "prefixed"` включает симметричные адреса: префикс
обязателен у каждой локали, включая основную, — русский публикуется в `/ru/`,
английский в `/en/`. `root: "redirect"` создаёт статическую страницу `/`,
ведущую на `default_locale`. Автоматический выбор языка браузера намеренно
выключен: один адрес имеет одну стабильную языковую идентичность. При
`legacy_unprefixed_redirects: true` сборка создаёт перенаправление для каждого
прежнего адреса без префикса и записывает их в
`build_<environment>.docara/locale-routes.json`. Перенаправления адресов самого
проекта задаются отдельно, в [`redirects.json`](/ru/authoring/redirects/).

Существующие проекты можно не переводить на префиксы сразу. Режим
`strategy: "default_unprefixed"` оставляет пустой префикс основной локали и
публикует её в корне, как прежняя Docara; его же использует заготовка
`minimal`. Оба режима проходят одну проверку, но смешивать их правила в одной
конфигурации нельзя.

`locales.missing_page_policy: "skip"` публикует только существующие
страницы. Значение `"error"` требует одинаковый набор адресов у всех локалей и
останавливает сборку с `LOCALE_PAGE_MISSING` и ожидаемым путём файла. Ни один
режим не копирует текст страницы из другой локали.

### Подписи интерфейса и fallback

Общие подписи каждой локали находятся в `content/<locale>/lang.json`. Для части
подписей Docara поставляет значения по умолчанию: кнопки копирования и переноса
строк в коде, подписи адаптивного просмотра примеров, размер шрифта и ширина
страницы, заголовок «Настройки» и кнопка «Сбросить настройки» в панели
настроек, кнопки «Режим чтения» и «Скрыть содержание страницы», кнопка паузы
фоновой анимации hero (`effects.pause`, `effects.play`). Встроенные значения
есть на английском и русском. Язык выбирается по основной части тега локали:
`ru` и `ru-RU` получают русские подписи, локали без собственного набора —
английские.

Ключ из `lang.json` всегда важнее встроенного значения, поэтому любую подпись
можно переопределить в проекте. Основные подписи шапки, поиска, навигации и
панели настроек обязательны: локаль без собственного файла останавливает
сборку, а не берёт подписи молча из другого языка. Файл может быть неполным:
отсутствующую строку Docara ищет по явно объявленной цепочке `fallbacks`.
Fallback никогда не выбирается по догадке из названия языка и никогда не
подменяет Markdown-страницу. Все ключи файла перечислены в справочнике
[«Подписи интерфейса lang.json»](/ru/reference/lang-json/).

### Результат сборки

Каждая локаль получает изолированное дерево адресов, свой `lang`, `dir`,
навигацию и локализованный каталог компонентов. Связанные страницы получают
`hreflang` и переключатель языка. Каждая содержательная страница получает
self-canonical, а `hreflang="x-default"` указывает на стабильный корневой
redirect. Для RTL-языка нужно явно указать `"direction": "rtl"`: Docara добавит
`dir="rtl"` в корневой HTML-элемент и не определяет направление по списку
известных языков.

Если для страницы нет соответствующей страницы в другом дереве, переключатель
не придумывает перевод: связь строится только между одинаковыми
относительными путями контента или одинаковым `translation_key`.

### Статусы переводов

`translation_tracking` — независимый отчётный контур. Он не меняет fallback,
адреса или `missing_page_policy`, а показывает статусы `current`, `stale`,
`missing`, `unverified`, `orphan`, `duplicate_key`, `structure_mismatch` и
`excluded`. Режим `report` (по умолчанию) только сообщает статусы, режим
`publish-current` описан [выше](#строгий-режим-publish-current).

## Как добавить ещё один язык

1. Добавьте локаль в `locales`.
2. Создайте её `content_root` и дерево Markdown с теми же относительными путями.
3. Создайте `content/<locale>/lang.json` с общими подписями интерфейса.
4. Настройте явные `fallbacks`, если файл неполный.
5. Запустите `build` и `verify-static`.

## См. также

- [Подписи интерфейса lang.json](/ru/reference/lang-json/) — все ключи файла
- [Перенаправления](/ru/authoring/redirects/)
- [Меню и разделы](/ru/authoring/navigation/) — верхнее меню каждой локали

