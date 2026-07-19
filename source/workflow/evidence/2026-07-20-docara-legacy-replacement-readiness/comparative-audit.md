# Comparative audit: portable Docara vs legacy

Date: 2026-07-20
Status: working-tree implementation verified; exact candidate pending

## Verdict

The portable implementation is the stronger product base. The legacy frontend
should remain a compatibility reference, not the design or runtime to restore.
Portable Docara now keeps the useful reading affordances while replacing the
unsafe or opaque parts with deterministic Markdown/JSON and an immutable Simai
Framework runtime.

## What legacy did well

- A compact shell exposed requirements, quick start and code in the first
  desktop viewport.
- The article column was wider and the H1 smaller, so long technical pages felt
  dense and direct.
- Search was permanently visible on desktop.
- The right outline was visually prominent.
- Width, text-size and fullscreen controls exposed more reader choices.

## What legacy did poorly

- The checked menu exposed no reliable active trail or `aria-current`; observed
  depth and disclosure semantics were weaker than the portable four-level tree.
- Several controls lacked accessible names or a reliable expanded state, and
  mobile touch targets fell below 44 px.
- Search did not close reliably with Escape and its result surface was overly
  wide.
- `/en/en` was generated as a broken breadcrumb route and the favicon returned
  404.
- The install/runtime story depended on Node, mutable frontend sources,
  executable PHP/Blade callbacks and moving project-owned behavior.
- Dynamic translation, collections and arbitrary tags mixed product content
  with executable build logic, preventing a safe universal migration claim.

## What portable Docara already did better

- Four visible menu levels, active ancestors, `aria-current`, breadcrumbs,
  outline and real previous/next links share one navigation model.
- Search, theme settings, landing pages and the generated component catalog are
  first-class product surfaces.
- Dialog interactions have names, Escape behavior, focus containment and focus
  restoration.
- Markdown and validated JSON remain the authoring source of truth; output and
  Framework revisions are deterministic.
- The static verifier checks pages, fragments, assets, search, catalog,
  redirects and immutable Framework projection.

## Corrections made from the comparison

- Removed card-on-card framing and quieted ordinary navigation rows.
- Reduced the documentation H1/header scale while preserving the landing hero.
- Replaced mobile in-flow navigation and outline with native overlay dialogs;
  opening either no longer moves the article.
- Added declarative, collision-safe, deterministic static redirects and a
  verifier-owned receipt.
- Fixed the product contract to one locale and one documentation version per
  build, without a false English-to-Russian fallback.
- Made fenced code one surface. Static `<pre><code>` is the fallback; the pinned
  Simai Framework owns the single language/copy header, highlighting and line
  numbers.

## Browser evidence from the working tree

- 1440 px: content-first three-column docs shell, quieter navigation and one
  code surface.
- 390 px: document width equals viewport width; no page-level horizontal
  overflow.
- Long code: the block width remains 360 px while its internal pre scroll width
  reaches 445–480 px.
- Mobile menu: opening the 308 × 844 dialog leaves the article at the same
  coordinates.
- Active mobile path: `Содержание и макет` → `Макеты и навигация`, with the
  current page exposed.
- Code runtime: 7 source blocks, 7 Framework headers and 7 copy buttons, with
  no nested `.source`.
- Copy action reaches the Framework `Copied` state and selects source text
  without line-number chrome.

## Deliberate non-equivalences

- Collections, remote collections, PHP/Blade callbacks, executable generated
  files, Azure build-time translation and arbitrary custom tags are not
  silently reproduced.
- English `/en/**` content remains the retained reference until an equivalent
  portable English variant exists; it is not redirected to Russian.
- Reader width and text-size controls are deferred; fullscreen is retired as a
  browser capability.
- Version/locale switchers, edit/report links, social image and custom 404 are
  admitted only after explicit safe contracts exist.
- Fine-grained code options such as highlighted lines, diff modes and optional
  line numbers remain a Framework gap.

## Remaining acceptance boundary

The implementation is not yet declared replacement-ready. It still requires
one immutable candidate, exact-archive rebuild/test/browser/HCS evidence, an
independent tester PASS for that SHA and a separate local publication with
backup, rollback and served-digest proof. Public release, default-branch
migration and repository archival remain out of scope.
