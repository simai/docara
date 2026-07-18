# Standalone pre-commit reverse review

Date: 2026-07-18
Scope: changes from `v1.3.65` before the standalone candidate commit
Role: independent read-only pre-commit reviewer
Final verdict: `PASS`

## Corrections closed

1. Portable initialization now fails closed whenever any legacy marker exists,
   including all three partial portable-marker cases. No scaffold write occurs.
2. The public builder validates the lexical root before `realpath`, rejecting
   `link`, `link/`, and `link/.` while preserving the resolved destination.
3. Reset-only configuration preserves an empty JSON object through canonical
   and pretty serialization. The published
   `.docara/resolved-page-plans.json` contains `{}`, not `[]`.

The empty-object contract uses the reset-only form. The `$value` form is
documented for scalars, lists, and non-empty objects.

## Verification

- Focused portable suite: 51 tests / 328 assertions, PASS.
- Full repository suite: 322 tests / 996 assertions, PASS.
- Pint: PASS.
- `git diff --check v1.3.65`: PASS.

This is closure of pre-commit findings, not the final cross-repository tester
acceptance for the goal.
