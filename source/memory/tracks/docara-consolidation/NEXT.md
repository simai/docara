# Next Step

## Where We Stopped

Track `docara-consolidation` is attached to workflow:

- `source/workflow/2026-07-21-docara-symmetric-locale-routing.md`
- workflow title: Workflow: Symmetric locale routing for Docara

## Next Meaningful Goal

Make symmetric locale routing the recommended Docara product model: every
locale owns an isolated `content/<locale>` tree and an explicit `/<locale>/`
URL prefix, while `/` deterministically routes to the configured default
locale. Migrate the current Russian Docara documentation to `content/ru` and
`/ru/` without losing old URLs, static portability, deterministic builds,
search, navigation, Smart components, RTL support or update safety.

## Stages

1. Restore current state from the workflow and project memory.
2. Execute the next safe batch from the workflow.
3. Update track memory after the batch.

## Next Safe Batch

If a public release is requested later, prepare a separate release workflow
covering hosting-level 301/308 redirects, sitemap policy, public monitoring and
consumer migration.

## Checks

- Read `source/workflow/2026-07-21-docara-symmetric-locale-routing.md`.
- Check route/gates before writes.
- Update this track memory after progress.
