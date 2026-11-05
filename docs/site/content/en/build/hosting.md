---
description: How to host build_production on nginx, Apache, GitHub Pages, Netlify or in S3. The server needs no PHP, Composer or Node.js — only the build machine does.
tags: [deploy, hosting, host]
---

# Hosting

The result of `build production` is an ordinary set of static files. Any
static host serves the `build_production/` folder as it is: nginx, Apache,
GitHub Pages, Netlify, S3-compatible storage with web access and others. The
server needs no PHP, Composer or Node.js; they are needed only where the
site is built.

## Before you upload

Build and check exactly the folder you are going to upload:

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

`verify-static` runs no project PHP code and checks the static folder: the
pages from the manifest and the physical HTML files, local links and
`#fragment`, unique HTML `id`s, assets, the search index, resolved page
plans, redirect pages, the component catalogue, the SIMAI Framework
projection and the build receipts. If the exit code is not zero, do not
upload the folder. The details are in
[Checking the static result](/en/build/verify/).

Upload the contents of `build_production/` as a whole and unchanged. The
build receipts sit next to it, in `build_production.docara/`, and are not
uploaded to the host: the visitor does not need them. Do not open the pages
through `file://`: pretty routes, `base_url`, search and asset loading do not
work that way. For a local look use `serve production --no-build`.

## Routes

Every page is a folder with an `index.html`: the route `/guides/install/`
matches the file `guides/install/index.html`. The host must serve
`index.html` for a folder request. Most static hosts do this by default.

nginx:

```nginx
server {
    root /var/www/docs/current;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

Apache (`.htaccess` or the virtual host configuration):

```apache
DirectoryIndex index.html
```

## A site in a subfolder

If the site opens not from the domain root but, for example, at
`https://example.com/docs/`, set `base_url` in `docara.json` before the build:

```json
{
  "base_url": "/docs/"
}
```

Docara builds every link, asset and the search with this prefix, and
`verify-static` checks the folder relative to it. The links an author writes
from the site root — `[Quick start](/en/start/quick-start/)` in Markdown, the
`href` of `header_navigation` items, the address of the internal preview, the
image `![Diagram](/en/guide/scheme.png)`, `srcset`, a video `poster` — get the
prefix from the build as well, so the same content builds both for the domain
root and for a subfolder. Upload the contents of `build_production/`
into the folder the server serves at `/docs/`. Do not fix links by hand
after the build. For a project site on GitHub Pages the address usually looks
like `https://<owner>.github.io/<repository>/`, so `base_url` equals
`"/<repository>/"`.

## Redirects

Entries from `redirects.json` turn into ordinary HTML pages at the old
address: `old/route/index.html` holds a canonical, `noindex`, a meta refresh
and a visible link to the new page. You do not need to configure redirects on
the server. If the host can answer with HTTP 301, such rules can be added
separately, but the static redirect pages stay in the folder. The file format
is described in [Redirects](/en/authoring/redirects/).

## The 404 page

The build puts a `404.html` file into the site root (under `base_url`): a
"Page not found" page with the usual header, navigation, search and site
theme. It has a heading, a short explanation, a link to the home page and a
link that opens search (without JavaScript it leads to the home page). The
text is taken in the main language of the site (`default_locale`); Docara has
an English and a Russian variant.

The server returns this page for any address that does not exist, for example
`/en/a/b/c/`. That is why every link, style, script and the search index on it
is addressed from the site root with `base_url` taken into account, without
relative paths, and the page works the same at any depth.

The page does not get into search engines or into the structure of the site:
it has `<meta name="robots" content="noindex">`, it is absent from the
navigation, the search, `sitemap.xml`, `llms.txt` and
`_docara/page-metadata.json`.

### Turning it on at the host

The server must serve `404.html` for missing addresses and keep the 404
response code.

- **nginx:**

  ```nginx
  server {
      root /var/www/docs/current;
      index index.html;
      error_page 404 /404.html;

      location / {
          try_files $uri $uri/ =404;
      }
  }
  ```

- **Apache** (`.htaccess` or the virtual host configuration):

  ```apache
  ErrorDocument 404 /404.html
  ```

- **GitHub Pages** serves `404.html` from the site root itself, nothing to
  configure. Netlify does the same.
- **S3-compatible storage:** in the static site settings set the error
  document to `404.html`.

For a site in a subfolder add `base_url` to the path, for example
`/docs/404.html` instead of `/404.html` in nginx and Apache.

### Your own text

The labels of the page are the `error.not_found.*` keys in the `lang.json` of
the main language:

```json
{
  "schema": "docara.lang.v1",
  "version": 1,
  "error": {
    "not_found": {
      "title": "Page not found",
      "message": "The page may have moved. Start from the home page or the search.",
      "home": "Go to the home page",
      "search": "Search the documentation"
    }
  }
}
```

To replace the contents as a whole, create a `404.md` page in the content root
of the main language, for example `content/en/404.md`. This is ordinary
Markdown with front matter and components; the build publishes it as
`404.html` instead of `/en/404/`, adds `noindex`, removes the page from the
navigation and the search and turns off breadcrumbs, the outline and the
"Previous/Next" links on it. Write the links to the home page and to search
yourself in such a page. A link with the address `#docara-search` opens
search, as on the built-in page; without JavaScript or on a site without
search it leads to the home page
(see [A link that opens search](/en/settings/search/#a-link-that-opens-search)):

```markdown
# No such page

Check the address, start from the [home page](/en/) or
[open search](#docara-search).
```

Write links from the site root (`/en/start/`), the build will add `base_url`.
Relative links and images are turned into addresses from the root by the
build as well, which assumes that the page sits at `/en/404/`.

### Turning it off

```json
{
  "not_found": {
    "enabled": false
  }
}
```

Without the page the build is the same as before `404.html` appeared: the file
is absent, and `404.md`, if it exists, is published as an ordinary `/en/404/`
page.

### Several languages and the portal

A site gets one `404.html` page, in the main language: the server returns one
file for all missing addresses. Docara does not make separate `/en/404.html`
files for other languages; the file `content/en/404.md` stays an ordinary
`/en/404/` page and does not get into `sitemap.xml`.

A portal build does not create `404.html`: that file belongs to the portal. If
you set `"not_found": {"enabled": true}` in it, the build stops with
`PORTAL_NOT_FOUND_PAGE_FORBIDDEN`. More detail is in
[Building for a portal](/en/build/portal/).

## Caching

The shared build resources are published once under `<base_url>_docara/`.
Files whose path contains a SHA-256 or a revision get a new address when they
change, so they can be cached for a long time:

- `_docara/docara-shell.<sha256>.css` — the shell styles shared by pages,
  4–6 files per page on average;
- `_docara/brand/<sha256>.*`;
- `_docara/vendor/simai-framework/runtime/<revision>/**`.

`Cache-Control: public, max-age=31536000, immutable` suits them. Serve the
HTML pages and the other `_docara/` files without a hash in the path, for
example `_docara/search-index.json` and `_docara/search.js`, without
`immutable` and with a freshness check by `ETag` or `Last-Modified`. Pages
load some resources with a version parameter (`?docara_v=…`, `?sf_v=…`), so a
CDN must include the query string in the cache key.

```nginx
location ~
^/_docara/(docara-shell\.[0-9a-f]{64}\.css$|brand/[0-9a-f]{64}\.|vendor/simai-framework/runtime/)
{ add_header Cache-Control "public, max-age=31536000, immutable"; }
```

For a site in a subfolder add the `base_url` prefix at the start of the
expression.

## Notes for each platform

- **GitHub Pages.** When publishing through GitHub Actions
  (`actions/upload-pages-artifact`) upload `build_production/` as the
  artifact. When publishing from a branch put an empty `.nojekyll` file in
  its root: otherwise Jekyll skips the `_docara/` folder, whose name starts
  with an underscore.
- **Netlify and similar services.** Set `build_production` as the publish
  directory. If the build runs on the service side, its environment needs
  PHP 8.2+, Composer and Node.js 20+; it is simpler to build and check the
  site in CI and upload the finished folder.
- **S3-compatible storage.** Turn on the static site mode with the index
  document `index.html`, so that folder addresses open pages. If the storage
  sits behind a CDN, check that the CDN also serves `index.html` for nested
  routes.

## After the upload

Open the site root and a nested page, check the styles, the search, a link
with a `#fragment` and one redirect page. Open an address that does not exist
at several levels of nesting: the server must answer with code 404 and show
the `404.html` page with its styles and search. The order with an intermediate
folder, a digest and going back to the previous version is described in
[Publishing and rollback](/en/build/publish/).
