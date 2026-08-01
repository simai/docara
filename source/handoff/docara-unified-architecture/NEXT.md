# Next executable checkpoint: independent M3-A Alert plan review

Checkpoint ID: `docara.batch.m3a.alert_plan`

Route: `/ru/components/alert/`

## Required review

1. Confirm the exact current owner/projection chain and the recorded
   full/single/browser baseline.
2. Confirm `docs/site/content/ru/components/alert.md` as the sole proposed
   public page owner, with no page prose in language/config sources.
3. Confirm that Alert needs only a generic typed block-component capability in
   the existing compiler, registry, Smart gateway and `PageBuilder`.
4. Confirm the early route-selection design: an isolated build must select the
   physical source before compilation and catalog/example projection.
5. Confirm the exact one-route allowlist/language-pack reduction, parity gates,
   test matrix, rollback path and stop conditions.

Plan:
`source/workflow/2026-08-01-docara-m3a-alert-route-plan.md`.

Evidence:
`source/workflow/evidence/2026-08-01-docara-unified-architecture/m3a-alert-plan/`.

## Allowed result

An independent PASS, PASS_WITH_NOTES or blocking plan review. A passing review
may authorize a separate M3-A implementation assignment limited to this route.

## Forbidden now

- creating or migrating the Alert Markdown page;
- changing `src/`, `resources/`, `docs/site/content/`, dependencies or locks;
- reducing the allowlist or deleting legacy;
- touching another route/component;
- claiming M3 implementation, source ownership, migration coverage, release or
  production readiness;
- merge, push, tag, release or deploy.

## Implementation boundary after a separate acceptance

The future implementation must preserve the current URL, Russian content,
HTML, assets, appearance and behavior; use the accepted single target pipeline;
select the route before unrelated compilation/projection in isolated mode; and
retain a one-commit rollback path. Full and isolated builds must pass after the
slice before the exact Alert legacy entries may shrink.
