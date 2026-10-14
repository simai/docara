# My documentation

This site was created by `docara init`. Every page is a Markdown file in
`content/en/`, and the site settings live in `docara.json`.

:::ui.alert
{"type":"info","variant":"default","title":"Start here","supporting-text":"Edit this file, content/en/index.md, then run the build again."}
:::

## What is in this starter

- [Writing pages](writing/): Markdown, code blocks, tables and a live example.
- [Components](components/): ready-made SIMAI Framework components.

The menu, the search index, the page outline and the previous/next links are
built from these files. Add a Markdown file to `content/en/` and it appears in
the menu after the next build.

## Build and check

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara serve production --no-build
```

The finished site is the `build_production/` folder. Copy it to any static
host.
