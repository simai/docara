---
description: Все поля docara.json с описанием, значением по умолчанию, типом и правилом проверки. Таблицы строятся при сборке из JSON Schema.
tags: [docara-json, настройки, поля, схема]
---

# docara.json

`docara.json` — файл настроек всего сайта. Только здесь задаются корень
контента, базовый и публичный адрес, реестр языков и правила адресов, версия
документации, файл перенаправлений, панель читателя, Framework lock и
пространство имён Smart-компонентов проекта. Поля оформления, меню и чтения,
заданные здесь, служат значениями по умолчанию для всех страниц.

## Как читать таблицы полей

Таблицы ниже строятся при сборке из JSON Schema и кода Docara, поэтому в них
есть каждое поле `docara.json`. Строка «По умолчанию» под описанием показывает
значение, которое Docara использует, если поле не задано. Если такой строки
нет, значения по умолчанию нет: поле обязательное или без него функция
выключена. В «Технических данных» указаны тип, правило проверки и место в
схеме.

Поля оформления, меню и чтения также можно задать в `section.json` и
`<page>.page.json`: см. [section.json и page.json](/ru/reference/section-and-page/).
Как работают уровни и наследование, описано в разделе
[«Сайт, раздел и страница»](/ru/settings/levels-and-inheritance/).

## Основное

:::schema_reference {schema=site scope=site fields=schema,version,title,preset,framework_lock,content_root,documentation_version}
:::

## Адрес и публикация

Подробнее — в разделе [«Адрес сайта и поисковые системы»](/ru/settings/site-address/).

:::schema_reference {schema=site scope=site fields=base_url,site_url,discovery,redirects_file,not_found,page_transforms,portal}
:::

## Языки и переводы

Подробнее — в разделе [Локали и маршрутизация](/ru/authoring/multilingual-site/).

:::schema_reference {schema=site scope=site fields=locale,default_locale,locales,locale_routing,translation_tracking}
:::

## Внешний вид

Подробнее — в разделах [«Бренд и тема»](/ru/settings/branding-and-theme/) и
[«Области макета»](/ru/settings/layout-and-regions/).

:::schema_reference {schema=site scope=site fields=theme,branding,settings,layout,effects}
:::

## Меню, чтение и поиск

Подробнее — в разделах [«Меню и разделы»](/ru/authoring/navigation/),
[«Контекст чтения»](/ru/settings/reading/), [«Поиск»](/ru/settings/search/) и
[«Панель читателя»](/ru/settings/reader-preferences/).

:::schema_reference {schema=site scope=site fields=navigation,header_navigation,reading,search,reader_preferences}
:::

## Примеры, код и расширения

:::schema_reference {schema=site scope=site fields=examples,code,documentation_tracking,smart}
:::
