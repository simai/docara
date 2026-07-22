# B2-B3 verification

Date: 2026-07-22

## Result

- The old Jigsaw/Blade/Mix runtime, its starter, providers, tests and snapshots
  are removed from the Docara 2 candidate.
- The declarative Markdown/JSON pipeline is the only page publication path.
- The legacy publisher selector, preview site, semantic parity scaffolding and
  template mirror are removed.
- Production publication retains only the fail-closed receipts needed by the
  verifier; the development preview is no longer emitted.

## Inventory

- `src`: 131 files (was 242 at audit baseline)
- `tests`: 31 files (was 556 including snapshots)
- `stubs`: 20 files; the only starter is `stubs/portable`

## Executed checks

- PHPUnit: 305 tests, 4160 assertions, PASS.
- Pint dirty scope: PASS.
- Composer strict validation: PASS (ServBay Composer emits upstream PHP 8.4
  deprecation notices, exit status is zero).
- Legacy runtime symbol scan over `src`, `tests`, `scripts`, `resources` and
  `composer.json`: zero matches.
- `git diff --check`: PASS.

No release, push or deployment was performed.
