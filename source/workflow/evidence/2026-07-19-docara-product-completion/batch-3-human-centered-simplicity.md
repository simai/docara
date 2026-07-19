# Batch 3 — Human-Centered Simplicity preacceptance

Baseline: `f482ced`
Candidate: `73eae43b9e8f715c0dc978390f4e60a1011465c9`
Status: `PASS` from independent complete-diff review.

## Primary outcome

A reader understands where the current document sits, what sections it
contains and where to continue, without learning Docara internals or opening a
second navigation model.

## Complete changed-surface inventory

| Purpose | Exact files | Necessity |
| --- | --- | --- |
| Public entry contract | `README.md`; `docs/README.md`; `docs/portable-format.md` | Announces the inherited reading contract, build behavior and maintained documentation source. |
| Author documentation | `docs/site/content/authoring.md`; `authoring/configuration.md`; `authoring/inheritance.md`; `authoring/layout-and-navigation.md`; `authoring/reading-context.md`; `start.md` | Gives one beginner-to-advanced path for enabling, inheriting, resetting and using the UI. |
| Build/developer/migration reference | `build/local-preview.md`; `build/static-output.md`; `development/architecture.md`; `development/testing.md`; `migration/legacy.md`; `reference/resolved-plan.md`; `reference/schemas.md`; `reference/security-and-errors.md` | Explains deterministic anchors, verifier failures, architecture, tests and safe legacy migration. |
| Canonical consumers | `docs/site/docara.json`; `stubs/portable/docara.json`; `stubs/portable/content/guides/getting-started.md`; `stubs/portable/content/landing.md` | Keeps the documentation site and empty-directory starter on one contract and provides a real outline/fragment demonstration. |
| Strict configuration schemas | `resources/schemas/presentation.schema.json`; `site.schema.json`; `section.schema.json`; `page.schema.json` | Rejects unknown fields and invalid types/depth at every inheritance level. |
| Static safety gate | `scripts/verify-static-build.php` | Rejects duplicate ids, unresolved local fragments and unsafe fragment encodings independently from rendering. |
| Configuration/topology/outline | `src/Portable/PortableConfigurationLoader.php`; `src/PortableSite/PortableNavigationBuilder.php`; `src/PortableSite/PortableDocumentOutlineBuilder.php`; `src/PortableSite/PortableSiteBuilder.php` | Resolves one inherited contract and derives all reading projections from canonical page data. |
| Presentation | `src/PortableSite/PortableHtmlRenderer.php` | Composes native semantics with exact Framework primitives and bounded product layout CSS. |
| Integration and negative tests | `tests/PortableInitCommandTest.php`; `tests/PortableSiteBuilderTest.php`; `tests/Unit/PortableConfigurationTest.php`; `tests/Unit/PortableDocumentOutlineBuilderTest.php`; `tests/Unit/PortableNavigationBuilderTest.php`; `tests/Unit/StaticBuildVerifierTest.php` | Covers starter, schemas, topology, Unicode, responsive-equivalent DOM and verifier rejection paths. |
| Goal state and evidence | `source/workflow/2026-07-19-docara-product-completion.md`; `source/workflow/ACTIVE.md`; `batch-3-reading-context-decision.md`; `batch-3-reading-context-implementation.md`; `batch-3-browser-ux-design-preacceptance.md`; this file | Preserves decisions, complete scope, verification and nonclaims without treating the batch as the Goal. |

## Simplicity decisions

- one `reading` branch instead of separate settings for each renderer;
- one topology instead of menu, breadcrumb and adjacency registries;
- native `nav`, links and `details` instead of a new runtime controller;
- one heading-decoration pass instead of client-side outline discovery;
- exact Core breadcrumbs with a supported item-count value instead of a local
  fork or hidden custom duplicate;
- no scrollspy, settings panel, analytics, service, database or new dependency
  in this batch.

## Protective complexity retained

Strict schemas, provenance, Unicode normalization, collision-safe ids,
accessible heading text, static fragment verification, responsive equivalence,
keyboard focus and exact Framework pins are protective rather than optional
ceremony. Removing them would make generated documentation appear correct
while leaving broken links, inaccessible headings or drift-prone runtime
behavior.

## Progressive disclosure

- desktop readers see the compact outline in a dedicated rail;
- tablet/mobile readers see one collapsed native disclosure;
- short H1-only pages render no empty outline;
- home renders no redundant one-item breadcrumb;
- missing previous/next boundaries render no disabled fake control;
- landing pages receive none of the documentation-only chrome.

## Exact verdict

The independent reviewer confirmed the candidate is a direct child of the
baseline, `git diff --check` passes and the exact diff contains `43` files
(`35` modified, `8` added). Every file is covered by the inventory above,
including this file as `this file`.

No second registry, local Framework fork, duplicate configuration truth or
accidental product surface was found. Shell id reservation is a bounded
collision-safety list, not a competing registry, and static verification
detects drift. Verdict: `PASS` for Batch 3 only.
