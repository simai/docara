---
description: The base path base_url and the public address site_url — sitemap, robots.txt, llms.txt, canonical addresses, social cards and the browser tab title.
tags: [site_url, base_url, sitemap, robots, llms, seo, address]
---

# Site address and search engines

## Base path: base_url

`base_url` is the path the site lives under on the server: `"/"` for the
domain root or, for example, `"/docs/"` for a subfolder. Set it before the
production build: the generator adds it to all internal links and file
addresses, and `verify-static` checks that links stay inside it.

## Public address: site_url

`site_url` is the absolute public address of the site: the origin and
`base_url` together. The path in `site_url` must match `base_url`, otherwise
the build stops with `SITE_URL_BASE_MISMATCH`.

```json
{
  "base_url": "/docara/",
  "site_url": "https://simai.github.io/docara/",
  "discovery": {
    "description": "Docara documentation.",
    "llms_links": [
      {"title": "AI manifest", "path": "ai.json", "description": "machine-readable documentation index"}
    ]
  },
  "branding": {
    "share_image": "assets/share.png"
  }
}
```

When `site_url` is set, the build also:

- writes `sitemap.xml`, `robots.txt` and `llms.txt` to the site root (under
  `base_url`);
- makes `canonical` and `hreflang` of every page absolute;
- adds Open Graph and Twitter card tags: `og:title`, `og:description`,
  `og:url`, `og:type` (`website` for a locale's home page, `article` for the
  rest), `og:locale` and `og:locale:alternate` for translations,
  `og:site_name`, `twitter:card`, and with `branding.share_image` set,
  `og:image` and `twitter:image`.

**`sitemap.xml`** lists every published indexable page by its absolute
address. Translated pages have `xhtml:link hreflang` to all translations. The
map leaves out drafts (`draft: true`), pages with `noindex: true` in the
front matter, redirect pages, the `404.html` page, other error pages (the
last route segment is a 4xx or 5xx code, for example `404/`) and pages that
show the source text instead of a translation in `publish-current` mode.

**`robots.txt`** allows crawling the whole site and names `sitemap.xml`.
Search robots read `robots.txt` only at the domain root: for a site in a
subfolder, for example on a project's GitHub Pages, the file helps only if
the domain root serves it too or links to the sitemap.

**`llms.txt`** follows the [llmstxt.org](https://llmstxt.org/) convention:
the heading is the site name, then the description (`discovery.description`,
otherwise the description of the main locale's home page), the top-level
menu sections of the main locale with links and descriptions, the home pages
of other languages and machine-readable files: the links from
`discovery.llms_links`, `sitemap.xml` and `_docara/page-metadata.json`. A
path in `llms_links` starts from `base_url`; the file may appear after the
build too (for example `ai.json` written by a separate script), but it must
exist by the time `verify-static` runs.

`branding.share_image` is a PNG, JPEG or WebP file in the project; it can be
overridden for a section or a page in `section.json` and `*.page.json`.
Without `site_url` the image is not published.

Without `site_url` the build output stays as before: these files and tags
are not created. If the content already publishes its own `robots.txt`,
`sitemap.xml` or `llms.txt` at the root, a build with `site_url` stops with
`DISCOVERY_FILE_COLLISION`.

With `site_url` set, `verify-static` checks that all three files exist and
are correct: `sitemap.xml` is a valid urlset, each address lies under
`site_url` and leads to a built indexable page, every such page is in the
map, and `hreflang` points to pages of its own locale; `robots.txt` matches
the expected text; every `llms.txt` link lies under `site_url` and leads to a
file of the build.

A site built for one path of a portal sets a `portal` section next to
`site_url`: then the build writes only `llms.txt` and leaves `sitemap.xml` and
`robots.txt` to the portal. See [«Сборка для портала»](/en/build/portal/) (in
Russian).

In CI it is handy to set `site_url` in the same step as `base_url`. For
GitHub Pages both values come from `actions/configure-pages`:

```bash
jq --arg base "${BASE_PATH%/}/" --arg site "${SITE_URL%/}/" \
  '.base_url = $base | .site_url = $site' docara.json > docara.json.pages
mv docara.json.pages docara.json
```

where `BASE_PATH` is `steps.pages.outputs.base_path` and `SITE_URL` is
`steps.pages.outputs.base_url`. This is how
<https://simai.github.io/docara/> is built.

## Tab title

A page's `<title>` is "Page title — Site name". If the page title matches the
site name (usually on the home page), the name is not repeated:
`<title>Docara</title>`, not "Docara — Docara".

## See also

- [Hosting the site](/en/build/hosting/) — `base_url` on different hosts
- [Building for a portal](/en/build/portal/)
- [Address and publishing fields in docara.json](/ru/reference/docara-json/#адрес-и-публикация) (in Russian)
