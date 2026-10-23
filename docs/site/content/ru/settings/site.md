---
description: Что задаёт docara.json: корень контента, base_url и site_url, локали и маршрутизация, редиректы, настройки чтения, Framework lock и значения по умолчанию.
---

# Настройки сайта

`docara.json` владеет site-only keys: content root, base URL, locale registry/routing, documentation version, redirects file, reader preferences, Framework lock и project Smart namespace. Presentation families можно определить здесь как defaults для всех страниц.

## Публичный адрес сайта: `site_url`

`site_url` — абсолютный публичный адрес сайта: origin и `base_url` вместе.
Путь в `site_url` обязан совпадать с `base_url`, иначе сборка остановится с
`SITE_URL_BASE_MISMATCH`.

```json
{
  "base_url": "/docara/",
  "site_url": "https://simai.github.io/docara/",
  "discovery": {
    "description": "Документация Docara.",
    "llms_links": [
      {"title": "AI manifest", "path": "ai.json", "description": "машиночитаемый индекс документации"}
    ]
  },
  "branding": {
    "share_image": "assets/share.png"
  }
}
```

Когда `site_url` задан, сборка дополнительно:

- пишет в корень сайта (под `base_url`) `sitemap.xml`, `robots.txt` и
  `llms.txt`;
- делает абсолютными `canonical` и `hreflang` каждой страницы;
- добавляет Open Graph и Twitter card: `og:title`, `og:description`,
  `og:url`, `og:type` (`website` для главной страницы локали, `article` для
  остальных), `og:locale` и `og:locale:alternate` для переводов,
  `og:site_name`, `twitter:card`, а при заданном `branding.share_image` —
  `og:image` и `twitter:image`.

**`sitemap.xml`** перечисляет каждую опубликованную индексируемую страницу
абсолютным адресом. У переведённых страниц есть `xhtml:link hreflang` на все
переводы. В карту не попадают черновики (`draft: true`), страницы с
`noindex: true` во front matter, redirect-страницы, страница `404.html`,
другие страницы ошибок (последний сегмент маршрута — код 4xx или 5xx, например
`404/`) и страницы, которые в режиме `publish-current` показывают исходный
текст вместо перевода.

**`robots.txt`** разрешает обход всего сайта и называет `sitemap.xml`.
Поисковые роботы читают `robots.txt` только в корне домена: у сайта в
подкаталоге, например на GitHub Pages проекта, файл полезен, если корень
домена отдаёт его же или ссылается на sitemap.

**`llms.txt`** следует соглашению [llmstxt.org](https://llmstxt.org/):
заголовок — название сайта, затем описание (`discovery.description`, иначе
description главной страницы основной локали), разделы верхнего уровня меню
основной локали со ссылками и описаниями, главные страницы других языков и
машиночитаемые файлы: ссылки из `discovery.llms_links`, `sitemap.xml` и
`_docara/page-metadata.json`. Путь в `llms_links` задаётся от `base_url`;
файл может появиться и после сборки (например, `ai.json`, который пишет
отдельный скрипт), но к запуску `verify-static` он должен существовать.

`branding.share_image` — PNG, JPEG или WebP в проекте; его можно
переопределить для раздела или страницы в `section.json` и `*.page.json`.
Без `site_url` картинка не публикуется.

Без `site_url` вывод сборки остаётся прежним: эти файлы и теги не
создаются. Если контент уже публикует собственный `robots.txt`,
`sitemap.xml` или `llms.txt` в корне, сборка с `site_url` остановится с
`DISCOVERY_FILE_COLLISION`.

`verify-static` при заданном `site_url` проверяет, что все три файла есть и
корректны: `sitemap.xml` — правильный urlset, каждый адрес лежит под
`site_url` и ведёт на собранную индексируемую страницу, каждая такая страница
есть в карте, `hreflang` указывает на страницы своей локали; `robots.txt`
совпадает с ожидаемым; каждая ссылка `llms.txt` лежит под `site_url` и ведёт на
файл сборки.

Ключ `effects` задаёт каталог фоновых эффектов SIMAI Framework для
параметра `effect` первого экрана. См. [Первый экран](/components/hero/).

Сайт, который собирается для одного пути портала, задаёт рядом с `site_url`
секцию `portal`: тогда сборка пишет только `llms.txt`, а `sitemap.xml` и
`robots.txt` оставляет порталу. См. [Сборка для портала](/build/portal/).

В CI `site_url` удобно задавать тем же шагом, что и `base_url`. Для GitHub
Pages оба значения даёт `actions/configure-pages`:

```bash
jq --arg base "${BASE_PATH%/}/" --arg site "${SITE_URL%/}/" \
  '.base_url = $base | .site_url = $site' docara.json > docara.json.pages
mv docara.json.pages docara.json
```

где `BASE_PATH` — `steps.pages.outputs.base_path`, а `SITE_URL` —
`steps.pages.outputs.base_url`. Так собирается
<https://simai.github.io/docara/>.

## Заголовок вкладки

`<title>` страницы — «Заголовок страницы — Название сайта». Если заголовок
страницы совпадает с названием сайта (обычно на главной), название не
повторяется: `<title>Docara</title>`, а не «Docara — Docara».

## Как читать таблицы полей

Таблицы ниже строятся при сборке из JSON Schema и кода Docara, поэтому в них
есть каждое поле `docara.json`. Строка «По умолчанию» под описанием показывает
значение, которое Docara использует, если поле не задано. Если такой строки
нет, значения по умолчанию нет: поле обязательное или без него функция
выключена. В «Технических данных» указаны тип, правило проверки и место в
схеме.

Поля оформления, меню и чтения также можно задать в `section.json` и
`<page>.page.json`: см. [настройки раздела](/settings/section/) и
[страницы](/settings/page/).

## Основное

:::schema_reference {schema=site scope=site fields=schema,version,title,preset,framework_lock,content_root,documentation_version}
:::

## Адрес и публикация

:::schema_reference {schema=site scope=site fields=base_url,site_url,discovery,redirects_file,not_found,page_transforms,portal}
:::

## Языки и переводы

Подробнее — в разделе [Локали и маршрутизация](/ru/authoring/multilingual-site/).

:::schema_reference {schema=site scope=site fields=locale,default_locale,locales,locale_routing,translation_tracking}
:::

## Внешний вид

Подробнее — в разделе [Бренд и тема](/settings/branding-and-theme/).

:::schema_reference {schema=site scope=site fields=theme,branding,settings,layout,effects}
:::

## Меню, чтение и поиск

:::schema_reference {schema=site scope=site fields=navigation,header_navigation,reading,search,reader_preferences}
:::

## Примеры, код и расширения

:::schema_reference {schema=site scope=site fields=examples,code,documentation_tracking,smart}
:::
