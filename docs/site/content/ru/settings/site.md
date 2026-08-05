# Настройки сайта

`docara.json` владеет site-only keys: content root, base URL, locale registry/routing, documentation version, redirects file, reader preferences, Framework lock и project Smart namespace. Presentation families можно определить здесь как defaults для всех страниц.

## Exhaustive schema reference

:::schema_reference {schema=site scope=site}
:::

`Default: не объявлен` означает, что JSON Schema не приписывает скрытое значение: effective value приходит из принятого runtime/preset/inheritance и проверяется через inspect/receipt. Это честнее, чем дублировать runtime default в prose.

