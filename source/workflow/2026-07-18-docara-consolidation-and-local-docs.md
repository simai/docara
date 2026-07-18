# Workflow: Docara consolidation and local documentation

Date: 2026-07-18
Status: in-progress, exact-candidate verification and local publication active
Process model: `general_delivery`
Cycle: `coding_batch_cycle`
Current state: `tests_recorded`
Target state: `evidence_recorded`
Track ID: docara-consolidation-and-local-docs
Memory decision: `skip`
Memory reason: project-local workflow and project memory are authoritative;
mutable facts are reverified from the exact repository and runtime before writes.

## Track

Track ID: docara-consolidation-and-local-docs

Consolidate the Docara ecosystem around one canonical generator and one
frontend contract, prove that the portable user build remains Node-free, and
build the Docara documentation with that same engine for the local ServBay
site at `https://docara.test/`.

## Final Outcome

`simai/docara` is the single source of truth for the generator, schemas,
starter, examples, documentation-site contract, and template export. Active
Docara consumers use Vite for asset development; a portable site build still
requires PHP only. The new Russian documentation is built by Docara itself and
is served from `/Users/rim/Sites/docara.test/build_production` with verified
backup and rollback. `docara-mix` is archived, not deleted, only after all
active default-branch consumers have migrated and an independent retirement
gate passes.

## Current Position

- The clean implementation worktree is based on the independently accepted
  portable-contract candidate `f51debe3e1def82d2dcf611bf820c4517cd65f8f`.
- Batch 0 is complete.
- Batches 1-3 and 5 are implemented. The canonical owner, generated-mirror
  contract, Markdown authoring surface, and PHP-only portable build have fresh
  focused or end-to-end evidence.
- The documentation tree has been freshly rebuilt: 39 pages, 398 verified
  quoted local references, and two byte-identical production builds with tree
  hash `abec753f99654bbe0a57c6b4c117c25fc71a5e9e131a9778059e0ace2f7c6f9a`.
- Five maintained consumers have isolated Vite migration branches and passed
  exact Yarn 1.22.22 installation plus production builds against the candidate
  Docara engine. They remain merge-blocked until this Docara candidate has an
  exact release and their Composer locks are updated to it.
- `ui-doc-core` remains a legacy retirement candidate and was not declared a
  migrated consumer. Active-default-branch zero-reference acceptance has not
  passed.
- `docara-mix` retirement is **NOT READY**.
- Tabs remain an exact Framework-contract blocker and must not be emulated by a
  local substitute.

## Current Goal

Execute the requested consolidation points in order. Their original numbering
from the user's approved list is preserved here:

2. Make `docara` the only owner of the generator, schemas, starter, and
   examples.
3. Move the canonical `docara-template` content into `docara`; if the external
   repository remains useful, make it a generated and verified mirror only.
4. Establish Vite as the only supported asset-development tool for active
   Docara consumers.
5. Keep the portable end-user build completely free of a Node.js requirement.
6. Remove active `docara-mix` consumption and archive the repository only
   after zero-reference and independent retirement acceptance.
7. Expand the Markdown/Simai Framework authoring surface for alert, button,
   code, steps, table, and card; hold tabs until its exact Framework contract is
   repaired and accepted.

After points 2-7, build and locally publish the new Docara documentation at
   `https://docara.test/` through the same engine.

## Done When

- `docara` contains and tests the canonical schemas, starter, examples, and
  template-export contract; no second hand-maintained template source exists.
- The external `docara-template`, if retained, is reproducibly generated from
  the exact `docara` revision and a verifier rejects drift.
- Active consumer repositories have clean, committed Vite migrations, and
  clean-install, build, and bounded watch/dev checks pass.
- A fresh repository scan of active default branches finds no dependency or
  build reference to `docara-mix`.
- `docara-mix` has a rollback/export record, independent retirement `PASS`, and
  is archived rather than deleted. Until then the result explicitly says
  `retirement NOT READY`.
- A disposable portable project builds with PHP and Composer only; Node, npm,
  Yarn, pnpm, Mix, webpack, `main`, and `latest` are not runtime requirements.
- Markdown examples use real Simai Framework contracts or honest semantic
  HTML/utility recipes as classified in
  `framework-component-classification.md`; no fake Smart-component is added.
- The documentation tree is built from a clean exact candidate, the output is
  deterministic, and internal links/assets pass.
- `/Users/rim/Sites/docara.test/build_production` is replaced only after a
  command-specific runtime gate and timestamped backup; rollback is tested or
  mechanically verified.
- Browser acceptance covers representative desktop/mobile pages, light/dark
  theme behavior, navigation, component examples, and absence of console or
  asset errors.
- Exact-candidate tester and Human-Centered Simplicity verdicts pass without a
  release, production-readiness, or broader ecosystem-readiness claim.

## Stages

- completed: establish canonical Docara ownership and portable contracts;
- completed: implement and verify the generated mirror and Markdown surface;
- completed: prepare and build-test isolated Vite consumer migrations;
- in progress: freeze exact candidate, independent acceptance, and local
  ServBay publication;
- blocked by future release/default-branch gates: publish mirror, merge
  consumers, and retire `docara-mix`.

## Batches

- completed: Batch 0 isolation, inventory, workflow, and rollback boundary;
- completed: Batches 1-3 canonical engine, docs, and mirror implementation;
- completed: Batch 4 five maintained consumer build proofs;
- completed: Batch 5 PHP-only portable proof;
- blocked: Batch 6 active-default zero-reference and `docara-mix` retirement;
- in progress: Batch 7 exact acceptance and local publication.

## Batch Matrix

| Batch | Result | Verification | Status |
| --- | --- | --- | --- |
| 0 | Clean base, workflow, ownership/inventory, local-runtime preflight, rollback plan | exact SHA, clean isolation, process/project checks, HTTP preflight | completed |
| 1 | Canonical owner/starter/schema work plus semantic component rendering; incident correction | focused PHP tests, schema negatives, template-export determinism | implemented; exact commit pending |
| 2 | Russian documentation tree and honest Framework examples | clean docs build, links/assets, content/HCS review | implemented; fresh deterministic build pass |
| 3 | Generated external `docara-template` mirror | fail-closed exporter/verifier tests, exact release/tag gate, deterministic fixture export | implemented in Docara; external publication blocked until exact release |
| 4 | Maintained direct consumers migrated from Mix/webpack to Vite | exact Yarn install and production build per isolated branch | five maintained consumers build-pass; merge blocked by unreleased candidate; legacy `ui-doc-core` not retired |
| 5 | Node-free portable distribution proof | PHP-only disposable build with Node unavailable, deterministic output | complete; fresh proof pass |
| 6 | Active-branch zero-reference proof and `docara-mix` retirement | fresh remote/default-branch scan, export/rollback, independent retirement verdict | **NOT READY** |
| 7 | Atomic local publication and browser acceptance | runtime gate, backup/swap/rollback evidence, desktop/mobile/theme smoke | pending |

## Requirement Matrix

| Requirement | Evidence | Current status |
| --- | --- | --- |
| Canonical Docara owner | source, schemas, starter, exporter, focused tests | implemented; exact commit pending |
| Generated template mirror | exporter/verifier, exact release/tag gate, fixture hashes | implementation and tests pass; external mirror publication awaits release |
| Vite-only development contract | isolated consumer branches and clean builds | five maintained consumers build-pass; merge awaits exact Docara release; legacy retirement pending |
| Node-free portable build | isolated PHP-only build log | complete; 3 pages, 7 local references, deterministic hash pass |
| Honest component catalogue | Framework classification and renderer tests | alert/button/card/steps/code/table pass; tabs blocked |
| Documentation authored | `docs/site/**` | complete for bounded Russian v1 corpus |
| Documentation build | exact candidate build manifest | 39 HTML, 398 links checked, deterministic hash pass |
| `docara-mix` retirement | zero active refs plus independent verdict | **NOT READY** |
| Local ServBay publication | backup/swap/rollback and browser evidence | pending |
| Independent acceptance | exact candidate verdicts | pending |

## Direct Consumer Inventory

The bounded scan found six unique direct `laravel-mix-docara` consumers:

- `simai-env/docara`;
- `publications`;
- `sf4-doc`;
- `ui-doc-core`;
- `sitepack/docara`;
- `ui-doc` through its nested/core relationship.

`docara-template` is an indirect generated-site consumer. `ui-doc-template` is
related legacy infrastructure but is not itself a direct `docara-mix`
consumer. Retirement is determined from fresh active default-branch evidence,
not only from local migration branches.

## Framework Contract

- User-facing text says **Simai Framework**; `sf5` is not introduced as a
  product name or code prefix.
- `ui.alert` and `ui.button` use existing backend-to-Smart contracts.
- Code, steps, table, and card use semantic HTML plus existing Framework
  utilities where no suitable Smart-component contract exists.
- Tabs are blocked: the backend manifest is absent and the current `cl-tabs`
  CSS/JS asset contract is incomplete. No local imitation is allowed.
- Exact detail and the safe fallback recipes live in
  `source/workflow/evidence/2026-07-18-docara-consolidation-and-local-docs/framework-component-classification.md`.

## Safe-Write Boundaries

### Allowed

- The clean Docara worktree:
  `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consolidation`.
- Dedicated clean consumer worktrees under
  `/Users/rim/Documents/GitHub/larena-workspace/source/worktrees/docara-consumer-*`.
- A dedicated clean generated-mirror worktree created for
  `docara-template` after the exporter is accepted.
- Disposable dependencies, builds, and browser profiles under `/private/tmp`.
- The single served-output path
  `/Users/rim/Sites/docara.test/build_production`, but only in Batch 7 after a
  command-specific ops gate and a timestamped backup.

### Forbidden

- Any write, cleanup, reset, checkout, stash, or history operation in the
  user's dirty recovery worktrees:
  `/Users/rim/Documents/GitHub/docara`, `docara-template`, `docara-mix`, and
  `ui-doc`.
- Reading, printing, copying, or committing `.env`, credentials, cookies,
  tokens, keys, or other secrets.
- Deleting or overwriting `/Users/rim/Sites/docara.test` source, `.env`, vendor,
  or user data.
- `git reset --hard`, `git clean`, force push, history rewrite, direct default-
  branch mutation without a gate, or destructive repository deletion.
- Archiving `docara-mix` before active-default-branch zero-reference proof and
  independent retirement acceptance.
- Inventing local Framework manifests, Smart-components, or moving
  `main`/`latest` runtime references to conceal a missing contract.
- Claiming release, production, or ecosystem readiness from local builds.

## Gates And Stop Conditions

- Re-run repo hygiene, secret/source policy, process, and exact-candidate tests
  after incident recovery and before commit.
- A failed test opens a correction batch; it never lowers acceptance scope.
- Stop any individual consumer migration if a clean install would overwrite
  consumer-owned source or if the owner contract is ambiguous; record the
  blocker and continue with safe independent consumers.
- Stop retirement if any active default branch, generated package, CI path, or
  documented install path still requires `docara-mix`.
- Stop local publication if backup, rollback, candidate hash, TLS request, or
  browser smoke cannot be demonstrated.
- Stop tabs work until the Simai Framework owner supplies an exact backend
  manifest and coherent CSS/JS runtime contract.

## Local Publication Contract

1. Build the candidate outside the served path and record its manifest/hashes.
2. Run a command-specific ops/action gate for the local ServBay write.
3. Move the current `build_production` to a timestamped backup under the local
   site backup area.
4. Atomically move the candidate into `build_production`.
5. Verify `https://docara.test/`, representative routes, assets, themes, and
   responsive layouts.
6. On any failure, restore the timestamped backup and preserve failure
   evidence. Never touch the local `.env`.

## Evidence

Store concise, reviewable evidence under:

`source/workflow/evidence/2026-07-18-docara-consolidation-and-local-docs/`

Each batch records exact revisions, commands, exit codes, changed paths,
semantic outcome, and limitations. Dependency trees, generated production
output, browser profiles, and secrets remain outside Git.

## Human-Centered Simplicity

- quality_controls: `[human_centered_simplicity]`
- simplicity_review:
  `source/workflow/evidence/2026-07-18-docara-consolidation-and-local-docs/hcs/human-centered-simplicity-review.json`
- The review must prefer one canonical source, a small authoring surface,
  visible blockers, and examples that explain user intent rather than build
  internals.

## Incident Recovery

The bounded recovery record is
`source/workflow/evidence/2026-07-18-docara-consolidation-and-local-docs/incident-recovery.md`.
Tracked deletions were restored path-by-path from exact HEAD. The untracked
current-workflow artifacts are reconstructed from the accepted baseline and
conversation evidence. Implementation-owned untracked core tests were
reconstructed by their owning actor. No dirty user repository was used as a
recovery source.

## Next Safe Step

Freeze and commit the exact Docara candidate, complete independent acceptance,
then perform the gated local `docara.test` backup/swap and browser acceptance.
Consumer default-branch publication, a Docara release, external template-mirror
sync, and remote `docara-mix` retirement remain explicitly outside this local
publication batch.
