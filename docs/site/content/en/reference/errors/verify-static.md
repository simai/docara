---
description: The marks of the verify-static command — broken links and anchors, repeated ids, search, languages, redirects and build files — with the cause and the fix.
---

# Build check marks

`docara verify-static` checks the finished build and prints a report in
JSON. Each problem is a record in the `broken` list:

```json
{"page": "ru/start/index.html", "reference": "../guide/#setup", "target": "@missing-fragment:setup"}
```

- `page` — the page with the error, or `@build` if the error concerns the
  whole build;
- `reference` — a link from the page, or a check mark that starts with `@`;
- `target` — where the link leads, the mark or an explanation.

If a record has no `@` mark, it is an ordinary broken link: the build has no
file at the `target` address. Fix the link in the source Markdown and rebuild
the site. More about the check on [Checking the build](/build/verify/).

Most marks below mean that the build folder was changed after
`docara build` or was built with another Docara version. Then rebuild the
site with the same version and do not edit files in `build_*`.

## Links and anchors

These errors are usually fixed in Markdown.

| Mark | Cause and what to do |
| --- | --- |
| `@missing-fragment` | A link leads to an anchor `#...` that the page does not have. The anchor name follows the colon. **What to do:** Find the real `id` of the heading in the built page and fix the link. The anchor changes when the heading text changes. |
| `@duplicate-html-id` | The page has two elements with the same `id`, usually two identical headings. **What to do:** Rename one of the headings. |
| `@unsafe-fragment-encoding` | The anchor in a link is encoded incorrectly: broken `%XX` or not UTF-8. **What to do:** Write the anchor as plain text or fix the encoding. |
| `@unsafe-percent-encoding` | The link path has invalid `%XX`. **What to do:** Fix the path in the link. |
| `@unsafe-decoded-path-segment` | After decoding, the path contains `/`, `\` or `..`. **What to do:** Use an ordinary path without encoded separators. |
| `@outside-root` | A relative link goes above the site root through `../`. **What to do:** Give a path from the site root: `/section/page/`. |
| `@outside-deployment-base` | A root link `/...` leads outside `base_url`. **What to do:** Check `base_url` or give a path inside the site. |
| `@html-fragment-inventory` | The page could not be parsed to collect its `id`s. **What to do:** Rebuild the site. If the error repeats, report it. |
| `@unreadable` | An HTML file of the build cannot be read. **What to do:** Check the file permissions and rebuild the site. |

## Build content

| Mark | Cause and what to do |
| --- | --- |
| `@unsafe-artifact-entry` | The build has a symbolic link, a hard link or a special file. **What to do:** Remove them from `assets/` and the content, then rebuild the site. |
| `@build-receipts-in-output` | The published folder holds the service folder `.docara/`. It must lie beside the build, not inside it. **What to do:** Rebuild the site and do not copy `.docara/` into the published folder. |
| `@html-base-element` | The page has a `<base>` tag. **What to do:** Remove it: Docara builds links without `<base>`. |
| `@page-build-identity` | The service marks of the page (the language, the documentation version) do not match the build plan. **What to do:** Rebuild the whole site. |
| `@resolved-page-plans` | The plan of the built pages is missing or damaged. **What to do:** Rebuild the whole site. |
| `@standalone-examples` | The files of isolated examples do not match the build description. **What to do:** Rebuild the whole site. |
| `@discovery-files` | `sitemap.xml`, `robots.txt` or `llms.txt` do not match the expected ones, or a link from `llms.txt` leads to a missing file. **What to do:** Rebuild the site. Create the files from `discovery.llms_links` that a separate script writes before the check. |

## Search, languages and redirects

| Mark | Cause and what to do |
| --- | --- |
| `@search-artifacts-missing` | Search is on, but `_docara/` has no search files. **What to do:** Rebuild the site. |
| `@search-artifacts-unexpected` | Search is off, but search files or links to them are left. **What to do:** Rebuild the site into a clean folder. |
| `@search-index-contract` | The search index does not match the pages of the build. **What to do:** Rebuild the whole site. |
| `@locale-route-contract` | The redirects between languages do not match the `locale_routing` settings. **What to do:** Rebuild the whole site. |
| `@redirect-contract` | The redirect pages do not match `redirects.json`. **What to do:** Rebuild the whole site. |

## SIMAI Framework and the component catalogue

| Mark | Cause and what to do |
| --- | --- |
| `@framework-asset-projection`, `@framework-shell-asset`, `@vendor-asset-selection` | The SIMAI Framework files in `_docara/` do not match those pinned in `simai-framework.lock.json`. **What to do:** Rebuild the site. Do not edit files in `_docara/`. |
| `@framework-shell-stylesheet`, `@site-theme-stylesheet` | The presentation or site theme styles were changed after the build. **What to do:** Rebuild the site. |
| `@framework-preload-order`, `@framework-preload-contract` | The preload order of SIMAI Framework files on the page does not match the expected one. **What to do:** Rebuild the site. |
| `@component-catalog-contract`, `@component-index-contract`, `@public-registry-schema-projections` | The component catalogue or the schema reference on the site does not match the Docara package. **What to do:** Rebuild the site with the same Docara version that is installed in the project. |

## An error code instead of a mark

| Code | Cause and what to do |
| --- | --- |
| `PREVIEW_BUILD_PURPOSE_FORBIDDEN` | A preview folder is checked, not a build for publication. **What to do:** Build the site with `docara build production` and check `build_production`. |
