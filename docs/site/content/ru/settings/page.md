---
description: Настройки страницы в front matter и page.json: title, description, slug, locale и разрешённые presentation overrides. Текст остаётся в Markdown.
---

# Настройки страницы

Page level задаёт title/description/slug/locale и разрешённые presentation overrides. Основной prose остаётся в `<route>.md`; sidecar или front matter не становится вторым источником текста страницы.


## Как читать таблицы полей

Если поле не задано здесь, действует значение уровнем выше: из ближайшего
`section.json` или из `docara.json`.
Строка «По умолчанию» под описанием показывает встроенное значение Docara,
если поле не задано нигде. В «Технических данных» — тип, правило проверки и
место в схеме.

## Основное

:::schema_reference {schema=page scope=page fields=schema,version,title,description,slug,locale,preset}
:::

## Внешний вид

:::schema_reference {schema=page scope=page fields=branding,settings,layout}
:::

## Меню, чтение и поиск

:::schema_reference {schema=page scope=page fields=navigation,header_navigation,reading,search}
:::

## Примеры и код

:::schema_reference {schema=page scope=page fields=examples,code}
:::

## Front matter

Front matter — блок между строками `---` в начале страницы. Он принимает
только эти ключи; любой другой останавливает сборку с
[`FRONT_MATTER_KEY_UNKNOWN`](/reference/errors/content/#заголовок-страницы-front-matter):

- `title` и `description` — заголовок и описание страницы;
- `tags` — список в одну строку, например `tags: [деплой, публикация]`; по меткам страницу находит [поиск](/ru/settings/search/#синонимы-метки-страницы);
- `draft` — `true` или `false`; черновик нельзя собрать как public `--page`;
- `noindex` — `true` или `false`; `true` добавляет `robots: noindex` и убирает
  страницу из `sitemap.xml` и `llms.txt`. Поиск по сайту настраивается
  отдельно: `"search": {"indexed": false}` в `<route>.page.json`;
- `translation_key` — стабильный ключ, который связывает переводы одной
  страницы;
- `profile` — встроенный профиль проверки `validate`: `landing`, `article`,
  `tutorial`, `how_to`, `reference` или `explanation`;
- `composition_recipe` — путь к JSON-дескриптору Composition Recipe
  относительно проекта; пример — страница
  [Composition Recipe: расширенная шапка](/ru/examples/composition-recipe-expanded/).

Route rename требует full build и явный redirect со старого URL.
