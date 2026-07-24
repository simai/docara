# Workflow: Docara transparent media and SIMAI brand spelling

Date: 2026-07-24
Status: completed
Owner: `docara`
Companions: `docs`, `imagegen`
Track: `docara-consolidation`
Process model: `docara_documentation_site_publication`
Launch record:
`source/workflow/2026-07-24-docara-transparent-media-and-simai-brand.launch.yaml`

## Current Goal

Make the landing illustrations work on both light and dark Framework surfaces
without baked backgrounds, and normalize the public brand spelling to `SIMAI`.

## Final Outcome

Transparent landing media render without baked theme plates, canonical SIMAI
spelling is enforced, and the exact verified build is served locally with
rollback.

## Done When

- the Hero and three feature illustrations contain a real alpha channel;
- no checkerboard, black tile or theme-specific background is baked into them;
- the component surface, not the image, owns the background;
- active product, documentation, starter and catalog surfaces use
  `SIMAI Framework`;
- a regression test prevents the legacy spelling from returning;
- tests, production build, static verification and light/dark browser checks
  pass;
- the exact verified build is published locally to `docara.test` with rollback.

## Stages

- transparent-media preparation;
- product-surface spelling normalization;
- automated build and static acceptance;
- reversible local publication and browser acceptance.

## Batches

1. Generate and extract transparent landing media.
2. Replace active authoring/catalog assets and use `object-fit: contain` for
   transparent Hero/Promo illustrations.
3. Normalize `SIMAI Framework` on maintained product surfaces.
4. Add a product-surface spelling contract.
5. Build, verify, publish locally and visually check both themes.

## Track Linkage

- Track: `docara-consolidation`;
- previous goal: full-bleed landing correction;
- this workflow closes only transparent landing media, spelling consistency and
  the local test-site publication.

## Boundaries

- preserve PHP namespace `Simai\Docara`;
- preserve historical workflow/evidence records;
- keep product screenshots rectangular;
- no commit, push, merge, tag, package publication or public release;
- local publication requires backup, rollback and exact-build comparison.

## Personal Memory

Personal memory decision: skip

Personal memory reason: repository workflow and evidence are the durable source
of truth; the user did not request a personal-memory update.

## Evidence

- `source/workflow/evidence/2026-07-24-docara-transparent-media-and-simai-brand/acceptance.md`;
- PNG alpha metadata and light/dark composites;
- PHPUnit, Pint, build, static verification and deployed-tree comparison;
- browser screenshots in both themes.

## Result

`PASS`.

- Hero and three feature illustrations have true alpha;
- public product and documentation surfaces use `SIMAI Framework`;
- the spelling contract is covered by PHPUnit;
- the production build and static verifier pass;
- the exact verified tree is served from the actual ServBay document root;
- desktop light/dark and mobile light browser checks pass.

No commit, push, merge, tag, package publication or public release was
performed.

## Kaizen

`stable_reusable_lessons_or_skip_reason`: when ServBay points at a nested static
document root, publication verification must read the active Caddy root before
rsync and then compare the exact built and served trees.
