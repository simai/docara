# Context: terminal standalone Docara v2

## Identity boundary

`/Users/rim/Documents/GitHub/docara` is the standalone static Docara v2
generator and product (`simai/docara`). It is not the separate Larena backend
package at `/Users/rim/Documents/GitHub/larena-workspace/packages/docara`
(`larena/docara`).

## Revision boundary

- repository revision at governance intake:
  `d514c536b8cf379b90a15be8aaf14bcb85b06f7e`;
- unchanged product/runtime baseline:
  `c5f6140a85435913a9d5f7389bdf34967d4d70f8`;
- the committed revision and product baseline remain distinct identities;
- the onboarding package was untracked at intake and therefore explained the
  otherwise clean tracked worktree.

The repository revision is not a new release candidate. The product baseline
is not a tag or publication authorization.

## Accepted terminal outcome

- Goals 1-3, Goals A-C and Surface/Hero Goals S1-S3 are independently accepted;
- `ui-doc` is a content-only Docara v2 consumer on clean `main`;
- Framework, Docara and `ui-doc` are converged on their verified `main` refs;
- legacy `docara-template` and `docara-mix` repositories are archived with
  rollback evidence;
- there is no active implementation goal, track, stage or batch.

On 2026-08-25 a separately authorized bounded workflow added reusable project
examples and report-only translation tracking to the current working tree,
adopted them in `ui-doc`, and synchronized the English Docara skill source.
That workflow is complete but uncommitted and unpublished; it does not change
the repository revision or product baseline identifiers above.

## Current sources of truth

1. `source/workflow/ACTIVE.md`;
2. `source/workflow/2026-08-09-docara-terminal-governance-sync.md`;
3. `source/workflow/2026-08-09-docara-legacy-repositories-retirement.md`;
4. `source/workflow/evidence/2026-08-09-docara-legacy-repositories-retirement/INDEX.md`;
5. `graph/graph.json`, related `graph/specs/` and generated context;
6. `docs/specification/README.md` and the implementation roadmap;
7. actual current Git state and fresh task-specific checks.
8. `source/workflow/2026-08-25-shared-examples-translation-tracking.md` and its
   verification summary for the uncommitted authoring lifecycle.

Historical workflows, evidence and the archived
`source/handoff/docara-unified-architecture/` package preserve chronology but
are not executable routing.

## Safety boundary

The only next lifecycle entry is `explicit_user_decision`. Before any version,
tag, release, package publication or deploy, a separate user decision must name
the action, exact revision/artifact, scope and required gates. Until then every
authorization flag remains false.

The canonical English Docara skill source now describes the current
architecture and completed Skill Sync Gate. Installation or activation is a
separate operation. Do not infer live/release permission from accepted
historical readiness evidence.
