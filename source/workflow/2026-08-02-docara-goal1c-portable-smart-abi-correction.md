# Goal 1-C — portable SF5 Smart ABI correction

Date: 2026-08-02
Status: `ready_for_independent_audit`
Project mode: `productization`
Repository: `/Users/rim/Documents/GitHub/docara-unified`
Branch: `codex/docara-unified-architecture`
Input revision: `531ccdbb3493a3109bfabe91bb3f2e00a17447ce`
Rejected implementation candidate: `34496d49ce366f1108d2aed37c0adda35f6e5f58`
Rollback boundary: revert correction commits in reverse order; do not reset or rewrite history
Parent roadmap: `source/workflow/2026-08-02-docara-extensible-lego-architecture-plan.md`
Evidence index: `source/workflow/evidence/2026-08-02-docara-goal1c-portable-smart-abi-correction/INDEX.md`
Resume evidence: `source/workflow/evidence/2026-08-02-docara-goal1c-portable-smart-abi-resume/INDEX.md`

## Goal

Restore a single portable Smart artifact ABI that uses the exact source-pinned
SF5 array template context under both SF5 and Docara, remove the remaining
Goal 1 component-ID dependencies from active runtime/search/admission, and
produce a truthful `audit_pending` candidate without starting Goal 2.

The only public pipeline remains:

```text
Markdown -> typed Document IR -> DocumentRendererRegistry
-> SmartComponentGateway -> LayoutComposer -> PageBuilder
```

## Audit findings accepted as correction input

1. The previous cross-host proof is rejected. The tracked `fixture.notice`
   template expected a Docara-only object `$view`, while pinned SF5 passes
   arrays. Its claimed HTML hash therefore does not prove portability.
2. `RegisteredTemplateStrategy`, `TrustedTemplateRegistry` and
   `SmartTemplateContext` do not yet expose the pinned SF5 template ABI.
3. `PortableSearchTextExtractor`, the available `DocumentParser` path and
   `FrameworkConsumerPolicy` still contain central component-ID knowledge.
4. The previous integrated evidence labels demonstrator-result hashes as
   Alert/Button component page hashes and overstates acceptance.
5. `RegionCompositionResolver` remains a Goal 2 Design Registry boundary. It
   is neither fixed nor claimed by Goal 1-C.

Historical Goal 1 evidence is preserved unchanged as an audit trail. New
correction evidence supersedes its rejected compatibility/readiness claims.

## Done When

- one unchanged tracked artifact directory renders non-empty expected
  title/text through Docara and exact SF5 with warnings/errors zero;
- portable templates receive the pinned SF5 array variables and shapes;
- views, presets, props, slots/children, assets, hydration and truthful host
  context survive provider -> invocation -> strategy -> artifact;
- Framework admission is artifact/lock/provider data, not a central component
  map, and a newly locked compatible fixture needs no component-specific
  engine edit;
- active Goal 1 runtime/search/admission has no list or switch for
  `ui.alert`, `ui.button` or another component ID;
- security gates remain fail closed and no authored path can select PHP;
- full/single equality, two-build determinism, public parity, static checks and
  browser smoke pass at one exact correction revision;
- docs, graph, workflow and handoff describe only proven outcomes and leave
  Goal 1 `audit_pending`.

## Scope boundaries

Allowed: Goal 1 runtime, tracked contract/fixtures, starter, tests,
specification, public developer docs, graph, workflow, evidence and handoff.

Forbidden: Goal 2 Design Registry/preview implementation, Goal 3 SDK/MCP,
external Framework writes, `docara.test`, `docara-new.test`, merge, push, tag,
release or deploy, and deletion without parity plus zero-reference evidence.

## Execution batches

| Batch | Outcome | Verification | Status |
| --- | --- | --- | --- |
| G1C.0 | recovery, rejected-claim register, exact SF5 ABI inventory | git/source pin checks, action gates | pass |
| G1C.1 | array-compatible portable context and exact cross-host harness | same artifact, two hosts, stderr/HTML/hash assertions | pass on exact adapter `b3cdff87…` |
| G1C.2 | provider/context/admission data-driven completion | provider/lock/gateway/template positive and negative tests | pass within Docara |
| G1C.3 | active search/parser ID-list retirement | focused search/parser tests and structural scan | pass |
| G1C.4 | integrated builds, parity, security, browser and governance | complete acceptance matrix | pass; independent audit pending |

## Gates and assumptions

- Federation preflight at 2026-08-02T16:14:04Z passed repository hygiene,
  pre-commit, release-context and runtime-naming gates. The resolver selected
  disabled `docara`; repository sources plus `teamlead`, `dev`, `sf5`,
  `tester`, `docs` and `graph` are the fallback authority.
- Work mode is `single_agent`; no subagents are authorized.
- Pinned upstream is read-only and may be inspected with `git show`; no
  external checkout is mutated.
- If pinned SF5 cannot represent required portable context without a
  Docara-only dialect, stop and request a cross-repository contract decision.

## Current state

Goal 1 implementation is complete and remains unaccepted pending an independent
reverse-outcome audit. Exact adapter pin
`b3cdff87563ff78e7eddf044048a4b298fc69036` preserves the normalized array
context and produces byte-identical cross-host HTML for the unchanged fixture.
The old `d6f90bba…` failure remains historical evidence only. Goal 2 remains
unstarted until the audit accepts this candidate.
