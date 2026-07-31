# Docara Framework scrollbar integration

Date: 2026-07-25
Status: completed
Primary owner: SIMAI Framework consumer integration
Framework coordinator: `ui-control`

## Goal

Use the published SIMAI Framework `sf-scrollbar` contract in Docara instead of
native product-owned scrollbar styling. The documentation sidebar and page
outline must use the same configurable preset, with `overlay` as the default.

## Exact Framework input

- source: `ui-loader@e6dd3cb8a0cc89169ea5c3ede807f8749b088b94`;
- builder: `ui-builder@f9aa00ab2c4646262a85b7f61629e17af1f78ba7`;
- Core: `ui@f0b41eb526a8f1daf24a34484143bdfabf7802a4`;
- Smart: `ui-smart@ab896dc7cd33f151377e3992ffb286769beee7f7`.

This is the published default-branch tuple recorded by `ui-control`. It is not
a release tag or a production-readiness claim.

## Changed surface

- immutable Docara Framework locks and projected Smart bytes;
- `layout.scrollbar.preset` schema and propagation;
- publisher view model and docs-layout template;
- sidebar and outline layout CSS;
- starter and documentation-site configuration;
- focused configuration, rendering and site-build tests;
- reader-facing configuration and layout documentation;
- local disposable build published to `https://docara.test/`.

## Acceptance

- omitted preset resolves to `overlay`;
- accepted values are `overlay`, `persistent`, `standard`, and `hidden`;
- `overlay` uses the public no-attribute default;
- left navigation and right outline use `.sf-scrollbar` with a direct
  `.sf-scrollbar__viewport`;
- Docara does not implement custom track/thumb behavior;
- light/dark, desktop/mobile, overflow/no-overflow, wheel, keyboard, hover,
  drag and idle return are checked;
- exact locks, asset hashes, tests, static verification, links and
  `git diff --check` pass;
- the local site is backed up before replacement and has a rollback path.

## Simplicity contract

- primary outcome: the site owner selects one documented preset while readers
  receive consistent Framework behavior;
- simplest complete alternative: one site-level enum and two Framework
  compositions, without a Docara scrollbar runtime;
- progressive disclosure: no configuration is required for the recommended
  `overlay` default;
- protected complexity: keyboard access, focus, forced colors, coarse pointer
  fallback and RTL remain owned by Framework;
- residual complexity: mobile sheets and search-result lists remain native
  scroll containers in this bounded batch because they are different UI
  surfaces and are not part of the approved sidebar/outline scope.

## Exclusions

- unfinished LTR/RTL Framework work;
- Framework source or generated-repository edits;
- tags, Framework releases and production deploys;
- cleanup of unrelated dirty files or worktrees.

## Rollback

Keep the pre-publication `/Users/rim/Sites/docara.test` snapshot under its local
backup directory. Revert this bounded consumer diff and restore that snapshot
if runtime or browser acceptance fails.

## Completion evidence

- PHPUnit: `331 tests, 5131 assertions`, PASS;
- focused site-builder suite: `35 tests, 838 assertions`, PASS;
- static verification: `198` HTML pages, `11452` local references, no broken
  references;
- generated and served trees are byte-identical by their sorted content
  manifest digest:
  `7f454e43102b06e36c23a89faa381c496cf18e003994520d3ee7256c345bb77a`;
- live browser: two Framework roots initialized with
  `data-sf-scrollbar-ready="true"`; the overflowing left rail shows the
  Framework thumb, while the non-overflowing right rail remains inactive;
- the default preset omits the redundant `data-sf-scrollbar` attribute;
- the visible thumb uses the Framework `2px` resting size and an adaptive
  theme-aware color;
- local publication: `https://docara.test/ru/`, HTTP `200`;
- rollback snapshot:
  `/Users/rim/Sites/docara.test/.docara-backups/framework-scrollbar-20260725-204515/build_production.previous`;
- follow-up flush correction removes the last `1px` Docara wrapper padding;
  focused regression: `36 tests, 904 assertions`, PASS; corrected rollback:
  `/Users/rim/Sites/docara.test/.docara-backups/scrollbar-flush-20260725-212803/build_production.previous`;
- detailed acceptance:
  `source/workflow/evidence/2026-07-25-docara-framework-scrollbar-integration/acceptance.md`.

No tag, Framework release, production deployment or readiness claim was made.
