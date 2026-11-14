# Troubleshooting

Start from the symptom, fix the source Markdown or JSON and build again. Do
not edit files inside `build_local` or `build_production`: the next build
replaces them.

If a build or a command printed a code in square brackets, for example
`[FRONT_MATTER_KEY_UNKNOWN]`, look it up in the
[error code reference](/reference/errors/): it gives the cause and the fix.

## Composer cannot find version 3.x

The message `Root composer.json requires simai/docara ^3.0, found
simai/docara[... v2.18.2] but it does not match the constraint` means that
Composer sees only the 2.x line. Docara 3.x is published on Packagist, so the
usual cause is a stale Composer metadata cache or a `repositories` entry in
`composer.json` that points to the former repository. Remove that entry,
clear the cache and install again:

```bash
composer clear-cache
composer require simai/docara:^3.0
```

## Composer requires a PHP extension

A message like `simai/docara requires ext-intl * -> it is missing from your
system` means that PHP lacks an extension. Docara uses `ctype`, `dom`,
`intl`, `json`, `libxml` and `mbstring`. Install the extension for the PHP
version that `php -v` runs, and check it with `php -m`.

## `init` refuses to create the project

`Initialization requires an empty target directory` means that the folder
already has files. `init` accepts an empty folder, a folder with only
`composer.json`, `composer.lock` and `vendor/` after `composer require`, and
the service files `.git/`, `.gitignore`, `.gitattributes` and `.DS_Store`.
Create the project in a new folder or move the other files away.

`Detected an existing Docara project` means that the project already exists.
To update its service files, use `docara update`; to move to a new Docara
version, use `docara upgrade` (see [Updating Docara](/build/update/)).

## The build cannot find Node.js

The code `COMPOSITION_RECIPE_RUNTIME_REQUIRED` means that the `node` command
was not found. The build runs the SIMAI Framework page composition code in
Node.js 20 or newer. Install Node.js or give its path:

```bash
export DOCARA_NODE_BINARY=/path/to/node
php vendor/bin/docara build production
```

The finished site does not need Node.js: only the build machine does.

## The build stopped on a JSON check

In the error message, find the file name, the JSON Pointer and the code
`SCHEMA_VALIDATION_FAILED`.

1. Check the commas, quotes and the type of the value.
2. Compare the field with the [schema reference](/reference/schemas/).
3. Remove unknown fields instead of keeping them "for later".
4. Run `php vendor/bin/docara build local` again.

If you changed section settings, check the nearest `section.json`; if you
changed the settings of one page, check the `<page>.page.json` beside it.

The code `SECTION_DESCRIPTOR_LEGACY_NAME` means that the old name
`_section.json` was found. Rename the file named in the error to
`section.json`. Do not keep both: only `section.json` is canonical. Other
file check codes are in [Common codes](/reference/errors/#common-codes).

## The page opens without styles

Do not open the HTML through `file://`. Start the HTTP preview:

```bash
php vendor/bin/docara serve local --host=localhost --port=8000
```

In Network, the exact-commit Core CSS/JS and the local `_docara/framework`
files must load successfully. If you set a document root in ServBay or
another server, it must point to `build_local` or `build_production`, not to
the source folder of the project.

## An old version shows after a change

`--no-build` deliberately shows the result that already exists. Stop the
preview with `Ctrl+C` and run `serve` without this flag:

```bash
php vendor/bin/docara serve local --host=localhost --port=8000
```

If Docara asks you to confirm clearing the destination and you decline, the
preview does not start: this guards against silently showing an old build.

## A nested route or a component returns 404

For local preview use `docara serve`: its built-in read-only router
understands pretty URLs and identifiers with a dot, for example
`/components/alert/`.

On an external server, turn on the usual resolution of a folder to
`index.html`. Do not rewrite URLs to PHP and do not route requests to the
Markdown sources.

## A component is rejected

Open the [component reference](/components/) and find the exact identifier:

- a `supported` entry has a working call and a live example;
- an unavailable entry shows the reason, a safe replacement and the condition
  for admission;
- remove an unknown prop;
- `FRAMEWORK_PROP_MANAGED` means that Docara computes the field;
- do not work around `FRAMEWORK_COMPONENT_UNSUPPORTED` with a similar custom
  element.

All codes for components and their parameters are in
[Components and their parameters](/reference/errors/content/#components-and-their-parameters).

The exact syntax of supported components is on their generated pages, not on
old hand-written routes.

## Search does not find a page

Check the resulting inheritance:

```json
{
  "search": {
    "enabled": true,
    "indexed": true
  }
}
```

Always rebuild the site after a change. `search.enabled` creates the shared
search surface, and `search.indexed: false` excludes one page from the index.

## `verify-static` reports a broken link

Fix the link in the source Markdown and run the production build again:

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

In the content use the resulting route `/section/page/`, not the path
`page.md`. For a link with `#fragment`, check the actual `id` of the heading
after the build. What each `@...` mark in the report means is described on
[Build check](/reference/errors/verify-static/).

## The theme looks mixed

Do not add your own `theme-light` or `theme-dark` classes in Markdown. Choose
the theme in the reading settings, reset the reader's choice and check the
`system` mode. The interface must use SIMAI Framework tokens, not hard-coded
colours.

## The preview port is busy

Stop the earlier process with `Ctrl+C` or choose another port:

```bash
php vendor/bin/docara serve local --host=localhost --port=8010
```

To move an existing site, use the separate
[migration order](/migration/).
