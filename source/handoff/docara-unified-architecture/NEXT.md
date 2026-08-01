# Next executable checkpoint: M3.2 shared runtime and Alert slice

Goal batch: `docara.batch.m3.migrate`

Overall goal recovery source:
`source/workflow/2026-08-01-docara-m3-ru-components-goal.md`.

Evidence index:
`source/workflow/evidence/2026-08-01-docara-unified-architecture/m3-ru-components/INDEX.md`.

## Accepted starting state

- M3-A plan independently accepted at
  `b14fe4e1e70a5465fe382bd5ced1de26cb65a315`;
- M3.1 inventory/baseline PASS: 32 routes, 2 physical owners, 30 generated
  projections, deterministic full builds, all-route isolated parity, zero
  broken references and representative browser baseline;
- no M3 runtime or content migration has yet been claimed.

## Execute M3.2

1. Select an isolated route before compiling or projecting unrelated catalog
   pages/examples while retaining the same PageBuilder used by full build.
2. Extend the existing typed in-memory Document IR with the minimal generic
   block-component contract required by Alert; do not add Alert-specific IR.
3. Resolve the block through the one renderer registry and one Smart gateway,
   including fail-closed route/source/line/column diagnostics.
4. Move only `/ru/components/alert/` to
   `docs/site/content/ru/components/alert.md` and prove focused/full/isolated/
   static/browser parity.
5. Reduce exact Alert legacy entries only after successful parity and a
   zero-reference scan; retain a Git commit rollback path.
6. Continue automatically into the remaining M3 families after M3.2 PASS.

## Current boundaries

- preserve public URLs, reader meaning, features, assets and appearance;
- one PageBuilder, renderer registry and Smart gateway only;
- no migration of other locales and no Framework/dependency-lock changes;
- no unproved legacy deletion or M3 completion claim;
- no merge, push, tag, release or deploy.
