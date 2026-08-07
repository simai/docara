# Docara Alert page and complete local icon projection correction

Date: 2026-08-07
Status: complete_ready_for_independent_audit

Status: `local_framework_runtime_ready_for_independent_audit`

Current stage: `docara.stage.lfr.local_framework_runtime`

Current batch: `docara.batch.lfr.integrated_retest`

Current next action: `independent_local_framework_runtime_audit`

Next roadmap goal: `docara.stage.lfr.local_framework_runtime` (`audit_pending`, authorized=`true`)

## Outcome

The public Alert guide follows the same compact reference sequence as Badge,
and every demonstrated Alert state renders its icon from the immutable local
Framework asset closure on `docara-new.test`.

## Reproduced defects

- `docara.alert` emits `check_circle` for the success state, but the accepted
  Framework Alert CSS explicitly assigns `--sf-icon--color: transparent` to
  every success icon, so the glyph exists but is invisible;
- the active locally published Outlined font is also the incomplete 409 KiB
  Framework subset, so the general local icon closure is not complete;
- the exact official Google projection already contains complete Rounded and
  Sharp families but omits the matching complete Outlined family;
- the Alert guide renders the type matrix as four unframed blocks, unlike the
  table-followed-by-copyable-example pattern used by Badge.

## Bounded implementation

1. Extend the existing `docara.framework_icon_projection.v1` with the exact
   official Outlined variable font from the already pinned
   `google/material-design-icons@50f0603134ce7b70b2d71b686cc13e8b57ccb74c`.
2. Keep one `FrameworkAssetPlanner`; make its existing Outlined face consume
   that projected file instead of the incomplete runtime subset.
3. Reorder Alert documentation into intro, table and copyable rendered example
   for `type`, then the same sequence for `variant`.
4. Add contract and real docs-site PageBuilder regressions.
5. Rebuild twice, verify full/full/single equality and static references, then
   atomically switch only `docara-new.test` after a successful action gate and
   preserve a rollback backup.
6. Add one bounded compatibility declaration after the immutable Framework
   CSS so a success icon uses the existing semantic `--sf-success` token. This
   does not change the renderer or component identity dispatch.

## Done when

- success `check_circle`, clear, info, warning and danger icons render as local
  glyphs without external requests;
- Alert `type` and `variant` sections each present description, table and
  copyable rendered example in that order;
- existing Alert schema, typed IR, Gateway and PageBuilder path are unchanged;
- focused/full tests, deterministic builds, static verification and desktop/
  mobile browser checks pass;
- tracked worktree is clean; no push, release or write to `docara.test` occurs.

## Human-centered simplicity

- primary outcome: a reader can understand Alert variants and see every icon;
- changed visible surface: one Alert guide and its already-present icons;
- simplest complete alternative: finish the existing three-family local font
  projection and reuse the established Badge documentation pattern;
- removed/avoided: no new gallery, registry, renderer, icon service or special
  success-state branch;
- protected complexity: immutable hashes, local-only publication, admission,
  accessibility and atomic rollback remain mandatory.

## Rollback

- Git: revert the focused product/governance commits.
- Site: use `scripts/atomic-static-cutover.php rollback` with the exact active,
  candidate, backup and digest values recorded in fresh evidence.

## Exact result

- product candidate: `d5e9ecbb1b65904b4015c4a8b8db3aa66d7fe30f`;
- implementation commits: `2a04b48804b02af023538863e5fd34c539687f1d`
  and `68617a246a5328a5abe280a039e881011b602e95`;
- exact build digest: `db628b95db1087878f46c087297c289c153cdc1ef9675f474f358189d04b8521`;
- exact evidence:
  `source/workflow/evidence/2026-08-07-docara-alert-page-correction/INDEX.md`.
