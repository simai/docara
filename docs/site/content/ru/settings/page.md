---
description: Настройки страницы в front matter и page.json: title, description, slug, locale и разрешённые presentation overrides. Текст остаётся в Markdown.
---

# Настройки страницы

Page level задаёт title/description/slug/locale и разрешённые presentation overrides. Основной prose остаётся в `<route>.md`; sidecar или front matter не становится вторым источником текста страницы.

:::schema_reference {schema=page scope=page}
:::

Front matter принимает только эти ключи; любой другой останавливает сборку с
`FRONT_MATTER_KEY_UNKNOWN`:

- `title` и `description` — заголовок и описание страницы;
- `tags` — список в одну строку, например `tags: [ui, status]`;
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
