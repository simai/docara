# Workflow: local SIMAI Framework runtime for Docara

Date: 2026-08-07

Status: `local_framework_runtime_ready_for_independent_audit`

Current stage: `docara.stage.lfr.local_framework_runtime`

Current batch: `docara.batch.lfr.integrated_retest`

Current next action: `independent_local_framework_runtime_audit`

Next roadmap goal: `docara.stage.lfr.local_framework_runtime` (`audit_pending`, authorized=`true`)

## Goal

Publish every SIMAI Framework asset required by Docara from one immutable,
hash-bound local projection so a transient CDN failure cannot leave icons or
interactive Smart elements uninitialized. After repository acceptance, rebuild
and atomically switch the explicitly authorized local validation site
`docara-new.test`.

## Done When

- Core, Utility, Inter, Material Symbols, Framework bootstrap JavaScript,
  required webpack chunks and admitted Smart assets are local and hash-bound.
- The generated HTML contains no external runtime/font asset dependency.
- A changed, missing, symlinked, hardlinked or path-escaping projected file is
  rejected before render/publication.
- Existing typography, Smart ABI, renderer/Gateway/LayoutComposer/PageBuilder
  and default public HTML semantics remain unchanged apart from local asset
  URLs and their build receipts.
- Focused/full tests, static verification, full/full/single determinism,
  package/fresh-consumer checks and browser tests with jsDelivr blocked pass.
- `docara-new.test` is backed up, switched atomically to the verified candidate,
  and passes route/static/browser smoke with a documented rollback target.

## Context

- Entry HEAD: `1e1e093109cbd8956cbe9693cc2fd98dd8e1759f`.
- Accepted typography product candidate: `d6e511c1ed73aa4d1a91b666340549a28ec1312f`.
- Framework distribution source: `simai/ui@d1daa951dd08b94a9f209fd9f31a78d2b3779563`.
- Current failure: a first-load jsDelivr failure for `smart-base.js` leaves
  `sf-icon` undefined and all icon hosts empty; reload succeeds only when the
  external network request succeeds.
- Owner repository bytes are consumed read-only; no external owner checkout is
  modified.

## Constraints And Risks

- Preserve one Markdown -> typed IR -> renderer registry -> Smart gateway ->
  LayoutComposer -> PageBuilder path.
- No handwritten replacement Framework runtime, component-ID branch, second
  renderer or remote fallback hidden behind a local URL.
- Runtime files must retain their distribution-relative paths because webpack
  resolves chunks from `window.sfPath`.
- No writes to `docara.test`; no merge, push, tag, release or publication.
- Site cutover requires backup, verified candidate digest and rollback proof.

## Batch Plan

| Batch | Goal | Work | Verification | Status |
| --- | --- | --- | --- | --- |
| 1 | Freeze closure | Record exact owner files/hashes and local projection contract | source/hash/closure tests | complete |
| 2 | Local runtime | Extend lock, repository, planner and publisher | focused unit/security tests | complete |
| 3 | Integrated QA | Full builds, static, package/consumer and offline browser | deterministic ledgers and browser evidence | complete |
| 4 | Validation site | Backup and atomic `docara-new.test` cutover | HTTP/static/browser/rollback smoke | complete |
| 5 | Handoff | Synchronize workflow/spec/graph/evidence | context/graph/diff/clean status | complete |
| 6 | Standalone icon correction | Bind the local outlined font to both custom-element and direct icon hosts | red/green contract, browser glyph metrics, full retest and atomic recutover | complete |
| 7 | Complete icon families | Admit exact official Rounded and Sharp variable fonts and publish all documented families locally | hash-bound projection, full/package/browser retest and atomic recutover | complete |

## Progress

### Batches 1-5

- Status: complete; independent audit pending.
- Product candidate: `ad147f32ec9854e5bb97ea635b349b3ce803ed43`.
- Exact owner projection: 117 files from `simai/ui@d1daa951…`, packet
  `790b8014…`, manifest `8c917f69…`; source fidelity is 117/117.
- Build: two full roots and representative Icon single are byte-identical at
  655 files, digest `b6e0d9a7…`; static is 266 HTML / 36,128 references /
  broken=0.
- Package: two 1,000-file artifacts reproduce ZIP `1cfd3acf…`; both repository
  verifiers pass.
- Browser: Outlined, Rounded and Sharp standalone/custom icons use their exact
  local canonical families; former ligature strings now occupy their 16/20/24px
  hosts, including the filled Rounded state. Overflow and external runtime
  request invariants remain clean with zero console warnings/errors.
- Validation site: `docara-new.test` was atomically switched to the exact
  build; the former tree is retained under the rollback name documented in
  the evidence index.

## Final Result

- Result: local Framework runtime and fonts are content-addressed and served
  by Docara itself; `docara-new.test` uses the exact corrected verified tree.
- Verification: see
  `source/workflow/evidence/2026-08-07-docara-local-framework-runtime/INDEX.md`.
- Remaining: independent read-only reverse-outcome audit only.
- Follow-up: no release, push or production deployment is authorized.
