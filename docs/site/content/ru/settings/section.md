---
description: section.json настраивает раздел и вложенные страницы: preset, title, locale и presentation. Какие ключи остаются только на уровне сайта.
---

# Настройки раздела

`section.json` действует на соседний route и descendants. Он может переопределять preset, title, locale и presentation families, но не владеет Framework lock, content root, base URL, locale registry, documentation version, redirects или project namespace.


## Как читать таблицы полей

Если поле не задано здесь, действует значение уровнем выше: из
`section.json` родительского каталога или из `docara.json`. Столбец «По умолчанию» показывает,
откуда берётся значение, и встроенное значение Docara, если поле не задано
нигде. В «Технических данных» под описанием — тип, правило проверки и место
в схеме.

## Основное

:::schema_reference {schema=section scope=section fields=schema,version,title,locale,preset}
:::

## Внешний вид

:::schema_reference {schema=section scope=section fields=branding,settings,layout}
:::

## Меню, чтение и поиск

:::schema_reference {schema=section scope=section fields=navigation,header_navigation,reading,search}
:::

## Примеры и код

:::schema_reference {schema=section scope=section fields=examples,code}
:::

## После изменения

После изменения section inheritance выполните full build: navigation, search, previous/next и несколько descendants могут измениться одновременно.
