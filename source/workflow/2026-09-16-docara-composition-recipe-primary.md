# Workflow: Composition Recipe as the primary Docara pipeline

Date: 2026-09-16
Status: in-progress
Owner: Docara
Process: implementation and consumer acceptance
Launch record: `source/workflow/2026-09-16-docara-composition-recipe-primary.launch.yaml`
Evidence: `source/workflow/evidence/2026-09-16-docara-composition-recipe-primary/`

## Goal

Make `simai.composition.recipe.v1` the primary internal composition contract
for every Docara page while preserving Markdown, `docara.json`, `section.json`
and page sidecars as the authoring surface.

## Done When

- ordinary Markdown pages and explicit Recipe pages both resolve through the
  accepted Framework Recipe runtime;
- the generated page receipt contains Recipe, Document and dependency digests;
- Docara's former resolved-render-plan path no longer owns production
  composition after parity is proved;
- the Docara documentation site and `ui-doc` build and pass static and browser
  acceptance through the new path;
- existing authoring inputs, URLs, content, navigation, search, accessibility,
  exact Framework locking and atomic build behavior remain intact.

## Accepted architecture

- normative Recipe schemas and resolution algorithm remain owned by
  `ui-source/src/core/contracts/composition-recipe-v1/`;
- Docara owns source inheritance, Markdown parsing, product type manifests,
  product renderers, navigation/search derivation and static publication;
- author files are projected into Recipe automatically and are not rewritten;
- one managed Node runtime is reused for a complete build;
- static build directories are the publication snapshots; no second editable
  page store is introduced;
- Larena sites are not switched in this workflow.

## Stages

1. Freeze baseline, capability mapping and parity corpus.
2. Implement the Docara source-to-Recipe projection and batched runtime host.
3. Run old/new parity, correct accepted differences, then retire the old
   production composition path.
4. Build and verify Docara and `ui-doc`, then perform browser acceptance and
   local immutable rollback checks.
5. Integrate the exact revisions, update documentation and close the workflow.

## Protected invariants

- no source Markdown or project JSON is rewritten by build;
- unknown fields, paths, types, slots and unsafe values continue to fail
  closed;
- no HTML, arbitrary classes, executable expressions or secrets are admitted
  into Recipe inputs;
- exact Framework runtime and contract digests are required;
- full and single-page builds use the same production pipeline;
- a failed page never partially replaces a verified build;
- existing unrelated worktrees and dirty files are preserved.

## Simplicity decision

Reuse the accepted Recipe resolver, existing configuration loader, Markdown
parser, registries, renderers and atomic publisher. Add only the adapter and
runtime session needed to connect them. Do not add another authoring format,
page database, registry, renderer family or public migration switch.

## Verification corpus

- all package unit and integration tests;
- the bundled Docara documentation site;
- the full `ui-doc` project;
- representative documentation, landing, component, Smart-component, code,
  example, multilingual LTR/RTL and explicit Recipe pages;
- negative Recipe, path, digest, cycle, revision and rendering cases;
- complete and single-page builds, static verification and browser console,
  theme, direction and responsive checks.

## Stop conditions

- a required change would move the normative standard out of `ui-source`;
- preserving current authoring requires arbitrary HTML or executable Recipe
  data;
- a second editable source of truth is required;
- an unrelated dirty worktree must be reset, stashed or overwritten;
- a public release or production deployment is required before local
  acceptance is complete.
