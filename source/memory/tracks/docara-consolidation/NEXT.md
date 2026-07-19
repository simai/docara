# Next Step

## Active Goal

Portable Docara must become replacement-ready relative to the retained legacy
reference without public release, default-branch migration or repository
retirement.

Canonical workflow:

`source/workflow/2026-07-20-docara-legacy-replacement-readiness.md`

## Baseline

- accepted candidate:
  `de87bdef224d518d1c707286d4640be0238d34bc`;
- accepted served digest:
  `502e43119ea2f2fc6ce358042858937060a67c4aa3d4d5ac0295e3d19c8e782f`;
- target: `https://docara.test/`;
- reference: `https://docara-legacy.test/en/`.

## Current Batch

Batch 7: immutable replacement-ready candidate, exact-archive regression,
comparative browser/HCS and independent tester verdict.

## Next Safe Action

Commit the verified working tree as one candidate, test only exact archives,
then publish only the independently accepted build to the local stand with
backup, rollback and digest evidence.

## Hard Boundaries

- no public release, push, merge, tag or default-branch mutation;
- no repository/worktree archive or deletion;
- no Framework owner writes or moving assets;
- no local-site replacement before backup/rollback/digest preflight.
