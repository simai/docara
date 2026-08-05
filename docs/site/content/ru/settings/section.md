# Настройки раздела

`section.json` действует на соседний route и descendants. Он может переопределять preset, title, locale и presentation families, но не владеет Framework lock, content root, base URL, locale registry, documentation version, redirects или project namespace.

:::schema_reference {schema=section scope=section}
:::

После изменения section inheritance выполните full build: navigation, search, previous/next и несколько descendants могут измениться одновременно.

