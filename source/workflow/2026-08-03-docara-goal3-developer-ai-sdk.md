# Goal 3 — Developer/AI SDK, structured QA and optional MCP

Date: 2026-08-03
Status: `ready_for_independent_audit`
Project mode: `productization`
Input handoff: `adb27f1acde6dfa5f018f7b2e3c2f20b404a0ed2`
Accepted Goal 2 candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`
Audit marker: `019fc66b-a168-7ef2-9b42-d3fc10032434`
Branch: `codex/docara-unified-architecture`
Evidence: `source/workflow/evidence/2026-08-03-docara-goal3-developer-ai-sdk/INDEX.md`

## Outcome

A developer or agent can inspect, scaffold, validate, preview and test Docara
Smart/design artifacts through stable application services and CLI. Human and
JSON output share one result object. Optional MCP delegates to those services
and cannot broaden write authority. The accepted Markdown → typed IR → renderer
registry → Smart gateway → LayoutComposer → PageBuilder pipeline remains the
only production and preview path.

## Ownership and execution

- coordination: `teamlead`;
- implementation: `dev`;
- acceptance/evidence: `tester`;
- public developer documentation: `docs`;
- canonical state and derived context: `graph`;
- stale installed Docara skill: disabled and forbidden by user instruction;
- execution mode: `single_agent`; no subagents or external owner writes.

Federation routing selected the disabled legacy Docara owner. Repository graph,
specification, code and this accepted audit handoff therefore remain the domain
source of truth. The local reversible-work action gate passed with no blockers.

## G3.0 contract freeze

One immutable application result contract is used everywhere:

```text
docara.operation_result.v1
  operation, status, exit_code, subject
  data
  diagnostics[]
  provenance
```

Diagnostics are ordered and carry stable code, severity, message, safe relative
source path, JSON pointer and optional line/column, owner, provenance and an
actionable suggestion. Human formatters and JSON encoders consume the same
result; commands do not maintain parallel business logic.

Scaffold writes use `dry-run -> persisted hash-bound plan -> explicit apply`.
The plan binds operation, project identity, project/config inputs, every target
precondition and generated content hash. Apply rechecks all hashes, rejects
stale/unknown/duplicate/symlink/hardlink/traversal targets and publishes an
all-or-nothing project-local tree. Engine, lock, generated output and external
roots are always denied.

Optional MCP lives in `tools/mcp-docara/` as a PHP stdio JSON-RPC adapter. It is
not registered in normal consumer Composer dependencies, build runtime or
static output. Its handlers call the same read/plan/apply application services;
it owns no renderer, validator, registry, gateway, composer or PageBuilder.

## Batches

| Batch | Result | Status |
| --- | --- | --- |
| G3.0 | service/result/diagnostic/write/MCP contracts and golden fixtures | complete |
| G3.1 | doctor/list/inspect/schema application services and commands | complete |
| G3.2 | hash-bound Smart/design scaffold dry-run/apply | complete |
| G3.3 | validate/test orchestration over accepted services | complete |
| G3.4 | optional screenshot/a11y/visual QA orchestration | complete |
| G3.5 | optional local MCP delegation adapter | complete |
| G3.6 | docs/metadata/package/consumer/security/browser/graph handoff | complete; audit pending |

Each green batch gets focused tests, evidence and a recoverable commit, then the
next batch starts automatically.

## Done When

The Done When, required checks, non-goals and stop conditions are exactly the
accepted Goal 3 assignment and Goal 3 section of
`source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md`.
Executor completion means only `ready_for_independent_audit`; it never means
release, merge, tag, deploy or self-acceptance.

## Exact candidate

- product/docs/test source: `fface98a8d5fc572b0f2b30e58049981fa9fad3a`;
- deterministic audit ZIP: `f01c612a1abe21a4c658528210d621a18b926259643429be643f8962f49d2ea5`;
- release-manifest SHA-256: `b849d11b6140e0ea6d19a9fa9a25f1623c1cc5f10e0cace1728170647831d085`;
- exact Composer lock SHA-256: `eece48daca67e2b5cefa645b8a310bbc04da2cbecbcb3ab8060dd44381d65847`;
- planned package value `2.0.0-alpha3` is an unpublished audit parameter, not a release claim;
- next action: `independent_goal3_reverse_outcome_audit`.

## Rollback

The input handoff above is the Goal 3 rollback boundary. Each batch is a
separate commit. Scaffold/update tests operate only in disposable fixtures and
must prove exact rollback/no partial write before their batch is accepted.

## Stop conditions

Stop only for the assignment stop conditions: duplicated runtime semantics,
unsafe or non-atomic writes, mandatory visual/MCP dependencies, external owner
write, arbitrary executable path, security weakening, public parity failure or
overlapping user changes. Ordinary implementation/test defects are corrected
inside Goal 3.
