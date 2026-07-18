# Batch 2 — Human-Centered Simplicity

Scope: complete Batch 2 diff from closure `235e99a` to the pending search
candidate. This is a bounded batch verdict, not the final Goal-wide HCS gate.

Verdict: PASS pending exact-candidate regression.

## Changed-surface inventory

| Surface | Files | Why it remains |
| --- | --- | --- |
| public contract | `README.md`, `docs/portable-format.md`, `docs/site/content/start.md`, `docs/site/content/authoring.md`, `docs/site/content/authoring/configuration.md`, `docs/site/content/authoring/inheritance.md`, `docs/site/content/authoring/search.md` | lets an author enable, inherit, exclude and troubleshoot search without source reconstruction |
| build/reference/migration docs | `docs/site/content/build/determinism.md`, `docs/site/content/build/static-output.md`, `docs/site/content/reference/schemas.md`, `docs/site/content/reference/security-and-errors.md`, `docs/site/content/migration/legacy.md` | explains generated artifacts, exact hashes, errors, public-data boundary and legacy mapping |
| starter and documentation site | `stubs/portable/docara.json`, `docs/site/docara.json` | one opt-in default in both canonical starter consumers |
| strict configuration/schema | `resources/schemas/presentation.schema.json`, `site.schema.json`, `section.schema.json`, `page.schema.json`, `search-index.schema.json`, `PortableConfigurationLoader.php` | prevents silent no-op fields and makes inheritance/reset explicit |
| build model | `PortableSearchTextExtractor.php`, `PortableSearchIndexBuilder.php`, `PortableSearchPlan.php`, `PortableNavigationBuilder.php`, `PortableSiteBuilder.php` | one deterministic plan before destination cleanup; navigation path is reused instead of duplicated |
| reader UI | `PortableHtmlRenderer.php`, `resources/portable/search.js` | smallest complete accessible local-search surface using Framework primitives |
| independent artifact verification | `scripts/verify-static-build.php` | catches broken/tampered search artifacts that ordinary link checking cannot see |
| regression protection | `PortableSiteBuilderTest.php`, `PortableConfigurationTest.php`, `PortableSearchTextExtractorTest.php`, `PortableSearchIndexBuilderTest.php`, `StaticBuildVerifierTest.php` | covers positive, negative, deterministic, disabled, inherited and tampered cases |
| decision/evidence | `batch-2-search-decision.md` and this evidence set | preserves why no new Framework component or external service was introduced |

## Simplicity judgement

- one inherited `search` branch, not per-page search files;
- one canonical index, not a database or server endpoint;
- one small browser runtime, not a JS application framework;
- one navigation-derived trail, not a second hierarchy;
- no new Smart-component or Framework owner write;
- protective complexity is limited to validation, deterministic hashes,
  same-origin/base containment, focus behavior and public-data warnings.

Nothing in the changed inventory can be removed without losing a stated user
job, an acceptance proof or a safety boundary.
