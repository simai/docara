---
description: How to send an old address to a new one when a route changes. Redirects are declared in redirects.json, without Markdown stubs or PHP.
---

# Redirects

A redirect is needed when a published route changes and old links must keep
leading to a page with the same content. In portable Docara it is a
declarative build input, not a Markdown stub and not project PHP.

## Turning it on

Name the file at the site level:

```json
{
  "schema": "docara.site.v1",
  "redirects_file": "redirects.json"
}
```

Create `redirects.json`:

```json
{
  "schema": "docara.redirects.v1",
  "version": 1,
  "redirects": [
    {
      "from": "guide/old-install",
      "to": "start"
    }
  ]
}
```

`from` and `to` are route slugs without a leading or trailing `/`. An empty
`to` means the root page of the current `base_url`; an empty `from` is
reserved for the system locale redirect and is not allowed in your file. The
builder applies `base_url` itself.

With symmetric language routing a separate publisher creates `/` →
`/<default_locale>/` and, if `legacy_unprefixed_redirects` is on, a redirect
for every former page without a language prefix. These records live in
`build_<environment>.docara/locale-routes.json`; do not repeat them in
`redirects.json`.

## What the build creates

The source route gets a static `index.html` with:

- a `canonical` link to the target;
- `robots: noindex`;
- a meta refresh;
- an ordinary visible link to the same target.

`build_<environment>.docara/redirects.json` also records the SHA-256 of the
canonically normalized source, the locale, `documentation_version`,
`base_url`, the output and the final URLs. It is a receipt for `verify-static`
and publishing. JSON formatting and record order do not change this hash.

The static fallback page does not promise an HTTP `301` or `308` status: the
status depends on the hosting. If it is required for a public SEO move, the
server rule must be generated from the same checked list and accepted
separately.

## Where the build stops

The build stops before it clears the previous output if:

- the target is not an existing generated page;
- the source matches a page, a folder, a file or a service route;
- the source and the target are the same;
- the target is the source of another redirect, forming a chain or a cycle;
- there is an external URL, a query, a fragment, a path traversal or an
  unsafe encoding;
- the file crosses a symlink or hardlink boundary or does not match the
  schema.

The order of records in the input array does not affect the result: the
receipt and the output are sorted canonically.

## Languages

A redirect does not prove that the content is the same. Do not send a
page to another language just to avoid a 404. Keep the old reference, or
prepare a page in the same language in a separate site variant.

After a change, run:

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

Check the source URL and an ordinary target over HTTP from the same output.

## See also

- [Menu and sections](/en/authoring/navigation/) — when a page address changes
- [Multilingual site](/en/authoring/multilingual-site/) — redirects for addresses without a language prefix
- [Redirect error codes](/ru/reference/errors/site/#перенаправления) (in Russian)
