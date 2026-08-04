# B4 — Framework useful-component wave gate

Date: 2026-08-04
Status: `external_dependency_pending`

## Required owner inputs

Goal B requires useful, non-gallery scenarios using independently accepted,
exact-pinned portable artifacts for at least:

- `ui.input`;
- `ui.dropdown`;
- `ui.checkbox`.

The current repository does not contain those accepted artifacts. The only
tracked Framework-owned public ABI pin is:

```text
owner repository: simai/bx-simai.main
source revision: b3cdff87563ff78e7eddf044048a4b298fc69036
contract: sf.smart_artifact_abi / 1.0.0 / sf-smart-artifact-abi-v1
```

Its admitted compatibility artifacts in Docara are only `ui.alert` and
`ui.button`.

## Reproducible inventory

```text
find resources/smart -mindepth 1 -maxdepth 1 -type d | sort | rg 'ui\.'
resources/smart/ui.alert
resources/smart/ui.button

rg 'ui\.(input|dropdown|checkbox)' resources/contracts resources/smart resources/framework stubs tests
no artifact, immutable lock or cross-host acceptance record
```

`resources/contracts/sf5/smart/v1/source.json` pins only the neutral ABI
schemas/runtime and does not claim those component artifacts. Raw presence in
an external Framework distribution would not satisfy the acceptance gate.

## Stop condition and safe remainder

Docara cannot create local imitations or declare raw components supported:
that would violate owner namespace, exact pin and cross-host requirements.
No external repository write is authorized. B0-B3 and every independent B5
check may complete, but Goal B cannot become `ready_for_independent_audit`
until the exact accepted owner inputs arrive.

## Unblock contract

For each component, provide immutable manifest/view/preset/template/assets,
owner revision and hashes, plus independent cross-host HTML/assets/hydration
evidence with empty warnings/stderr. Docara can then admit the artifacts through
the existing Framework provider and rerun B4/B5 without a new renderer,
Gateway, registry, LayoutComposer or PageBuilder.

The second read-only blocker audit confirms that exact `b3cdff87…` starter
skeletons exist but remain unaccepted component inputs according to canonical
compatibility state. See
[B4-SECOND-BLOCKER-AUDIT.md](B4-SECOND-BLOCKER-AUDIT.md).
