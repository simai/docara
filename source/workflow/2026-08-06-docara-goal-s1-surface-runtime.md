# Goal S1 — Full-bleed Geometry & Shared Surface Runtime

Date: 2026-08-06
Status: `goal_s1_ready_for_independent_audit`
Track: `docara.track.surface-hero-media`
Branch: `codex/docara-unified-architecture`
Entry HEAD: `8e4b71c58065a1f49382dd8077809363e0eed873`
Accepted parent product: `eb35f5c6f18e5eb9be69e91887b09486f5703136`
Exact product candidate: `45276f63422e8b8465b33e415d3fc302dfeac570`

## Outcome

Correct the landing direct-child full-bleed geometry and add one typed,
registry-owned `docara.surface` container. The implementation keeps the one
Markdown -> typed IR -> renderer registry -> Smart Gateway -> LayoutComposer
-> PageBuilder path. Hero background media and homepage art-direction changes
remain S2 work and are not part of this goal.

## Checkpoints

1. freeze the accepted baseline and reproduce the direct-child selector gap;
2. add the typed Surface definition, capability-owned nesting contract and
   fail-closed local media admission;
3. render Surface through the existing typed Markdown/PageBuilder pipeline and
   correct the Layout-owned full-bleed selector;
4. prove preview/production, full/full/single, static, browser and package
   behavior on one exact product candidate;
5. synchronize graph, specification and handoff, then stop at
   `goal_s1_ready_for_independent_audit`.

## Risk map and stop conditions

- a second parser, renderer registry, Gateway, LayoutComposer or PageBuilder is
  forbidden;
- project callbacks, PHP/template paths, arbitrary CSS/classes and remote or
  data media are forbidden;
- nested Surface and Hero/Showcase/Promo children remain forbidden in v1;
- any external owner/site write, release, merge, push, tag or deploy stops the
  goal;
- if existing Hero semantics change beyond the corrected outer width, stop and
  preserve the exact diff.

## Rollback

The entry boundary is `8e4b71c58065a1f49382dd8077809363e0eed873`.
Rollback is the normal revert of the bounded S1 commits; no public/test site or
external repository is modified. Generated build/evidence roots are disposable.

## Nonclaims

- Goal S1 is not independently accepted by this executor;
- S2/S3, Hero background mode and shared adoption by Hero/Showcase/Promo are not
  implemented;
- release, merge, tag, publication and deployment are not authorized.

## Exact result

- `docara.surface` is a typed, registry-owned public container with closed
  presentation enums and project-local decorative media admission;
- landing direct-child full-bleed reaches the viewport while the inner
  container remains aligned; documentation full width is bounded by `main`;
- full/full/single build ledger:
  `129eef82bdcefc29174115cc51e9e2698cdf110ac8f66e6c67e36ea128547d42`;
- existing Hero section is byte-identical to the entry baseline at
  `5a5e8996aae6dad9ed410070d06cdc277c193e02dd659c00796818e711bd2a49`;
- integrated evidence is indexed in
  `source/workflow/evidence/2026-08-06-docara-surface-hero/INDEX.md`.
