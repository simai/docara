# Workflow: browsable declarative preview

Date: 2026-07-20
Status: in_progress
Workflow ID: `2026-07-20-declarative-preview`
Process model: `general_delivery`
Current state: `validation_passed_pending_local_deployment`
Target state: `evidence_recorded`
Parent track: `docara-consolidation`

## Goal

Publish a safe, browsable preview of the complete accepted declarative chain
next to the unchanged legacy site, with an index of rendered and skipped pages,
clickable preview navigation, diagnostics and detailed developer documentation.

## Done When

- Supported authored pages publish full preview HTML under
  `_docara/declarative-preview/pages/**`.
- `_docara/declarative-preview/index.html` lists every authored page and shows
  whether a declarative preview exists or why it was skipped.
- `_docara/declarative-preview/index.json` is a deterministic machine-readable
  receipt.
- Internal links between supported pages stay inside preview while preserving
  their original destinations as evidence.
- Preview documents use fixed trusted templates and the exact Framework asset
  plan; builder/compiler contain no HTML, CSS or JavaScript.
- The normal legacy output remains byte-identical for the same inputs.
- Full tests, two deterministic builds and static verification pass.
- A staged local deployment to `/Users/rim/Sites/docara.test/build_production`
  has backup, rollback evidence and browser acceptance.
- Documentation explains every source, processing stage, output path, receipt
  and URL needed to inspect the chain.

## Scope

Allowed:

- `src/Declarative/Preview/**`;
- narrow integration in `src/PortableSite/PortableSiteBuilder.php`;
- narrow preview-receipt integration in `scripts/verify-static-build.php`;
- trusted preview templates and registry entries under `resources/**`;
- tests, docs, workflow, evidence and project memory;
- staged local replacement of only
  `/Users/rim/Sites/docara.test/build_production`.

Forbidden:

- changes to `PortableHtmlRenderer.php`;
- replacing the legacy publisher;
- writes to source content in `/Users/rim/Sites/docara.test`;
- public deploy, push, merge, tag or release;
- deletion of backup history;
- arbitrary templates, callbacks or executable authored configuration.

## Batches

| Batch | Work | Evidence | Status |
| --- | --- | --- | --- |
| 0 | Recover track, route, runtime inventory and launch contract | workflow/gates | completed |
| 1 | Preview link projection and trusted full-document/index rendering | focused tests | completed |
| 2 | Builder publication, preview index and diagnostics | integration tests | completed |
| 3 | Detailed chain and inspection documentation | docs contract | completed |
| 4 | Full regression and deterministic builds | PHPUnit/verifier | completed |
| 5 | Staging, backup, local deployment and browser review | runtime/browser evidence | in_progress |
| 6 | Reverse-outcome acceptance and closure | requirement matrix | pending |

## Design

1. The accepted `RenderArtifact` remains the canonical declarative result.
2. A preview-only projector rewrites exact known internal links after parity
   checks so preview navigation remains inside the preview tree.
3. The original URL is retained in `data-docara-original-href`.
4. Full HTML documents and the preview catalogue use fixed trusted templates.
5. Preview output is additive and is not part of normal page publication.
6. Skipped pages remain visible in the catalogue with their unsupported Smart
   component keys.

## Routing Gap

The central federation route classified the phrase about the rendering
pipeline as `skill_federation_change`. No skill or federation source is in
scope. Raw owner routing is therefore `docara + dev`, with `tester` acceptance
and `ops` for the local ServBay deployment.

## Local Runtime

- Source build:
  `docs/site/build_production`.
- ServBay project:
  `/Users/rim/Sites/docara.test`.
- Active document root:
  `/Users/rim/Sites/docara.test/build_production`.
- Baseline source and served `index.html` SHA-256:
  `fcf720578b678dd8bf792c5eb552eae4ef671544609458956c2e26fdcf3dce2f`.
- Backup root:
  `/Users/rim/Sites/docara.test/.docara-backups`.
- Staging root:
  `/Users/rim/Sites/docara.test/.docara-staging`.

## Verification

- preview projection positive and unsafe-input negative tests;
- trusted-template boundary test;
- index receipt determinism;
- builder integration and skipped-page evidence;
- legacy renderer SHA-256;
- focused and full PHPUnit;
- two equal production build digests;
- static verifier;
- `curl` hashes and browser checks at desktop and narrow viewport.

## Stop Conditions

- preview requires changing legacy output bytes;
- route projection can escape the configured preview prefix;
- arbitrary authored HTML/template execution becomes necessary;
- local deployment lacks a complete backup or staging verification;
- ServBay would require restart or configuration change;
- public/release/secret/destructive action is needed.

## Evidence

Root:
`source/workflow/evidence/2026-07-20-declarative-preview/`.

Required:

- `baseline.md`;
- `verification-summary.md`;
- `deployment.md`;
- `browser-acceptance.md`;
- `requirement-matrix.md`;
- `acceptance.md`.

## Current Result

- 45 authored documentation pages publish declarative preview HTML.
- One catalogue and one deterministic JSON receipt expose the complete set.
- The build contains 113 verified HTML documents and 10,920 checked local
  references.
- Two consecutive production builds have identical tree digest
  `d6ae16efae2e5066b2f7ca957cae2bd7d896e83e26acea3be3d27956b8f518be`.
- Full PHPUnit on ServBay PHP 8.2: 575 tests, 4,759 assertions, PASS.
- Legacy renderer SHA-256 remains
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Next

Commit the validated candidate, run the local runtime action gate, stage and
verify the exact build, create a complete backup, switch only
`build_production`, then perform curl and browser acceptance.
