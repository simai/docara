# Goal 3D: Smart SDK schema and provenance correction

Date: 2026-08-04
Status: in_progress
Branch: `codex/docara-unified-architecture`
Input HEAD: `f39cd3c61b0510d3092831001e7eb88ac5c459d1`
Input product candidate: `1dee6d19e2d9a6c35402b3552f3f5c8c366317b6`
Parent Goal 3 candidate: `2a7237bc59265d976b6871cb637e7ae67ca2c00b`

## Objective

Make the public Smart SDK schema and every effective Smart provenance record
describe the already accepted neutral Portable Smart ABI:
`sf.smart_artifact_abi`, schema version `1.0.0`, compatibility id
`sf-smart-artifact-abi-v1`. The historical `sf5.smart.artifact.v1` name may
appear only as `storage_compatibility_alias`; provider adapters and template ABI
remain separate implementation facts.

## Recovery and ownership

- Repository specification, graph, LEGO plan and handoff are authoritative.
- Federation routing selected the disabled stale Docara skill. It remains
  forbidden by the task; repository contracts plus the `dev` fallback govern
  this bounded correction. This is a recorded routing gap, not permission to
  enable or use the stale skill.
- Preserve without modification or staging the user-owned untracked proposal
  `source/workflow/2026-08-04-docara-content-design-settings-track.md`.
- Do not touch external Framework repositories, `docara.test` or
  `docara-new.test`.

## Batches

1. **G3D.0 RED and contract freeze** — reproduce the public schema/scaffold
   mismatch and the inconsistent builtin provenance matrix.
2. **G3D.1 authoritative schema** — expose one portable manifest schema that
   validates the exact scaffold output; retire the old public legacy schema
   name only after zero-reference evidence.
3. **G3D.2 neutral provenance** — normalize builtin, Framework and project
   artifact identities at provider/artifact boundaries without component-ID
   logic.
4. **G3D.3 parity and security** — bind CLI human/JSON/MCP results to the same
   application services and preserve all root, path, dry-run and hash-bound
   apply guards.
5. **G3D.4 integrated retest and handoff** — rerun focused/full, public,
   package, consumer and browser matrices; synchronize specification, graph and
   handoff at one exact candidate and stop at independent audit.

## Done when

- `schema smart` validates an unchanged `project.audit-card` scaffold manifest.
- Six bundled/Framework Smart definitions and one project fixture expose the
  same neutral identity, with honest provider adapter, template ABI and storage
  alias fields.
- CLI human/JSON and optional MCP return semantically identical schema and
  inspect results from the same application service.
- No component-ID switch/list, second renderer, Gateway, composer or
  PageBuilder is introduced, and all existing write/security boundaries remain
  fail-closed.
- Fresh exact evidence covers focused/full tests, deterministic public builds,
  full/single equality, package/consumer and browser regression.
- Canonical graph, generated context, workflow and handoff say only
  `goal3_ready_for_independent_audit`; no self-acceptance or release action.

## Stop conditions

Stop only if this correction requires a new external owner ABI decision, a
second public dialect, weaker validation/write security, an external write, or
overlaps user changes. Local defects and stale evidence are not blockers.

## Rollback

The immutable rollback boundary is input HEAD
`f39cd3c61b0510d3092831001e7eb88ac5c459d1`. Each green bounded batch is a
separate commit. Historical evidence is retained; rollback does not require
rewriting history or touching the user proposal.
