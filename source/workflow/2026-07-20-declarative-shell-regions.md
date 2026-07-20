# Workflow: declarative documentation shell regions

Date: 2026-07-20
Status: in_progress
Workflow ID: `2026-07-20-declarative-shell-regions`
Process model: `general_delivery`
Current state: `implementation_verified`
Target state: `evidence_recorded`
Parent track: `docara-consolidation`

## Track

### Final Outcome

Docara builds documentation and landing sites from separated Markdown, JSON
composition, typed data, product-owned Smart components and Simai Framework
presentation primitives, using the same Page -> Region -> Section -> Block ->
Smart philosophy as Larena without becoming Larena.

### Completed Goals

1. Portable engine and accepted legacy documentation experience.
2. First shadow declarative article-body pipeline with Larena projection.

### Remaining Goals

1. Accept the declarative documentation shell regions.
2. Migrate remaining shell behavior and component coverage in bounded slices.
3. Switch publication only after full visual, browser and migration acceptance.

### Current Goal

Accept typed header, sidebar and outline composition while the legacy renderer
remains byte-identical and continues to publish the site.

## Goal

Extend the accepted shadow declarative pipeline from the article body to the
documentation shell data: header, sidebar and outline must be populated by
typed Page -> Section -> Block -> Smart plans that can be projected to Larena
without changing the published legacy renderer.

## Done When

- An immutable page composition context carries normalized branding,
  navigation and outline data into the compiler after topology resolution.
- `header`, `sidebar` and `outline` contain explicit section and block plans;
  `main` remains the accepted article composition and `footer` remains an
  explicit empty optional region.
- Docara-owned composite Smart manifests use the Larena
  `larena.ui.smart_manifest.v1` contract and fixed view/template identifiers.
- `docara.header`, `docara.navigation` and `docara.outline` render through
  immutable view models and trusted presentation-only templates.
- Navigation preserves hierarchy, active page/section/ancestor state and a
  bounded depth of four levels.
- Structural semantic parity proves that branding, navigation hierarchy,
  active state and outline data equal the accepted builder inputs.
- The same populated region plan passes through the Larena contract adapter.
- Builder and parser contain no HTML, CSS or JavaScript implementation.
- The accepted legacy renderer remains byte-identical and remains the only
  published renderer.
- Focused and full tests, deterministic build and static verification pass.

## Scope

Allowed:

- `src/Declarative/**`;
- narrow shadow integration in `src/PortableSite/PortableSiteBuilder.php`;
- declarative schemas and resources under `resources/**`;
- tests, developer docs, workflow and evidence;
- project memory synchronization.

Forbidden:

- changes to `PortableHtmlRenderer.php`;
- switching published HTML to the new pipeline;
- adding a fake Simai Framework manifest for a component that does not exist;
- writes to Larena, Simai Framework, Bitrix or consumer repositories;
- public release, push, merge, tag, local publication or runtime changes;
- arbitrary authored template paths, PHP classes or callbacks.

## Ownership

| Role | Owner |
| --- | --- |
| Shared layout/Smart contract | `larena` |
| Docara compiler and static product mechanics | `docara` |
| Framework component/utility boundary | `sf5` |
| Repository implementation | `dev` |
| Acceptance and regression evidence | `tester` |
| Runtime/non-publication boundary | `ops` |
| Coordination and recovery | `teamlead` |

## Batches

| Batch | Work | Evidence | Status |
| --- | --- | --- | --- |
| 0 | Recover track, correct memory, launch contract | route/process/memory guard | completed |
| 1 | Typed shell composition context and populated region plans | compiler and adapter tests | completed |
| 2 | Composite Smart manifests, views, trusted templates and renderers | schema/security/render tests | completed |
| 3 | Move shadow compilation after navigation/reading-context resolution | builder integration tests | completed |
| 4 | Structural semantic parity and fail-closed depth/state checks | parity/negative tests | completed |
| 5 | Full regression, deterministic build and evidence | PHPUnit/build/verifier | completed |
| 6 | Separated reverse-outcome acceptance and closure | requirement matrix | in_progress |

## Design Decisions

1. `docara.*` shell components are product-owned composite Smart components,
   not invented Simai Framework components.
2. Their manifests use the same canonical Larena Smart manifest vocabulary.
3. Simai Framework remains the frontend implementation layer used by the
   templates through known utilities/components.
4. Topology and reading context remain data services; they do not render HTML.
5. The compiler receives normalized immutable data after those services run.
6. Templates receive view models and trusted child fragments only.
7. Existing HTML stays the golden behavior until a later full-shell migration
   Goal and browser acceptance.

## Verification

- manifest/schema validation;
- compiler region-plan assertions;
- four-level navigation fixture;
- active page/section/ancestor fixture;
- structural parity positive and negative fixtures;
- template allowlist and presentation-only checks;
- Larena adapter parity;
- legacy renderer SHA-256;
- focused and full PHPUnit;
- two identical disposable production builds;
- static verifier.

## Stop Conditions

- required behavior needs a write to an owner repository;
- Framework source evidence contradicts the proposed markup contract;
- moving shadow compilation changes accepted published HTML;
- arbitrary executable template selection becomes necessary;
- live, destructive, release, secret or external publication action is needed.

## Evidence

Root:
`source/workflow/evidence/2026-07-20-declarative-shell-regions/`

Required:

- `baseline.md`;
- `verification-summary.md`;
- `requirement-matrix.md`;
- `kaizen.md`;
- `acceptance.md`.

## Current State

- Previous implementation candidate:
  `a29c1ab03462415879ec7383e6cf53e1dcccb1c2`.
- Previous closure:
  `891bd4c69972bef4f09967612ddd073e9a36696a`.
- Legacy renderer SHA-256:
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.
- The first slice already provides typed `DocumentAst`, main article
  composition, trusted `ui.alert`, semantic content parity and Larena adapter.
- The current missing capability is real data in `header`, `sidebar` and
  `outline`.
- Implementation and regression evidence are complete in the working tree.
- Focused suite: 41 tests, 693 assertions.
- Full suite: 572 tests, 4,701 assertions.
- Two production builds have identical digest
  `dd7b4add26660ca067b94bccbbe9cadaf3d09fb7f11d91a5a98d1c03da90e92c`;
  each contains 66 HTML pages, 6,036 checked references and zero broken links.
- Legacy renderer remains byte-identical at
  `a28e914128a55143ce13e21c8bebc2216b5144919c6dbb2e5dfee366229125d0`.

## Next

Create the implementation candidate commit, then execute the separated
reverse-outcome acceptance against its exact archived tree.
