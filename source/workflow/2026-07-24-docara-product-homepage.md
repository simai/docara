# Workflow: Docara product homepage

Date: 2026-07-24
Status: completed
Owner: `docara`
Companions: `marketing`, `content`, `designer`, `docs`, `imagegen`
Track: `docara-consolidation`
Process model: `docara_documentation_site_publication`
Launch record:
`source/workflow/2026-07-24-docara-product-homepage.launch.yaml`

## Current Goal

Turn the Russian locale root into the primary Docara product and
documentation entrypoint: persuasive for an evaluator, useful for a new author
and navigable for an experienced developer.

## Final Outcome

`/ru/` explains the verified value of Docara, demonstrates the landing
capabilities of the product itself, gives short routes into the documentation
and component catalog, and remains readable in both themes and on mobile.

## Done When

- the page has one clear value proposition and primary action;
- evaluators, new authors and developers each have an obvious reading path;
- all claims are supported by current product behavior or explicit repository
  evidence;
- the page demonstrates Hero, feature, showcase, step, column and Promo blocks
  without adding a redundant renderer;
- selected illustrations have real transparency and work in light and dark
  themes;
- the page includes practical routes to quick start, authoring, components,
  build, publication and troubleshooting;
- marketing, editorial, accessibility and visual reviews are recorded and all
  material findings are corrected;
- tests, formatting, production build, static verification and browser
  acceptance pass;
- the exact verified build is published locally to `docara.test` with rollback.

## Audiences And Funnel

- evaluator: understand the product, fit and boundaries;
- new author: create the first site and find the authoring model;
- developer: inspect architecture, components and verification;
- funnel focus: product evaluation to quick-start intent;
- primary action: open the quick start;
- secondary actions: inspect the generated component catalog and live
  documentation experience;
- measurement gap: the current static site has no product analytics contract;
  no tracking is introduced by this workflow.

## Stages

1. Product-fact and existing-block audit.
2. Positioning, information architecture and media plan.
3. Source implementation with existing typed components.
4. Marketing, editorial and visual critical review.
5. Automated verification, reversible local publication and browser acceptance.

## Batches

1. Record the evidence-backed marketing brief and reading paths.
2. Compose the locale root from existing typed landing blocks.
3. Prepare and validate cross-theme product illustrations.
4. Correct findings from marketing, editorial and browser review.
5. Run the exact-candidate suite, build, static verification and local
   publication with rollback.

## Track Linkage

- track: `docara-consolidation`;
- previous goal: transparent media and canonical SIMAI spelling;
- this workflow closes only the product homepage and its local test-site
  publication.

## Boundaries

- no invented customers, adoption metrics, awards, prices or guarantees;
- no duplicated renderer or product-specific styling when a current Docara
  component or SIMAI Framework primitive is sufficient;
- preserve the documentation tree and deep links;
- preserve product-owned `docara.*` and Framework-owned `ui.*` boundaries;
- no commit, push, merge, tag, package publication or public release;
- local publication requires backup, rollback and exact-tree comparison.

## Personal Memory

Personal memory decision: skip

Personal memory reason: repository workflow and evidence are the durable source
of truth; the user did not request a personal-memory update.

## Evidence

- `source/workflow/evidence/2026-07-24-docara-product-homepage/product-marketing-brief.md`;
- `source/workflow/evidence/2026-07-24-docara-product-homepage/content-and-visual-review.md`;
- `source/workflow/evidence/2026-07-24-docara-product-homepage/acceptance.md`;
- desktop light/dark and mobile browser screenshots.

## Kaizen

`stable_reusable_lessons_or_skip_reason`: a public product homepage can remain
an exact demonstration of Docara when its information architecture is composed
from existing typed blocks; removing the older landing page is unsafe when the
documentation inventory is contract-tested, so keep a non-indexed technical
map instead of duplicate marketing content.

## Result

`PASS`.

- the product homepage is live at `https://docara.test/ru/`;
- marketing, editorial and visual reviews pass after correction;
- full PHPUnit, Pint, build and static verification pass;
- desktop light/dark and mobile browser acceptance pass;
- the exact verified tree is served locally with rollback.

No commit, push, merge, tag, package publication or public release was
performed.
