# Workflow: canonical `section.json`

Date: 2026-07-20
Status: completed
Process model: `general_delivery`
Current state: `completed`
Target state: `review_ready`

## Goal

Replace `_section.json` with the simpler canonical name `section.json` across
portable Docara, update all current author/developer documentation, and serve
the verified result on the local `docara.test` stand.

## Done When

- `section.json` is the only accepted section descriptor name.
- A legacy `_section.json` fails closed with a stable, actionable error.
- Site, section and page inheritance remains deterministic and provenance uses
  the new paths.
- Starter files, examples, tests and current documentation contain no
  `_section.json` contract reference.
- Focused and full tests pass.
- The documentation site builds and passes the static verifier.
- `docara.test` serves the new build after backup and atomic swap.
- HTTPS and browser smoke confirm the new documentation and no regression.

## Scope

Allowed:

- portable loader, navigation and site-builder source;
- portable fixtures, schemas/error documentation and tests;
- starter/template mirror;
- current product/developer documentation;
- this workflow and its evidence directory;
- local `.docara-backups`, `.docara-staging` and
  `/Users/rim/Sites/docara.test/build_production`.

Forbidden:

- public release, tag, merge or package publication;
- Framework owner repositories or immutable Framework lock;
- legacy Docara runtime behavior unrelated to portable descriptors;
- secrets, `.env`, database or ServBay configuration.

## Primary Outcome And Simplicity

An author sees one unsurprising naming system:

```text
docara.json
section.json
<page>.page.json
<page>.md
```

The simplest complete implementation is one canonical filename plus one
explicit migration error. A silent alias, compatibility switch, automatic
rename or second descriptor registry is intentionally excluded.

## Batch Plan

| Batch | Work | Verification | Status |
| --- | --- | --- | --- |
| 1 | Baseline, workflow and filename contract | inventory, action gate | completed |
| 2 | Loader/navigation/starter/tests migration | focused tests, legacy negative tests | completed |
| 3 | Current documentation and examples | zero current-contract legacy references | completed |
| 4 | Full regression and deterministic documentation build | PHPUnit, static verifier, identical build check | completed |
| 5 | Local backup, atomic publication and QA | digest, HTTPS, browser, rollback proof | completed |
| 6 | Evidence, simplicity verdict and commit | HCS validator, diff check, clean worktree | completed |

## Risks And Protections

- Existing unpublished prototypes using `_section.json` must receive an
  explicit rename error rather than silent ignore.
- Both names in one directory must not create ambiguous precedence.
- Historical workflow/evidence is immutable and may retain old filenames.
- Local publication requires an independent backup and same-filesystem
  `served-before` rollback tree.

## Evidence

Root:
`source/workflow/evidence/2026-07-20-section-descriptor-rename/`

## Current Progress

- User approved the canonical rename and local rebuild.
- Federation route, process resolver and preflight action gate passed.
- Baseline worktree was clean at `250a12f`.
- Fourteen current source/starter descriptors were renamed and validated.
- Loader, navigation and asset inventory reject the legacy name explicitly.
- Focused tests pass; 544 non-legacy-snapshot tests pass.
- Two legacy Jigsaw snapshot failures reproduce unchanged at the baseline.
- The deterministic static build passes 6036 link checks across 66 pages.
- `docara.test` serves the verified build after backup and atomic swap.
- Desktop and 390 px browser acceptance passed without console errors.
- Implementation candidate `b9b95ac` is committed.
- Human-centered simplicity validator and tester verdict: PASS.

## Next

No implementation work remains in this workflow. Public push, release, merge
and package publication remain outside its scope.
