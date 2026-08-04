# B4 second external-dependency audit

Date: 2026-08-04
Status: `external_dependency_still_unaccepted`
Docara handoff input: `06fca98e615025f802a133b5a93642218dbc5fd1`
Docara product candidate: `ccb076a89535954022ca89eb70b84d6c81d80de3`

## Purpose

This is the second consecutive Goal-turn audit of the same B4 stop condition.
It checks whether the required Framework wave became independently accepted;
it does not reinterpret raw source presence as acceptance.

## Exact owner inventory

The accepted ABI host-adapter commit
`b3cdff87563ff78e7eddf044048a4b298fc69036` does contain starter skeletons:

| Artifact | SHA-256 from `git show b3cdff87:<path>` |
| --- | --- |
| `input/manifest.json` | `787699c78a24bd8e4f8c242649258e0714be8d3430a7eae820a91d809cfe1272` |
| `input/template/default.php` | `468b24f54bcf1c4f354e9cf2d1382832f50f4b5034f01d2027e99e98cc0ca416` |
| `dropdown/manifest.json` | `b59a5e176f331a42e86c2b71d1d428513211f516a0ea75a9366d99200713a47c` |
| `dropdown/template/default.php` | `a392823ef87a647d0416816ec722977de709a8c2b6699b082818fb09c8d00c7d` |
| `checkbox/manifest.json` | `684af313e67121fd129bc24bf47ffc11a2bf2a4a23f11893b760621687d3be82` |
| `checkbox/template/default.php` | `961b1a0c6bfd25fb37a930892b7431a15c90258159dcba7e98bbb3b896ec533a` |

Their existence does not satisfy Goal B. The independent portable-ABI handoff
for `b3cdff87…` proves the neutral ABI and one unchanged `fixture.notice`
cross-host artifact. It does not claim component-level acceptance for input,
dropdown or checkbox.

## Canonical compatibility verdict

Read-only compatibility state:
`04da80ffa3f118cc1e03a63a1fcc1b7d2d0b931f`.

The generated Smart Component Atlas reports the same outcome for all three:

| Component | Readiness | Public contract | Compatibility | Gaps |
| --- | --- | --- | --- | --- |
| `sf-input` | `mapped_with_gaps` | `source_extracted_unreviewed` | `published_distribution_differs_from_reproducible_candidate` | `machine_contract_missing`, `ui_doc_component_reference_missing` |
| `sf-dropdown` | `mapped_with_gaps` | `source_extracted_unreviewed` | `published_distribution_differs_from_reproducible_candidate` | `machine_contract_missing`, `ui_doc_component_reference_missing` |
| `sf-checkbox` | `mapped_with_gaps` | `source_extracted_unreviewed` | `published_distribution_differs_from_reproducible_candidate` | `machine_contract_missing`, `ui_doc_component_reference_missing` |

The external atlas additionally records missing/different generated assets.
There is no component-level immutable accepted manifest/view/preset/template/
assets packet and no three-component cross-host acceptance with empty
warnings/stderr.

## Decision

B4 remains blocked. Vendoring the starter skeletons now would promote
unreviewed/mismatched artifacts under Framework-owned IDs and violate the Goal
B owner/acceptance gate. No Docara runtime, external repository or site was
changed. Goal C remains unauthorized.

Unblock remains exactly:

1. Framework owner publishes one immutable packet for the three artifacts;
2. independent acceptance binds manifests, optional views/presets/slots,
   templates, assets and hydration to exact hashes;
3. cross-host evidence is green with empty warnings/stderr;
4. Docara resumes B4 through the existing provider/Gateway and repeats affected
   B5 checks.
