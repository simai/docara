# SEO URL identity acceptance

Date: 2026-07-21
Verdict: PASS WITH LOCAL-STATIC NOTES

- every content page has a locale-prefixed self-canonical;
- `hreflang` is emitted only for an actually available translation;
- `hreflang=x-default` points to the deterministic root route;
- the root route points to the configured default locale and does not inspect
  browser language;
- 92 legacy unprefixed routes are deterministic noindex fallback pages whose
  canonical and visible target point to the corresponding `/ru/` URL;
- search, navigation and authored Markdown now link directly to canonical
  localized URLs rather than relying on legacy redirects;
- static verification found no broken route, fragment or local asset;
- redirect graph collisions, chains, loops and missing targets fail closed in
  the builder/verifier contract.

The local static fallback pages return HTTP 200 with meta refresh. They do not
claim hosting-level 301/308 behavior; a public migration would need generated
server redirects and a separate post-release SEO gate. Docara has no sitemap
publisher in the accepted baseline, so this goal does not invent or claim one.
No public indexing or production SEO readiness is claimed.
