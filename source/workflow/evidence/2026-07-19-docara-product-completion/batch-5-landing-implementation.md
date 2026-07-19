# Batch 5 landing implementation

Date: 2026-07-19
Status: mutable-worktree implementation complete; exact candidate not yet cut
Scope: local Docara product only

## Product result

Docara now has one bounded landing preset that explains the product and offers
a working quick-start path without turning Markdown into an untyped layout
language.

The portable starter and the live documentation source both provide:

- one H1 and short product lead;
- one native-link CTA;
- three responsive feature cards;
- one short shell command proof;
- no documentation navigation, search, breadcrumbs, outline or previous/next
  controls.

The live demo is generated at `/landing/`. The starter CTA points to the
starter's real `/guides/getting-started/` route; the documentation demo points
to `/start/`.

## Typed recipes

`PortableMarkdownRenderer` admits two new directives:

- `:::cta`: exactly one Markdown link with visible text;
- `:::features`: one flat unordered list containing 2–6 textual items.

Stable fail-closed errors cover malformed input:

- `MARKDOWN_CTA_LINK_REQUIRED`;
- `MARKDOWN_CTA_LINK_UNSAFE`;
- `MARKDOWN_FEATURES_UNORDERED_LIST_REQUIRED`;
- `MARKDOWN_FEATURES_ITEM_COUNT_INVALID`;
- `MARKDOWN_FEATURES_ITEM_CONTENT_INVALID`;
- `MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED`.

Unsafe CTA protocols use CommonMark's own unsafe-protocol contract. Nested
feature lists, block-level feature item composition, images, raw HTML and
nested portable directives are rejected. Labels made only from ordinary or
Unicode separators, controls, format characters, combining marks or variation
selectors are not treated as visible text; a Unicode letter, number,
punctuation mark or symbol is required.

The Smart-component pre-parser now preserves the portable
`MARKDOWN_BLOCK_LIMIT_EXCEEDED` code when a page exceeds the portable marker
limit; Framework marker overflow continues to use
`FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED`.

## Simai Framework composition

The result uses the already pinned Core/Smart pair and existing utility and
component classes. No Framework repository or revision changed.

- CTA: native `<a>` plus the existing button classes and utility fallbacks;
- responsive CTA: full width on the narrow viewport, compact from the Core
  `sm` breakpoint;
- features: `grid grid-col-1 lg:grid-col-3`, spacing, surface, border and radius
  utilities;
- command proof: native fenced Markdown code rendered by the accepted code
  surface.

The utility fallback keeps the CTA visible and 44 px high even when JavaScript
is disabled and the component CSS loader cannot run.

## Rendering corrections

The implementation also corrects two reusable output defects found during
visual preacceptance:

1. HTML pretty-print indentation is no longer injected into the literal text
   inside fenced `<pre><code>` blocks.
2. Feature items no longer inherit the prose `72ch` maximum, so a one-column
   grid fills its content column.

Landing command text uses `shell` and wraps without hidden horizontal
scrolling on the 390 px viewport.

## Documentation

Updated or added:

- portable format and README recipe summaries;
- syntax, layout and security/error references;
- dedicated CTA and features component pages;
- live landing source and sidecar;
- discoverable link from the layout guide.

## Verification completed before candidate

- focused PHPUnit: 66 tests, 619 assertions — PASS;
- complete sequential PHPUnit suite: 468 tests, 2,435 assertions — PASS;
- Pint — PASS;
- `git diff --check` — PASS;
- static build verifier: 47 HTML pages, 4,931 local references, 0 broken;
- native Google Chrome 150 matrix: 9/9 scenarios — PASS;
- mutable build digest remained stable during browser execution:
  `9638d9d39a714c58cf1b0651751b9462980dd873e7c96d2de61eb1eb6a56e4be`
  across 54 files.

The browser matrix covers 390x844, 768x1024 and 1440x900; light and dark
themes; JavaScript enabled and disabled; keyboard Tab/Enter; the real CTA
destination; responsive columns; 44 px action height; command visibility;
absence of page overflow, console errors, HTTP errors and unexpected network
failures.

This is mutable-worktree preacceptance only. It does not accept an immutable
candidate, authorize publication, or claim public release, production,
ecosystem or complete-goal readiness.
