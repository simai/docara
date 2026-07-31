# Docara component prototype parity

Date: 2026-07-30
Status: completed
Workflow ID: `2026-07-30-docara-component-prototype-parity`
Track: docara-consolidation

## Goal

Bring the public Docara component system into exact semantic and visual parity
with the accepted prototype `docara-component-system-preview.html`, while
keeping the implementation Framework-first and the documentation human-first.

## Source of truth

- accepted prototype: `source/workflow/prototypes/docara-component-system-preview.html`;
- accepted decisions: `source/workflow/2026-07-26-docara-component-system-prototype.md`;
- live consumer: `https://docara.test/ru/components/`;
- implementation: component catalog, Markdown parser/renderers, catalog
  projector, portable assets and tests in this worktree.

## Allowed changes

- `resources/component-catalog/**`;
- component-related files in `src/ComponentCatalog/**`, `src/Markdown/**` and
  `src/PortableSite/**`;
- component documentation and tests;
- the local `docara.test` build;
- workflow and QA evidence for this batch.

## Explicit boundaries

- the outdated Docara skill is excluded by owner instruction;
- generated Framework repositories are not edited manually;
- unrelated dirty-worktree changes are preserved;
- no default-branch merge, tag, package publication or production deploy;
- no readiness claim beyond this local candidate and its evidence.

## Done when

1. Every accepted prototype capability has a recorded disposition: native
   Markdown, Docara component, generated feature or intentional non-component.
2. Every supported public component has a working page, representative example,
   invocation syntax, useful parameters and all accepted variants.
3. Obsolete duplicate concepts are no longer advertised as public components.
4. Visual details accepted in the prototype are present in the generated site.
5. Targeted tests, full PHPUnit, deterministic build, static verification,
   light/dark and desktop/mobile browser checks pass.
6. Findings and readiness records describe remaining gaps without overclaim.

## Execution sequence

1. Inventory prototype and current runtime.
2. Record parity findings and choose the minimal public model.
3. Correct runtime, catalog data and documentation.
4. Build affected pages during iteration.
5. Run the complete build and acceptance matrix.

## Kaizen

The previous catalog completion claim was based on file/page counts rather than
an exact prototype-to-runtime matrix. This batch makes that matrix a required
testable artifact so future additions cannot silently remain prototype-only.

## Result

- Every accepted prototype capability has a public runtime disposition and a
  generated reference page or an explicit native/generated implementation.
- Previously prototype-only Tabs, Diagram, Math, Banner, Backlinks, automatic
  metadata, footnotes and sandboxed HTML now execute in the portable build.
- Partial Hero, Logos, Example, Code, Details, Steps, Tree, Alert and Embed
  examples now demonstrate the accepted variants instead of placeholder copy.
- The preferred public catalog contains 30 components/capabilities. Historical
  Columns, CTA, Features, Promo and Showcase renderers are not advertised as
  separate public building blocks.
- Backlink hydration and publisher asset copying were separated from the site
  builder so the build pipeline stays readable and testable.

## Evidence

- row-level verdict: `source/qa/2026-07-30-docara-component-prototype-parity/FINDINGS.md`;
- readiness: `source/qa/2026-07-30-docara-component-prototype-parity/READINESS.md`;
- deterministic production output: `docs/site/build_production`;
- browser captures: `output/playwright/component-parity-final/`.

This is acceptance of the local Docara candidate only. It is not a Framework
release, default-branch integration or production-readiness claim.
