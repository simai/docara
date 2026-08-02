# Goal 1-D — generic Smart view selection correction

Date: 2026-08-02
Status: `correction_in_progress`
Repository: `/Users/rim/Documents/GitHub/docara-unified`
Branch: `codex/docara-unified-architecture`
Input revision: `c5ea85f8d25deff99b671486fdc4d1e820a86491`
Rollback boundary: revert Goal 1-D commits in reverse order; do not rewrite history
Parent goal: `source/workflow/2026-08-02-docara-goal1-portable-smart-runtime.md`
Evidence index: `source/workflow/evidence/2026-08-02-docara-goal1d-generic-smart-view-correction/INDEX.md`

## Audit finding

The previous structural regression was too narrow. Active production code in
`DeclarativePageCompiler::defaultCompositeView()` selected a Smart view only
when the component ID was `docara.brand`. That contradicted Goal 1 even though
the Gateway and renderer themselves were generic.

## Outcome

Remove component-specific selection from compiler/compose/runtime. Unspecified
view plus a named preset must resolve through the existing Smart artifact
contract: an artifact preset may select its registered view, while explicit
view remains authoritative. Branding bindings may provide their mode as a
named preset, but the compiler must not know which Smart consumes it. The same
mechanism must work for a project/provider-local fixture without an engine edit.

The only public pipeline remains:

```text
Markdown -> typed Document IR -> DocumentRendererRegistry
-> SmartComponentGateway -> LayoutComposer -> PageBuilder
```

## Batches

| Batch | Result | Status |
| --- | --- | --- |
| G1D.0 | recovery, false-green registration and rollback | pass |
| G1D.1 | generic preset/view resolution across product and portable providers | in progress |
| G1D.2 | broad structural and behavioral regressions | pending |
| G1D.3 | focused/full/build/browser/security retest | pending |
| G1D.4 | graph/spec/handoff and audit-pending evidence | pending |

## Boundaries

- Goal 2 `RegionCompositionResolver` allowlist remains documented debt and is
  excluded from this correction.
- No new manifest dialect, second Gateway/renderer/PageBuilder or authored
  executable path is allowed.
- External repositories and live sites are read-only/out of scope.
- Goal 1 cannot return to `ready_for_independent_audit` until the complete
  retest is green.
