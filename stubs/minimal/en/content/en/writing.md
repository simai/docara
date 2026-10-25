# Writing pages

Pages are plain Markdown. The first heading is the page title; the second- and
third-level headings form the outline on the right.

## Text and lists

Use **bold**, *italic*, `inline code` and [links](../).

1. Numbered lists keep their order.
2. Nested lists work too:
   - a bullet;
   - another bullet.

> A quotation stands out from the text around it.

## Code

```php
<?php

echo 'Hello from Docara';
```

## Tables

| Command | What it does |
|---|---|
| `build` | Builds the site into `build_<environment>/` |
| `verify-static` | Checks the built folder |
| `serve` | Serves the built folder over HTTP |

## A live example

An `:::example` block shows the rendered result next to its exact source.

:::example {label="Example"}
```markdown
:badge[Ready]{variant=main scheme=success size=1/2}
```
:::
