---
description: Все поля section.json и page.json с описанием, значением по умолчанию, типом и правилом проверки, общие поля оформления и ключи метаданных страницы.
tags: [section-json, page-json, front-matter, настройки, поля]
---

# section.json и page.json

`section.json` настраивает раздел: соседнюю страницу-обзор и все страницы
каталога. `<page>.page.json` уточняет одну страницу и дополнительно задаёт её
`description` и `slug`. Как уровни наследуются и сбрасываются, описано в
разделе [«Сайт, раздел и страница»](/ru/settings/levels-and-inheritance/).

## Как читать таблицы полей

Таблицы строятся при сборке из JSON Schema. Если поле не задано в файле,
действует значение уровнем выше: из ближайшего `section.json` родительского
каталога или из `docara.json`. Строка «По умолчанию» под описанием показывает
встроенное значение Docara, если поле не задано нигде. В «Технических данных» —
тип, правило проверки и место в схеме.

## section.json

### Основное

:::schema_reference {schema=section scope=section fields=schema,version,title,locale,preset}
:::

### Внешний вид

:::schema_reference {schema=section scope=section fields=branding,settings,layout}
:::

### Меню, чтение и поиск

:::schema_reference {schema=section scope=section fields=navigation,header_navigation,reading,search}
:::

### Примеры и код

:::schema_reference {schema=section scope=section fields=examples,code}
:::

## page.json

### Основное

:::schema_reference {schema=page scope=page fields=schema,version,title,description,slug,locale,preset}
:::

### Внешний вид

:::schema_reference {schema=page scope=page fields=branding,settings,layout}
:::

### Меню, чтение и поиск

:::schema_reference {schema=page scope=page fields=navigation,header_navigation,reading,search}
:::

### Примеры и код

:::schema_reference {schema=page scope=page fields=examples,code}
:::

## Общие поля оформления

Эти ветки одинаковы на всех трёх уровнях. Таблица строится из
`presentation.schema.json`.

:::schema_reference {schema=presentation scope=shared}
:::

## Метаданные Markdown

Блок метаданных (front matter) в начале Markdown-страницы принимает только
ключи `title`, `description`, `tags`, `draft`, `noindex`, `translation_key`,
`profile` и `composition_recipe`. Что делает каждый ключ, описано в разделе
[«Markdown»](/ru/authoring/markdown/#метаданные-страницы).
