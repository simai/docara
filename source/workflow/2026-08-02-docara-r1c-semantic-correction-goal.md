# R1-C semantic architecture correction goal

Status: `in_progress`

Input/rollback revision:
`3c491e5bfdf60c8227954b27d50dc050f058d71b`

Superseded R1 artifact source:
`8c0d14566837b6e6f4552d14c656ea14b202cd18`

Superseded R1 ZIP SHA-256:
`83afd355436284a0040390c88e1d125f3e5648932a23ff324ba9afa9af5eb561`

## Goal

Close `DOCARA-DEBT-001..012` by making runtime, public schemas, starter,
documentation, specification, tests and the release artifact describe and use
one content-first contract. Produce a new immutable deterministic candidate and
fresh evidence without rewriting the superseded artifact.

## Done When

- public configuration has no `language_pack` and public build inputs are only
  physical Markdown, locale `lang.json`, composition settings and owned assets;
- obsolete package packs/schema/runtime have zero production/package references
  or a proved package-only boundary that cannot enter PageBuilder;
- front matter and `locales.missing_page_policy` are real, validated runtime
  contracts with actionable diagnostics;
- public docs/specification/actual classes/error codes agree;
- semantic source and artifact documentation gates reject the retired model;
- a new exact ZIP is byte-identical from two clean clones, has zero broken
  packaged-doc links and passes two fresh consumers;
- the superseded R1 candidate updates to the new candidate and rolls back
  exactly without changing project-owned files;
- full/single/static/browser/security matrices bind to the new artifact;
- graph/handoff name only the new candidate as current after independent retest;
- worktree is clean and no merge, push, tag, publication, release or deploy was
  performed.

## Milestones and batches

| Milestone | Batches | Verification | Status |
| --- | --- | --- | --- |
| R1-C.1 truthful governance | debt intake, correction state, historical artifact table | graph/JSON/YAML/diff | in_progress |
| R1-C.2 source boundaries | consumer inventory, remove public packs/field, zero-reference | focused runtime/schema/init tests | planned |
| R1-C.3 truthful authoring | public docs, route convention, front matter, missing-page policy, class/error map | focused positive/negative docs/runtime tests | planned |
| R1-C.4 semantic gates | source links, artifact links, schema/starter/runtime vocabulary | focused and negative fixtures | planned |
| R1-C.5 new candidate | two clean packages/consumers, old-to-new update/rollback | hashes, preservation and negative matrix | planned |
| R1-C.6 integrated retest | full suite/build/parity/static/browser/security, graph/handoff | reverse-outcome review | planned |

Each green implementation batch is committed separately and immediately opens
the next safe batch. Historical evidence is append-only and is not edited to
pretend the old artifact was corrected.

## Owners and gates

- delivery/integration: `teamlead` fallback;
- implementation/repository: `dev`;
- semantic acceptance: `tester`;
- graph state: `graph`;
- stale installed Docara skill: forbidden.

Action-gate preflight passed for reversible repository work. Release-context,
repo-hygiene, runtime-naming and pre-commit gates run again before checkpoints
and final handoff.

## Risks and stop conditions

Stop only for a decision that changes the already accepted front-matter/public
locale contract, an upstream Framework change, inability to preserve URLs or
project-owned files, an unbounded security/data-loss defect, external
credentials/live action, or inability to produce an immutable deterministic
artifact. Continue all independent safe batches before reporting a blocker.

## Recovery

- recovery source: this file;
- debt register: `source/workflow/2026-08-02-docara-architecture-documentation-debt-register.md`;
- evidence index: `source/workflow/evidence/2026-08-02-docara-r1c-semantic-correction/INDEX.md`;
- rollback: revert logical R1-C checkpoint commits from input revision;
- old R1 ZIP remains immutable and classified `superseded_after_audit`.

## Current state

- current batch: R1-C.1 truthful governance;
- remaining debt count: 12;
- next safe action: commit correction state, then inventory production callers
  of language-pack and locale configuration surfaces.

## Nonclaims

No new candidate exists yet. Local release readiness, release, merge, push,
tag, publication, production and full non-Russian translation are not claimed.
