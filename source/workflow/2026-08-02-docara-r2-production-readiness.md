# R2 production-readiness goal

Status: `superseded_after_determinism_audit`

Correction notice: the independent R2 audit found that two fresh dist
consumers produced different `_docara/page-metadata.json` and tree hashes.
Therefore every PASS below is retained only as historical evidence and cannot
authorize deployment. The active recovery source is
`source/workflow/2026-08-02-docara-r2-determinism-correction.md`.

Input revision: `f50ce3c816867936f7697af8413120259c023089`

Accepted artifact source:
`56a2abf8bad05923f689141afc0bb045aa4d6734`

Accepted artifact SHA-256:
`04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753`

## Goal

Prepare one unambiguous Docara 2 release candidate and a production-readiness
dossier that leaves the user only the explicit decision whether to deploy it
to `docara.test`. Prove the exact package in disposable production-like
consumers, classify all current/candidate runtime differences, and verify an
atomic same-filesystem cutover and exact rollback in a disposable mirror.

This goal does not authorize writes to `/Users/rim/Sites/docara.test`, Caddy
configuration, existing backup/staging directories, Git history outside normal
checkpoint commits, or any merge, push, tag, publication, release or deploy.

## Track linkage

- track: `docara-consolidation`;
- final outcome: a safely deployable Docara 2 product with one content-first
  architecture and an explicit user-controlled production gate;
- completed goals: M0-M5, R1 implementation and R1-C semantic correction;
- current goal: R2 production readiness without live cutover;
- next goal: user-approved deployment action, only if the user chooses it.

## Current Goal

Prove that the independently accepted R1-C exact artifact can replace the
currently served `docara.test` build through a fully specified, production-like
and exactly reversible process, without changing the live site in R2.

## Launch record and owner map

- process: `client_live_project` with safe preparation only;
- primary owner: `ops`;
- implementation/package owner: `dev`;
- acceptance gatekeeper: `tester`;
- delivery/governance: `teamlead`;
- machine state: repository project graph, with raw sources remaining
  authoritative;
- execution mode: single agent; no subagents;
- stale installed Docara skill: disabled and forbidden.

## Allowed and forbidden surfaces

Allowed writes:

- this repository on `codex/docara-unified-architecture`;
- disposable directories created with `mktemp -d` outside the active site;
- compact sanitized evidence under
  `source/workflow/evidence/2026-08-02-docara-r2-production-readiness/`.

Forbidden writes:

- `/Users/rim/Sites/docara.test/**` including `.docara-backups` and
  `.docara-staging`;
- Caddy/ServBay configuration or service state;
- other repositories/worktrees;
- merge, push, tag, GitHub/Composer publication, release or deploy.

## Access, environment and safety boundary

- access method: local read-only filesystem and HTTP inspection; no secret or
  credential is required for the approved preparation scope;
- active target reported by independent audit:
  `/Users/rim/Sites/docara.test/build_production`;
- target health and configuration are read-only inputs until separately
  user-approved deploy action;
- preflight action gates passed access/env/repo/source/secret checks and blocked
  live production writes until backup/rollback/smoke evidence exists;
- R2 satisfies that preparation gap in repository evidence and disposable
  mirrors, but does not override the live gate.

## Restore unit, backup and retention

- restore unit: the complete active `build_production` directory;
- proposed cutover: build and verify a sibling same-filesystem candidate, move
  current root to a uniquely named rollback directory, then atomically rename
  candidate to `build_production`; Caddy root remains unchanged;
- rollback: atomically move failed candidate aside and rename the preserved
  prior directory back to `build_production`, then repeat required smoke;
- no new live backup is created in R2;
- existing `.docara-backups` and `.docara-staging` are inventory-only and are
  never deleted or changed;
- retention proposal must cap future release backups by count and age while
  always retaining the immediate pre-cutover restore unit; any deletion needs a
  later explicit approval.

## Stop conditions

- any required live write, credential, merge, push, tag, release or deploy;
- product decision changing locale, URL or version contract;
- exact artifact reproducibility drift;
- non-exact disposable rollback;
- security/data-loss risk without bounded mitigation;
- required Framework producer change.

Required cutover stop thresholds:

- any required-route HTTP 4xx/5xx;
- missing or broken local asset/reference;
- browser console/page error or page overflow;
- candidate/current digest mismatch against the approved manifests;
- failed atomic rename or failed exact rollback.

## Stages

| Stage | Scope | Evidence | Status |
| --- | --- | --- | --- |
| R2.1 | Record independent R1-C acceptance and release identity | governance commit, exact hashes | pass |
| R2.2 | Rebuild/install exact package in disposable consumers | package/consumer manifests | pass |
| R2.3 | Compatibility and security closure | PHP/macOS/Linux matrix, audits/scans | pass |
| R2.4 | Exact HTTP/browser/product acceptance | route smoke, interactions, screenshots | pass |
| R2.5 | Current/candidate delta and deployment dossier | classified diff, mirror cutover/rollback | pass |
| R2.6 | Integrated outcome review and handoff | graph/docs/handoff, clean worktree | pass |

## Batches

1. R2.1 — acceptance/governance and immutable release identity.
2. R2.2 — exact package rebuild, dist consumers and HTTP preview.
3. R2.3 — PHP/macOS/Linux, dependency, security and archive checks.
4. R2.4 — 103-route HTTP smoke and browser/product matrix.
5. R2.5 — current/candidate delta and disposable atomic cutover/rollback.
6. R2.6 — release packet, graph/handoff, outcome-integrity and hygiene.

## Done When

- independent R1-C acceptance is recorded and local release-readiness is
  accepted without opening release/production gates;
- one planned version/tag target and one exact artifact are unambiguous;
- two exact dist consumers and a production-like HTTP preview pass;
- PHP 8.3/Linux are proved or have a precise reproducible external blocker;
- package, dependency, security and license surfaces pass;
- all 103 candidate/current routes and every path delta are classified;
- disposable current -> candidate -> smoke -> rollback restores the exact
  current digest;
- release notes, upgrade notes, operator commands, retention, stop thresholds
  and approval points are ready;
- graph/specification/handoff report the actual state and leave only a separate
  user-approved live action;
- worktree is clean and no forbidden action occurred.

## Evidence and recovery

- evidence index:
  `source/workflow/evidence/2026-08-02-docara-r2-production-readiness/INDEX.md`;
- rollback boundary: input revision `f50ce3c…` plus logical R2 commits;
- recovery source: this file;
- current batch: complete;
- next safe action: only the user's explicit choice
  whether to run the documented live cutover; no further process design is
  required.

## Evidence Plan

Each batch writes a compact Markdown result and, where useful, a sanitized JSON
summary under the R2 evidence root. Every result binds the relevant source SHA,
archive SHA, environment and command outcome. Build trees, Composer vendor,
raw browser profiles and private absolute-path dumps remain disposable and are
not committed.

## Nonclaims

No live cutover, merge, push, tag, publication, release, production approval or
complete non-Russian translation is claimed.
