# Migrating an existing site

Docara does not convert a site made by another generator automatically.
Create a new folder, move the supported content and switch publication only
after you compare the results.

If the project is already built with Docara 2.x, you do not need a new
folder: follow [Moving from 2.x to 3.0](/en/build/update/#moving-from-2-x-to-3-0).

## A safe order

1. Record the source revision, a working build and the list of public URLs.
2. Create a new project with `docara init` in an empty folder.
3. Move the Markdown and the project assets; express the settings through
   `docara.json`, `section.json` and `<page>.page.json`.
4. Write the old public URLs into `redirects.json`.
5. Run `build production` and `verify-static build_production`.
6. Compare the content, routes, search, themes and responsive layout over
   HTTP.
7. Prepare a backup of the published folder and a tested rollback.

You do not need to move old code, templates or a frontend build: the
interface of the new site is made of registered components and SIMAI
Framework.

:::ui.alert
{"type":"info","variant":"default","title":"Migration is always explicit","supporting-text":"The init command creates a new project; it is not a converter for an existing folder.","closable":false,"aria-label":"Migration happens in a new folder"}

:::
