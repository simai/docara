---
description: "Local preview with the serve command: how to build and open the site on your own computer and what to check in the browser."
---

# Local preview

The `serve` command first builds the environment you name, then starts the
built-in PHP server.

```bash
php vendor/bin/docara serve local --host=localhost --port=8000
```

If the result is already built, add `--no-build`:

```bash
php vendor/bin/docara serve local --host=localhost --port=8000 --no-build
```

For ServBay or another local web server, point the document root at the
contents of `build_local` or `build_production`. Do not point a server at the
Markdown and JSON sources.

Once it runs, check the home page, a nested route, the light and dark themes,
Network and Console.

On a page with several H2/H3 headings also check that:

- on a desktop the page outline stays at the right, and the breadcrumbs and the
  previous/next links lead where you expect;
- below a 1152 px viewport the outline is available through the native details
  block above the article;
- below a 704 px viewport the previous/next links stack into one column with no
  horizontal overflow;
- Tab shows a visible focus ring, Enter opens links, and following a
  `#fragment` brings the right heading into view;
- the light and dark themes keep the contrast of all three navigation surfaces.

## See also

- [Checking the build](/en/build/verify/)
- [Building the site](/en/build/php-only/)
- [The serve command](/en/reference/cli/)
