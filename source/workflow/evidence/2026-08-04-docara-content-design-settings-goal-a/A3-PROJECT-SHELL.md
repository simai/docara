# A3 — project-owned shell contribution

Date: 2026-08-04
Status: `PASS`
Implementation commit: `ac345582c8df8659114ec33b476d54da5b8a3dd6`
Exact Goal A product candidate: `8c04160ab50549b060fb933cf80f86193cd92113`

## Outcome

The fixture adds `acme.docs`, `acme.footer` and `acme.notice` only through
project-owned layout, Section, Block and View Tree artifacts. The footer
declares `shell.footer`; the project Section declares the same capability; the
element block is admitted and rendered by the existing DesignRegistry,
RegionCompositionResolver, ViewTreeRenderer, LayoutComposer and PageBuilder.

`git diff ac34558^ ac34558 -- src` is empty. The fixture therefore proves a
project shell contribution without an engine source edit, project callback,
class, PHP/template path or arbitrary HTML/CSS surface.

## Verification

- `ProjectDesignCompositionTest`: production compiler/render proof;
- capability mismatch, binding-owned prop spoofing, wrong Smart target,
  duplicate provider/namespace and unknown presentation negatives: PASS;
- existing DesignRegistry traversal, symlink, duplicate/case collision and
  unsafe View Tree/path tests remain part of the full suite;
- project/provider provenance is visible through `list binding`,
  `inspect binding <id>` and the resolved block provenance.

The project contribution is a design/capability artifact, not a new executable
binding provider. Package bindings remain trusted provider-owned resolvers;
project config can only select admitted IDs and data.

## Rollback

Revert the fixture commit. No runtime or public-content data depends on it.
