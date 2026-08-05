# Настройки страницы

Page level задаёт title/description/slug/locale и разрешённые presentation overrides. Основной prose остаётся в `<route>.md`; sidecar или front matter не становится вторым источником текста страницы.

:::schema_reference {schema=page scope=page}
:::

Минимальный front matter contract: `title`, `description`, `tags`, `draft`, `translation_key`. Draft нельзя собрать как public `--page`. Route rename требует full build и явный redirect со старого URL.

