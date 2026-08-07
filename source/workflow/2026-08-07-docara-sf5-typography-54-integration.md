# Docara / SF5 5.4 typography integration

Date: 2026-08-07
Status: `typography_54_docara_ready_for_independent_audit`
Entry Docara HEAD: `3558ee8a41873a60e261e74655b1032d33bc9f52`
Exact Docara product candidate: `93a259f4a3c1691926c596dcdc8786e14206c72d`
Current stage: `docara.stage.t54.framework_typography`
Current batch: `docara.batch.t54.integrated_retest`
Current next action: `independent_sf5_typography54_docara_audit`
Next roadmap goal: `docara.stage.t54.framework_typography` (`audit_pending`, authorized=`true`)

## User outcome

Use the generated SF5 5.4 typography contract in Docara: semantic Heading,
Display, Label and Body Text roles, corrected title heights, compatible legacy
token aliases and heading spacing must be exercised by the real Docara build.

## Immutable owner chain

- `simai/ui-loader@41cc7e01a3616bf245bf054917033397684d2093`;
- `simai/ui-builder@367b3423f9707b850c6bef9476ab8d1ed44039e1`;
- generated `simai/ui@2b2e6ea88ac5f30dc0c90c61104506e6c9541108`;
- distribution rollback parent
  `d1daa951dd08b94a9f209fd9f31a78d2b3779563`;
- contract SHA-256
  `9180a5fe78a01890a8492b240d676b212ad73bccd6f7e9c8853984e4c590a7b3`;
- Core CSS SHA-256
  `9c235fbdd02246def279e710bd92ee3c6fed4c3dcdcc859f0ebf9ab73afb20af`;
- Utility Full CSS SHA-256
  `8918648d7ba8b4cf285bbc5d28b22240e05d5f2c47b48c68f9e8e9827e685709`;
- owner packet ZIP SHA-256
  `d20a0ce7d97bbb3e9502236fa3cb73acd7ca3d74b2559a3120ea3496a4c98dad`.

Two fresh `--no-local` source/builder waves reproduced the recorded 57-file
Core ledger `27d05026…` and 3,842-file Utility ledger `c6040fd8…` byte for byte.
This is a clean-room executor recheck, not an organizationally independent
owner verdict; the final Docara candidate remains audit-pending.

## Integration contract

The existing accepted Framework JavaScript and Smart pair remains unchanged.
Because the typography distribution candidate is intentionally not pushed or
tagged, Docara must not point jsDelivr at an unavailable commit. Instead the
Framework lock admits a local, content-addressed typography projection whose
bytes are copied from the exact generated distribution. The normal publisher
copies these files into `_docara/vendor/`; `FrameworkAssetPlanner` uses them in
place of only the two Core/Utility stylesheet URLs. There is no second
renderer, Gateway, registry, composer or PageBuilder.

The projection is optional for historical project locks; when present it is
validated fail-closed before rendering. Unknown paths, changed hashes,
symlinks, hardlinks and malformed identities remain rejected.

## Batches

1. T0 — preserve the exact clean-room rebuild and generated distribution
   candidate evidence.
2. T1 — add the hash-bound local typography projection and lock it in package,
   starter and docs/site configurations.
3. T2 — focused/full tests, two full builds, representative single equality,
   static verification and computed browser typography matrix.
4. T3 — package/fresh-consumer proof and synchronized graph/handoff evidence.

## Stop conditions and non-goals

Stop if the local projection differs from the generated artifact, weakens the
Framework lock or changes JS/component runtime behavior. Do not merge, push,
tag, publish, release or deploy. Do not write `docara.test` or
`docara-new.test` in this workflow.

## Rollback

Revert the Docara integration commits and restore the three Framework locks to
their entry bytes. The generated distribution candidate rolls back to
`d1daa951dd08b94a9f209fd9f31a78d2b3779563`.

## Result

The exact generated Core/Utility stylesheets and all seven referenced Inter
Variable subsets are admitted by one optional Framework-lock projection. Every
file is local, regular, single-link and SHA-256 checked before render. The
normal publisher and asset planner preserve deployment-base URLs; the accepted
Framework JavaScript/Smart runtime remains unchanged.

Focused security, full PHPUnit, deterministic public builds, static, browser,
package and same-lock consumer evidence are indexed in
`source/workflow/evidence/2026-08-07-docara-sf5-typography-54/INDEX.md`.
This executor result is ready for independent audit, not independently accepted.
