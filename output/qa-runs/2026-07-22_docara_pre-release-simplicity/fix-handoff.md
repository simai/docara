# Fix handoff: clean new-major Docara

Source audit: `qa-report.md` in this directory.

Candidate audited: `9b1290bf547a8c87651704a9554be0acc881aebf`.

Status: correction required; no product fixes were applied by the audit.

## First bounded implementation batch

Goal: produce one coherent green candidate before deleting legacy code.

1. Integrate remote-only Windows safety commit `a913dce60...` without losing
   the 29 local commits.
2. Fix the structured `component_runtime.asset_plan` contract and its failing
   Smart asset-scoping test.
3. Apply formatter corrections in the 10 reported files.
4. Replace README's stale candidate SHA with the exact test candidate or a
   local-source instruction that cannot silently test the wrong revision.
5. Run PHPUnit, Pint, Composer validation, PHP lint, documentation build,
   static verifier and deterministic rebuild.

Stop after a clean exact SHA and independent tester verdict. Do not mix this
batch with deleting old source or changing the public CLI.

## Second bounded implementation batch

Goal: create a portable-only new-major branch.

- preserve the accepted 1.x branch/tags;
- make `init` invoke the current portable scaffold directly;
- remove old init presets, `source/_core` force options, Jigsaw build path,
  Azure translate command, legacy starter and old-only tests;
- boot the new builder without legacy providers/classes;
- recalculate Composer requirements from remaining symbol owners;
- package-inspect source and dist archives.

## Third bounded implementation batch

Goal: remove temporary and developer-only runtime layers.

- remove `LegacyPortablePagePublisher`, `PortableHtmlRenderer` fallback and
  `DOCARA_PORTABLE_PUBLISHER` after final parity evidence is archived;
- make component catalogue and demonstrator explicit features;
- move declarative previews out of production output;
- replace full resolved-page-plan publication with compact receipts;
- decide and execute retirement of `docara-template` mirror machinery.

## Protected invariants

Do not regress portable update preservation, path confinement, atomic publish,
deterministic output, multilingual routing, Framework revision pinning,
registered-template safety, Smart prop validation, accessibility, or static
reference verification.

## Independent acceptance

Tester must verify the exact candidate from an archive, not the executor's
working tree, and must distinguish source-checkout success from Composer-dist
success. Release/deploy readiness cannot be inferred from this audit.
