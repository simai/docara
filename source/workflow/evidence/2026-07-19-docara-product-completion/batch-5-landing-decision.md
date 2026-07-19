# Batch 5 landing decision

Date: 2026-07-19
Status: accepted for test-first implementation
Scope: local Docara product only

## Reader outcome

The landing page must let a first-time author or developer understand within
one viewport that Docara builds documentation and small landing sites from
Markdown and strict JSON, then follow a real link to the quick start.

The minimum complete page contains:

1. the accepted brand header and reading-settings control;
2. an open hero composition with one H1, a short lead and one primary action;
3. three equal feature cards;
4. one short command proof using a native fenced code block.

Search, documentation navigation, breadcrumbs, outline and previous/next stay
absent from the landing preset. A second CTA, decorative illustration, logo
strip, FAQ and footer are deferred because they do not improve the current
reader outcome.

## Exact Framework mapping

The implementation keeps the immutable pair already pinned by Docara:

- Core `simai/ui` `7e836d8a9414d5da553fb1ab0404721e5b48769a`;
- Smart `simai/ui-smart` `dd786bbae98391fb21df9b4e1e6cd402ead0614c`;
- pair `sf-v5.3.2-7e836d8a-dd786bba`.

The exact Core build already publishes the required utilities:

- layout: `flex`, `grid`, `flex-col`, `grid-col-1`,
  `lg:grid-col-3`;
- spacing and width: `gap-*`, `p-*`, `w-full`, `sm:w-auto`,
  `max-w-prose`;
- presentation: `bg-surface-0`, `border`,
  `border-outline-variant`, `radius-2`;
- action styling: the existing `sf-button`, `sf-button--default`,
  `sf-button--primary`, `sf-button--size-1` and
  `sf-button-text-container` classes.

No new hero, card, badge, icon or Smart-component is introduced. The admitted
`ui.button` Smart-component renders a native button and has no `href` property,
so it cannot be used for navigation.

## Typed Markdown decision

Two bounded Docara recipes close the landing gap without creating a second
content language:

- `:::cta` accepts exactly one Markdown link and renders that native link with
  the existing Framework button classes;
- `:::features` accepts exactly one unordered Markdown list and renders its
  items as a responsive one-to-three-column Framework utility grid.

Both recipes:

- remain semantic HTML and work without JavaScript;
- use the existing CommonMark parser and reference-definition behavior;
- reject empty, malformed, nested or unsafe input with stable error codes;
- add no runtime asset and change no Framework repository.

Generic columns remain deferred to Batch 7. The features recipe is deliberately
specific to a short feature list and must not become an untyped layout escape
hatch.

## Demonstration content

The starter and live `/landing/` demo use:

- H1: `Документация и лендинги из Markdown и JSON`;
- lead: Docara builds a static site with one PHP command while content remains
  in Markdown, settings inherit from strict JSON, and the interface uses Simai
  Framework;
- CTA: `Начать работу` -> `/start/`;
- features: writing in Markdown, configuring in JSON and building on PHP;
- command proof:
  `composer require simai/docara`,
  `php vendor/bin/docara init --portable`,
  `php vendor/bin/docara build local`.

The documentation exposes one discoverable link to this live demo from the
layout and navigation guide.

## Acceptance contract

- `/landing/` builds and returns `200`;
- the first desktop and mobile viewport contains the H1, lead and working CTA;
- the CTA is `<a href="/start/">`, not a button or Smart button;
- the feature list is one column below the Core `lg` breakpoint and three
  columns from `960px`;
- there is no outer hero card;
- mobile `390x844`, tablet `768x1024` and desktop `1440x900` have no horizontal
  overflow or clipped primary action;
- CTA keyboard focus is visible and Enter follows the link;
- light, dark and system themes preserve readable contrast;
- JavaScript-disabled rendering preserves all landing content and navigation;
- static link verification passes;
- the documentation preset and accepted Batch 4 shell do not regress.

## Evidence sources

- `src/PortableSite/PortableHtmlRenderer.php`;
- `src/PortableSite/PortableMarkdownRenderer.php`;
- `stubs/portable/content/landing.md`;
- `resources/framework/manifests/ui-button.json`;
- `source/workflow/evidence/2026-07-19-docara-product-completion/framework-building-block-map.md`;
- exact Core `distr/core/css/utility.full.css`;
- independent read-only UX/designer and Framework inventories completed on
  2026-07-19.

This decision does not claim public release, production readiness or support
for all Simai Framework records.
