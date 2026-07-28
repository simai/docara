# Replacement product decisions

## Legacy runtime boundary

Portable Docara is the primary product contour. The existing Blade/Jigsaw
runtime remains a retained compatibility contour in this repository while
active legacy projects still use it. Its documentation is a frozen reference,
not a promise that portable builds execute Blade, PHP callbacks, Collections,
translation providers or arbitrary tags.

## Locale

One portable site root and one generated build contain exactly one locale.
`default_locale` is the build locale. A section or page may repeat the same
locale for explainability, but a different value is rejected before output is
cleaned.

There is no implicit fallback, translation or cross-language redirect. Several
languages are several explicit site variants with their own source root,
`base_url`, output, navigation and search index. A switcher may be added only
after an immutable variant manifest proves that every offered target exists.

The current Russian product variant uses `/`. The English `/en/**` legacy
corpus is retained as a compatibility/reference variant until translated
portable content exists. It must not be redirected to Russian pages and called
equivalent.

## Documentation version

JSON field `version: 1` remains the schema-contract version. It is not the
documentation release.

Every site declares one `documentation_version`, exposed in generated HTML
metadata, resolved plans and the redirect receipt. The search index belongs to
that isolated build but does not duplicate the version field. The version is
informational within the build and does not silently alter routes. Several
documentation versions are separate immutable site variants under distinct
`base_url` values. Portable v1 has no dynamic version switch or fallback.

## Redirects

Site-only `redirects_file` points to schema-validated declarative data.
Redirect records contain relative route slugs and always resolve under the
configured `base_url`.

Accepted redirects:

- are same-site and target an existing generated page;
- have one normalized source and one final target;
- cannot shadow a page, asset or reserved namespace;
- cannot form a self redirect, chain or cycle;
- produce deterministic static HTML with `noindex`, canonical, meta refresh
  and a visible ordinary link;
- produce a canonical machine-readable receipt consumed by static verification.

External, protocol-relative, query-dependent, traversal and fragment redirects
are unsupported in v1. Hosting-specific status codes are not claimed by static
HTML; the portable fallback remains useful on any static host.

## Legacy URL corpus

The live legacy stand contains 48 HTML routes: `/` plus 47 English `/en/**`
routes. Its canonical live-path fingerprint is
`b56d73df2da3059fbca3808124c81baade25f09513cd7f90f4f053fd7ca2fcfa`.
The independently derived 47-Markdown source corpus fingerprint is
`e57931bbec8b47119a9cbd799538f0275014a7381650b4e24c8293f1f9e0f9c9`.
Both digests use UTF-8 routes sorted by code point, one route per LF, with a
final LF, so they can be reproduced from the served and source trees.

`/` is already the current product home and is an explicit exception. The 47
English routes are retained legacy-reference routes, not Russian redirect
targets. Old portable component-demo routes are the first generated redirect
set because their semantic targets already exist in the same locale.

## Reader controls

Theme remains the accepted portable reader preference. Width and text size are
deferred until measurement proves a task that browser zoom and author-owned
`layout.max_width` do not solve. Fullscreen is retired as duplicate browser
functionality.

## Code

`native.code` owns semantic fenced code and a readable static `<pre><code>`
fallback inside one visible surface. The immutable Simai Framework v5.3.2
runtime at `7e836d8a9414d5da553fb1ab0404721e5b48769a` progressively owns the
single language/copy header, syntax highlighting and line numbers. Docara does
not render a second header or implement another clipboard runtime.

The block scrolls horizontally without widening the page. The Framework copy
action selects the exact code text, including its intentional trailing
newline, without line-number chrome. If the runtime or a requested language
module is unavailable, the static code remains readable.

`native.code.enhanced` now means editor-grade author controls such as optional
line numbers, highlighted lines and diff states. Those fine-grained controls
remain a Framework gap; the basic highlighting and line-number behavior does
not.
