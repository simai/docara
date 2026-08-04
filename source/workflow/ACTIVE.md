# Active workflow: Docara unified architecture

Date: 2026-08-04
Status: Goal 3 ready for independent audit
Workflow ID: `2026-08-03-docara-goal3-developer-ai-sdk`
Graph goal: `docara.goal.unified`

## Source of truth

- start here: `source/handoff/docara-unified-architecture/START.md`;
- human specification: `docs/specification/README.md`;
- machine-readable state: `graph/graph.json` and `graph/specs/`;
- active recovery: `source/workflow/2026-08-04-docara-goal3d-smart-sdk-schema-provenance-correction.md`;
- parent Goal 3 recovery:
  `source/workflow/2026-08-03-docara-goal3-security-diagnostics-visual-correction.md`;
- fresh evidence: `source/workflow/evidence/2026-08-04-docara-goal3d-smart-sdk-schema-provenance/INDEX.md`;
- project-context freshness correction:
  `source/workflow/2026-08-03-docara-goal1d-project-context-correction.md`;
- rejected audit candidate: `c5ea85f8d25deff99b671486fdc4d1e820a86491`;
- corrected implementation candidate: `44acc1ff91233fa78140222fcb0589bf55b65ca0`;
- rejected Goal 2 candidate: `33a377758f12d02a34e50c2f4f6d2aa760cf678b`;
- corrected Goal 2 candidate: `39f1e3f6e97d7f8138e892b5884ba194cc889a7f`;
- branch: `codex/docara-unified-architecture`.

## Current state, stage and batch

- state: `goal3_ready_for_independent_audit`;
- goal: `docara.goal.unified`;
- stage: `docara.stage.g3.developer_sdk`;
- batch: `docara.batch.g3.developer_sdk`;
- candidate: `ba89bccf8e2ad11ed7c72d89d380e924aaaf17d8`;
- exact SF5 adapter: `b3cdff87563ff78e7eddf044048a4b298fc69036`;
- next action: `independent_goal3_reverse_outcome_audit`;
- Goal 1 and Goal 2 are independently accepted; release review remains unauthorized.

## Accepted pipeline

```text
Markdown -> typed Document IR -> NodeRendererRegistry
         -> SmartComponentGateway -> LayoutComposer -> PageBuilderResult
```

Every public route has one physical Markdown source. JSON controls composition,
`content/<locale>/lang.json` contains shared interface messages, and generated
IR/HTML remain disposable.

## Current result

The neutral `sf.smart_artifact_abi` v1 contract and the single Gateway/provider
runtime and Goal 2 DesignRegistry/preview were independently accepted. Goal 3
now provides one application-service/CLI/optional-MCP surface over that
production path. Its security, diagnostics and visual correction remain
intact. The cumulative candidate retains the exact SF5 UI-radius contract and
now makes `schema smart`, scaffold output and every provider's inspect
provenance agree on the neutral Portable Smart ABI. Combined evidence is ready
for an independent reverse-outcome audit; no release-review action is
authorized.

## Boundary

No mass rewrite, legacy deletion, default-branch merge, tag, release, public
deploy or readiness claim. The installed stale Docara skill is not a source of
truth for this track.
