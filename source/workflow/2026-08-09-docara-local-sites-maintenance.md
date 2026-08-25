# Workflow: Docara local sites maintenance

Date: 2026-08-09
Status: completed
Mode: local runtime maintenance
Process: bounded single-agent batch

## Goal

Rebuild the local `docara.test` site from the current Docara repository and
remove the obsolete `docara-new.test` local site without changing product
source or any Git/release state.

## Current Goal

Completed: rebuild the bounded local `docara.test` target and remove only the
obsolete `docara-new.test` target with verified recovery paths.

## Done When

- `/Users/rim/Sites/docara-new.test` is absent after a verified external
  recovery archive is created;
- `docs/site` builds successfully with the explicit ServBay PHP runtime;
- the candidate passes `verify-static` before publication;
- `/Users/rim/Sites/docara.test/build_production` is replaced atomically after
  a verified rollback copy is created;
- static and HTTPS smoke checks pass on the served local site;
- repository product/runtime source has zero diff from the pre-task baseline;
- workflow, project graph context and project memory are synchronized at the
  task boundary;
- no commit, push, tag, release or public deployment occurs.

## Scope

Allowed writes:

- ignored local dependency/build artifacts required for the build;
- `/Users/rim/Sites/docara.test/.docara-staging/<batch>/`;
- `/Users/rim/Sites/docara.test/.docara-backups/<batch>/`;
- `/Users/rim/Sites/docara.test/build_production` through same-filesystem
  directory renames;
- a recovery archive below `/Users/rim/Sites/.docara-backups/`;
- exact deletion of `/Users/rim/Sites/docara-new.test` after archive proof;
- this workflow, task evidence and project-technology derived context.

Forbidden:

- product/runtime source edits;
- ServBay configuration or process changes;
- any other local site;
- Git refs/history, commit, push, tag, release or public deployment;
- enabling or using the disabled legacy Docara skill.

## Owners And Gates

- integration: `teamlead`;
- build/repository boundary: `dev`;
- backup, rollback and local runtime safety: `ops`;
- acceptance verdict: `tester`;
- legacy Docara skill: disabled and not used.

Federation route selected the disabled legacy owner and therefore cannot be
used as an execution owner. The allowed fallback is `dev + ops + tester` using
the repository's current documentation and historical local-publication
evidence. Action-gate preflight passed with backup and destructive-safety gates.

## Batch

1. Capture Git/source baseline and local-site inventory.
2. Create and verify recovery archives.
3. Prepare dependencies and build `docs/site/build_production`.
4. Verify candidate and atomically publish it to the stable local target.
5. Remove only `docara-new.test` and verify absence.
6. Run static/HTTP acceptance and task-boundary technology synchronization.

## Stages

- [x] inventory and safety preflight;
- [x] backup and candidate build;
- [x] atomic local publication and obsolete-site removal;
- [x] acceptance, graph, memory and project-technology synchronization.

## Batches

- [x] single bounded local runtime maintenance batch.

## Evidence Plan

- verified external archive and same-filesystem rollback copies;
- Docara doctor, production build and static verifier outputs;
- HTTPS status and semantic page markers;
- repository diff, JSON/YAML, project-context and technology freshness checks.

## Track Linkage

Completed maintenance track: `docara-local-sites-maintenance`. It does not
reactivate the completed `docara-unified` implementation track.

## Rollback

- restore `docara-new.test` by extracting its verified archive to the exact
  original path;
- restore the previous `docara.test/build_production` via same-filesystem
  rename from the batch rollback copy;
- do not modify ServBay configuration.

## Stop Conditions

- either target resolves outside the exact `/Users/rim/Sites/` paths;
- archive creation, listing, checksum or size verification fails;
- candidate static verification fails;
- served target and rollback/staging directories are on different filesystems;
- any unexpected tracked product/runtime source change appears;
- a Git/release/publication action becomes necessary.

## Evidence

Evidence directory:
`source/workflow/evidence/2026-08-09-docara-local-sites-maintenance/`.

## Result

- `docara-new.test`: removed; verified archive remains readable at
  `/Users/rim/Sites/.docara-backups/local-sites-20260809-225400/docara-new.test.tar.gz`;
- `docara.test`: rebuilt from `docs/site` and published by same-filesystem
  directory renames to the stable `build_production` path;
- new served tree: 652 files, 261 HTML pages, 32,965 checked local references,
  zero broken references, canonical digest
  `f1480b2ec882766966bd1217e9344e5a18fee8df827af1a4b0397921005a7033`;
- previous served tree: preserved independently and as `served-before`, both
  with canonical digest
  `a3aced5396d2b031092afcf13f3687d37e0603539b24d1327f9b9d290b914581`;
- HTTPS smoke: primary pages and search index `200`; unique missing route and
  removed `docara-new.test` host `404`;
- product/runtime source diff introduced by this task: zero;
- Git/ref/release/public-deploy actions: none.

The standard project-technology sync briefly inferred a false active `release`
track from closed-boundary text. That generated track was removed, canonical
terminal memory was restored, and derived context was synchronized with the
known faulty memory inference disabled. The repeated safe sync produced zero
diff with `ready/current/ready` state and no active track.

The final-response checker accepted project memory, outcome integrity and
project-technology freshness, then failed closed only because its registry
again selected the disabled legacy Docara owner and a full release technology
packet. No release evidence was fabricated and no legacy owner source was
loaded.

The full PHPUnit suite and a broader focused subset were stopped after several
minutes because the portable-builder fixtures repeatedly perform expensive
site builds. No failure appeared before interruption. The actual candidate and
served tree both passed the dedicated static verifier. The separate current
governance test run produced one fixture-only failure because its shadow root
does not copy the canonical terminal evidence path; the real repository
`project-context.php check` passed. No source correction was made in this local
site maintenance task.
