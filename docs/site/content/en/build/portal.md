---
description: Building the Docara site for one portal path and packing it into a release archive with a checksum and a receipt.
---

# Building for a portal

A portal gathers several products into one site, for example
`https://portal.example.com/`: each product takes its own path
(`/ru/acme/docs/`), while the portal owns the home page, the language prefix
and the hosting files. The `portal` section in `docara.json` builds the site
for such a path, and the `portal-artifact` command packs the result into
three release files that the portal downloads, checks and lays out along its
own paths.

## Configuration

A portal build is a separate site configuration: one language, the portal
path in `base_url`, the full address in `site_url` and the `portal` section.

```json
{
  "default_locale": "ru",
  "locales": {
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "public_prefix": "",
      "fallbacks": []
    }
  },
  "locale_routing": {
    "strategy": "default_unprefixed",
    "root": "default_locale",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": false
  },
  "base_url": "/ru/acme/docs/",
  "site_url": "https://portal.example.com/ru/acme/docs/",
  "portal": {
    "product": "acme-docs",
    "home_url": "https://portal.example.com/ru/",
    "home_label": "portal.example.com"
  }
}
```

| Key | Required | Value |
|---|---|---|
| `portal.product` | yes | The product identifier in the portal registry: lowercase Latin letters, digits and a hyphen, the first one a letter. It becomes part of the release file names. |
| `portal.home_url` | yes | The absolute `https` address of the portal home page. The header of every page links to it. |
| `portal.home_label` | no | The text of the link to the portal home, up to 80 characters. The default is the host name from `home_url`. |

The build stops with a clear error if:

- `site_url` is not set (`PORTAL_SITE_URL_REQUIRED`);
- there is more than one language (`PORTAL_SINGLE_LOCALE_REQUIRED`): each
  language is built separately under its own portal path;
- `locale_routing.strategy` is not `default_unprefixed`
  (`PORTAL_UNPREFIXED_ROUTING_REQUIRED`): the language prefix is already part
  of the portal path, so inside the site the pages live without a prefix.

A site without a `portal` section builds as usual.

## What changes in the build

- The `canonical` of every page is the full address on the portal: `site_url`
  plus the page route.
- The header starts with a link to `portal.home_url` and is marked
  `data-portal-shell="header"`, the footer `data-portal-shell="footer"`. The
  portal uses these attributes to check the switch to the shared shell.
- `llms.txt` stays in the build and points at the pages and
  `_docara/page-metadata.json` by their portal addresses. `sitemap.xml` and
  `robots.txt` are not created: the portal writes them.
- If the content or the assets publish `CNAME`, `.nojekyll`, `robots.txt`,
  `sitemap.xml` or `404.html` in the root of the build, the build stops with
  `PORTAL_FILE_COLLISION`.
- The `404.html` page ([404 page](/en/build/hosting/#the-404-page)) is not
  created: the portal answers a missing address. `"not_found": {"enabled":
  true}` in a portal build stops it with `PORTAL_NOT_FOUND_PAGE_FORBIDDEN`,
  and `404.md` is published as an ordinary `404/` page.
- Links in content written for a site with a language prefix lead to pages
  without the prefix: `[Start](/ru/start/quick-start/)` in a build for
  `/ru/acme/docs/` becomes `/ru/acme/docs/start/`. Links from the site root
  (`/start/`) work the same way.
- The search index, the assets, the media and the example frames live inside
  the build and are addressed under `base_url`.

Build and check a portal build like any other:

```bash
php vendor/bin/docara build portal
php vendor/bin/docara verify-static build_portal
```

`verify-static` for a portal build checks `llms.txt` and the absence of
portal files; errors are printed with `reference: "@discovery-files"`.

## Packing a release

```text
php vendor/bin/docara portal-artifact [environment] --release=TAG --repository=OWNER/NAME --commit=SHA [--product-version=VERSION] [--output=DIR]
```

The command reads `build_<environment>` (`build_production` by default) and
the `portal` section from `docara.json` and writes into `--output`
(`build_<environment>.portal/` by default):

| File | Contents |
|---|---|
| `<product>-<version>-portal.tar.gz` | The build; `index.html` lies in the root of the archive. |
| `<product>-<version>-portal.tar.gz.sha256` | The SHA-256 of the archive in `sha256sum` format. |
| `<product>-<version>-portal-receipt.json` | A `simai.io.portal-artifact` receipt, version 1. |

`<version>` is the value of `--product-version`, or of `--release` without
the leading `v`.

```bash
php vendor/bin/docara portal-artifact portal \
  --release=v1.2.0 \
  --repository=acme/docs \
  --commit="$(git rev-parse HEAD)" \
  --output=portal-out
```

The receipt:

```json
{
    "schema": "simai.io.portal-artifact",
    "version": 1,
    "product": "acme-docs",
    "release": "v1.2.0",
    "repository": "acme/docs",
    "commit": "<40 hex>",
    "locale": "ru",
    "base_path": "/ru/acme/docs/",
    "archive": "acme-docs-1.2.0-portal.tar.gz",
    "sha256": "<64 hex>",
    "files": 1706,
    "framework_pair": "ui-<12 hex>",
    "docara_version": "3.6.0"
}
```

`files` is the number of files in the archive, `framework_pair` the SIMAI
Framework pair the pages load, and `docara_version` the version of Docara
that packed the build.

Before writing, the command checks the build and stops with
`PORTAL_PAGE_INVALID`, `PORTAL_FILE_COLLISION`, `PORTAL_INDEX_MISSING` or
`PORTAL_BUILD_LINK` if:

- the build contains a symbolic link or a portal file;
- there is no `index.html` in the root;
- a page has no `<html lang>` of the build language, no non-empty `<title>`,
  no description or no link to the portal home;
- the `canonical` of a page lies outside `site_url`;
- the pages load different Framework pairs.

A page without a `description` in its front matter takes its description from
the first paragraph. If there is no paragraph, add `description` to the front
matter. Redirect pages need no description: their `canonical` leads to a
target inside `base_url`.

## Archive reproducibility

The archive is built so that the same build gives the same bytes:

- the tar entries are sorted by path, and directories come before their
  contents;
- every entry has modification time 0, owner `root` (0:0), mode `0644` for
  files and `0755` for directories;
- the gzip header is fixed: no file name, time 0 and an unspecified
  operating system.

The compressed bytes depend on the zlib implementation, so compare archives
built on the same platform. Build the canonical archive in one place, for
example in the CI of your own project; the portal pins its SHA-256 from the
receipt.

The Docara documentation itself has been published only on GitHub Pages
since 3.5.0: the Docara release workflow no longer builds its portal archive
or attaches it to the release. The `portal` section and the
`portal-artifact` command stay available to projects.

Checking after the download:

```bash
sha256sum -c acme-docs-1.2.0-portal.tar.gz.sha256
jq -r .sha256 acme-docs-1.2.0-portal-receipt.json
```

Both values must match.
