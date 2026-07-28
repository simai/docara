# Batch 0 preparation evidence

Date: 2026-07-18
Status: completed

## Exact Base And Isolation

- Accepted Docara candidate:
  `f51debe3e1def82d2dcf611bf820c4517cd65f8f`.
- Implementation parent:
  `26d0641c5df0b9b4e01b40324eaed2d97df30b62`.
- Clean worktree:
  `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation`.
- Branch: `codex/docara-consolidation`.
- The worktree started at the exact accepted candidate and was isolated before
  implementation writes.

The following pre-existing user worktrees were explicitly excluded from all
writes and cleanup:

- `/Users/rim/Documents/GitHub/docara` — dirty recovery branch, 90 status
  entries at inventory time;
- `/Users/rim/Documents/GitHub/docara-template` — dirty recovery worktree, 6
  entries;
- `/Users/rim/Documents/GitHub/docara-mix` — dirty recovery worktree, 8 entries;
- `/Users/rim/Documents/GitHub/ui-doc` — dirty worktree, 10 entries.

No user changes in those repositories were reset, stashed, overwritten, or
used as an implementation source.

## Process And Safety

- Federation/process resolution selected `general_delivery`, owner `teamlead`,
  and companion owners `docara`, `sf5`, `dev`, `tester`, `ops`, `docs`, and
  `ux`.
- Cycle: `coding_batch_cycle`.
- Initial transition: `repository_prepared -> ready_to_code`; current launch
  state after implementation began: `coding_started`.
- Project doctor identified the repository as Docara and returned success with
  no findings.
- The reversible local-work preflight action gate returned success. Its
  ignored raw runtime path was recorded as
  `source/output/action-gates/action-gate-report-20260718065623.json`. A fresh,
  command-specific gate remains mandatory before any local ServBay output
  replacement or remote repository archival.

## Local Runtime Baseline

- `https://docara.test/` returned HTTP 200 during preparation.
- Site root: `/Users/rim/Sites/docara.test`.
- Existing served output:
  `/Users/rim/Sites/docara.test/build_production`.
- The existing site contains legacy dependencies/build material and is treated
  as runtime data, not as a clean source for the new engine.
- `.env` was not read and is outside the work scope.

Publication contract fixed during preparation:

1. build and verify the candidate outside the served path;
2. record candidate hashes;
3. run a command-specific runtime gate;
4. move the current served output to a timestamped backup;
5. atomically move the candidate into place;
6. verify HTTP, routes, assets, themes, and responsive layouts;
7. restore the backup on any failure.

## Dependency Baseline

- PHP acceptance uses ServBay PHP 8.2:
  `/Applications/ServBay/package/php/8.2/current/bin/php`.
- A dependency tree first resolved under PHP 8.4 selected incompatible/newer
  transitive behavior and produced subprocess signal failures. It was removed
  from the clean worktree and rebuilt with PHP 8.2 compatibility.
- The focused portable Markdown test passed before the incident:
  4 tests / 12 assertions.
- Full-suite and build results must be rerun after incident recovery; the
  earlier focused pass is baseline evidence, not final acceptance.

## Consumer Inventory

An exhaustive bounded scan of 235 Git roots/current worktrees found six unique
direct `laravel-mix-docara` consumers:

1. `simai-env/docara`;
2. `publications`;
3. `sf4-doc`;
4. `ui-doc-core`;
5. `sitepack/docara`;
6. `ui-doc` through its nested/core relationship.

Dedicated clean `codex/docara-vite-migration` worktrees were created from
refreshed `origin/main` for the six consumers. Inventory passed; migrations,
default-branch acceptance, and retirement did not.

Important findings:

- `ui-doc-core/copy-template-configs.js` can copy old webpack/Mix files back
  into a consumer, so root-only edits are insufficient.
- `simai-env/docara` and `sitepack/docara` vendor a full old core and therefore
  require both root and nested/core migration.
- `ui-doc` has a partial Vite surface but old nested/core and CI assumptions
  still require clean-init verification.
- The dirty recovery implementation based on a local esbuild script is not the
  canonical Vite contract and is not used.

Therefore `docara-mix` retirement is **NOT READY**.

## Framework Baseline

- Exact Core compatibility revision:
  `simai/ui@7e836d8a...` (`v5.3.2`).
- Exact Smart compatibility revision:
  `simai/ui-smart@dd786bba...` (`v5.3.1`).
- Registry inventory: 331 bounded entities — 225 utilities, 60 components, 45
  Smart-components, and 1 recipe.
- Alert/button have usable existing contracts. Code/steps/table/card use
  semantic HTML plus utilities. Tabs are blocked by the missing backend
  manifest and incomplete `cl-tabs` CSS/JS contract.

See `framework-component-classification.md` for the normative implementation
decision.

## Batch 0 Verdict

`PASS` for preparation and isolation only.

This is not implementation, release, production, ecosystem, or retirement
readiness. Batch 1 is currently in bounded correction/recovery, documentation
must be rebuilt, every consumer migration remains to be accepted, and tabs
remain blocked.
