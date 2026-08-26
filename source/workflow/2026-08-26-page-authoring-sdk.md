# Docara page authoring SDK

Date: 2026-08-26
Status: complete

## Goal

Add optional page authoring profiles and page-level SDK operations without a
second knowledge store or status engine.

## Done when

- six built-in profiles and optional `docara.authoring.json` are implemented;
- `list`, `inspect`, `schema`, `validate`, and `scaffold` support `page`;
- CLI and MCP delegate to the same application services;
- old projects without the authoring file keep their build behavior;
- Docara's own documentation pilots landing, tutorial, reference, explanation;
- tests, full docs build, static verification, browser smoke and diff audit pass;
- specification, graph/generated context and the English owner skill are synced.

## Boundaries

- Allowed: Docara engine, schemas, tests, own documentation, current graph and
  generated project context; the canonical English Docara skill after its gate.
- Forbidden: `ui-doc`, active local sites, public deployment, commit, push,
  tag, release and package publication.
- No `knowledge/`, knowledge lock, `knowledge *` commands or new Mirai profile.

## Batches

1. Contract and page inspection model.
2. SDK/CLI/MCP/scaffold/validation integration.
3. Tests and Docara documentation pilot.
4. Specification, graph, generated context and skill sync.
5. Full QA and bounded diff audit.

## Current batch

Completed.

## Next

Await an explicit user decision for any commit, push, release, publication,
deployment, or downstream `ui-doc` adoption.

## Verification

Accepted evidence is recorded in
`source/workflow/evidence/2026-08-26-page-authoring-sdk/verification-summary.json`.
The product, pilot, static build, browser matrix, SDK equivalence, graph sync,
and route checks passed. The global federation verifier retains one
pre-existing installed-override path mismatch; local Docara skill status and
verification pass, and activation of this uncommitted revision was not
authorized.

## Simplicity and reuse evidence

- Primary outcome: a human or any agent can discover, inspect, validate, and
  safely scaffold a documentation page through the existing SDK surface.
- Reuse review: the implementation extends `DiscoveryService`,
  `ValidationService`, `ScaffoldService`, `OperationResult`, CLI, and MCP;
  it does not create a second status engine or AI-specific service.
- Changed surface: one optional contract, one page inspection service, the six
  built-in definitions, existing commands, tests, and the Docara pilot.
- Removal review: no old authoring mechanism is removed; an absent contract
  preserves unprofiled discovery and the previous public build.
- Protected complexity: filesystem containment, link count, case collision,
  stale-plan, schema, locale, and profile checks remain explicit because they
  protect project-owned source.
- Progressive disclosure: page metadata is opt-in and editorial meaning is an
  advisory checklist, while measurable technical errors stay machine-readable.
- Verdict: the smallest complete extension is to reuse the current SDK and
  receipts; `knowledge/`, a knowledge lock, new command groups, a new Mirai
  profile, and copied catalogs are unnecessary.
