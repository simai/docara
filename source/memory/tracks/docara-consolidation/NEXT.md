# Next Step

## Where We Stopped

The declarative View Composition Contract is accepted and recorded in:

- `source/workflow/2026-07-20-declarative-view-composition-contract.md`;
- `source/workflow/evidence/2026-07-20-declarative-view-composition-contract/`.

The exact candidate is published locally at `https://docara.test/`. The legacy
publisher remains byte-identical and primary.

## Next Meaningful Goal

Plan and execute a separate full-shell migration that replaces the legacy
publisher with the accepted declarative pipeline without losing current Docara
capabilities, URLs, portable installation or rollback.

## Required Starting Gates

1. Produce a legacy-to-declarative capability parity matrix.
2. Define reversible migration and rollback boundaries.
3. Keep the accepted legacy renderer unchanged until the replacement candidate
   passes exact archive, static and browser acceptance.
4. Do not mix this migration with public release, default-branch change or
   `docara-mix` retirement.
