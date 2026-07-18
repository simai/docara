# Batch 2 — Human-Centered Simplicity

Baseline: `235e99a54f496d491213946224e899812001bff0`

Scope: the complete 39-file Batch 2 diff from the accepted Batch 1 closure to
the next exact search candidate. This is a bounded batch review, not the final
Goal-wide HCS gate.

Verdict: `PENDING_EXACT_REACCEPTANCE`.

The first exact review of `1d9bfed313efc7be725b577e53ce1b9271ec76d4`
returned `CORRECTION_REQUIRED`. The four findings are recorded below and have
implementation corrections in the working tree. PASS can be recorded only
after a new immutable candidate passes the independent exact HCS, tester and
UX/browser gates.

## Primary outcome

A reader can open one small search surface, type a local-language query, scan
up to twenty useful results and follow a native link without learning Docara's
storage model or relying on an external service. An author enables the feature
through one inherited JSON branch and can exclude individual public pages from
the index.

## Complete changed-surface inventory

Every file in `git diff --name-status 235e99a...<candidate>` is covered here.

| Purpose | Exact files | Necessity |
| --- | --- | --- |
| Public entry contract | `README.md`; `docs/portable-format.md` | States opt-in behavior, artifacts and nonclaims at the two public entry points. |
| Author path | `docs/site/content/authoring.md`; `docs/site/content/authoring/configuration.md`; `docs/site/content/authoring/inheritance.md`; `docs/site/content/authoring/search.md`; `docs/site/content/start.md` | Lets an author enable, inherit, reset, exclude and troubleshoot search without reading source. |
| Build and migration path | `docs/site/content/build/determinism.md`; `docs/site/content/build/static-output.md`; `docs/site/content/migration/legacy.md`; `docs/site/content/reference/schemas.md`; `docs/site/content/reference/security-and-errors.md` | Explains deterministic output, public-data boundary, legacy mapping and fail-closed errors. |
| Two canonical consumers | `docs/site/docara.json`; `stubs/portable/docara.json` | Keeps the maintained documentation site and starter aligned on one opt-in default. |
| Browser runtime | `resources/portable/search.js` | Provides the smallest complete keyboard-accessible local controller; no application framework or remote SDK is added. |
| Strict schemas | `resources/schemas/page.schema.json`; `resources/schemas/presentation.schema.json`; `resources/schemas/search-index.schema.json`; `resources/schemas/section.schema.json`; `resources/schemas/site.schema.json` | Rejects silent fields and makes inherited configuration and generated index shapes explicit. |
| Artifact verifier | `scripts/verify-static-build.php` | Independently checks manifest membership, safe URLs, hashes, exact references and generated artifacts. |
| Workflow SOT | `source/workflow/2026-07-19-docara-product-completion.md`; `source/workflow/ACTIVE.md` | Preserves active Goal and bounded Batch 2 state; neither claims Goal completion. |
| Decision and review evidence | `source/workflow/evidence/2026-07-19-docara-product-completion/batch-2-human-centered-simplicity.md`; `source/workflow/evidence/2026-07-19-docara-product-completion/batch-2-search-decision.md`; `source/workflow/evidence/2026-07-19-docara-product-completion/batch-2-search-implementation.md`; `source/workflow/evidence/2026-07-19-docara-product-completion/batch-2-ux-design-preacceptance.md` | Records why this design exists, what was implemented and which gates remain; it is durable evidence, not runtime code. |
| Effective configuration | `src/Portable/PortableConfigurationLoader.php` | Defines one inherited `search` branch with safe defaults and `$reset`. |
| Reader presentation | `src/PortableSite/PortableHtmlRenderer.php` | Renders only trigger, dialog, input, status and results using native semantics and exact Framework primitives. |
| Existing hierarchy reuse | `src/PortableSite/PortableNavigationBuilder.php` | Derives result trails from the same navigation tree instead of adding another hierarchy. |
| Deterministic search model | `src/PortableSite/PortableSearchIndexBuilder.php`; `src/PortableSite/PortableSearchPlan.php`; `src/PortableSite/PortableSearchTextExtractor.php`; `src/PortableSite/PortableSiteBuilder.php` | Extracts published text, validates deployment identity and prepares one canonical index before destination replacement. |
| Integration regression | `tests/PortableSiteBuilderTest.php`; `tests/Unit/PortableConfigurationTest.php`; `tests/Unit/StaticBuildVerifierTest.php` | Covers generated UI/artifacts, inheritance/reset, canonical starter compatibility and fail-closed verification. |
| Focused model regression | `tests/Unit/PortableSearchIndexBuilderTest.php`; `tests/Unit/PortableSearchTextExtractorTest.php` | Covers deterministic ordering, base containment, locale isolation, exclusions and exact text projection. |

## Necessity and removal map

| Candidate complexity | If removed | Decision |
| --- | --- | --- |
| Build-time index and plan | Search would require a server/database or crawl HTML in the browser. | Keep. |
| Small local runtime | The reader could not query, rank or render results. | Keep. |
| Strict schema and verifier | Invalid, stale or cross-base artifacts could appear to pass a build. | Keep as protective complexity. |
| Navigation-derived trail | Removing it reduces orientation; a second trail registry would duplicate truth. | Keep reuse, forbid duplicate registry. |
| Native dialog focus handling | Keyboard access and deterministic focus return would regress. | Keep, but reassess the manual loop if controls change. |
| Search filters, history, pagination, analytics, remote provider | They are not required for the stated reader job. | Excluded. |
| New Framework/Smart component | No accepted generic search component exists in the exact pin. | Excluded; keep Docara product controller. |

## Simplest complete alternative

The simplest complete design is the implemented one: one inherited boolean
configuration branch, one canonical static JSON index, one same-origin browser
runtime and native result links. A server endpoint, database, external search
provider or generic new Smart component would add ownership and failure modes.
A plain browser text scan would be smaller in files but incomplete: it cannot
search unopened pages or provide deterministic build evidence.

## Progressive disclosure

- Closed state shows one search trigger in the existing header.
- Opening reveals only query, status and results.
- Result trail and excerpt appear only when useful.
- Author controls remain in JSON/docs and never clutter the reader dialog.
- Advanced filters, history, ranking controls and diagnostics stay absent.

## Complexity delta

- Added: one runtime, one generated schema, three focused value/build classes,
  one verifier extension and their regression/docs evidence.
- Reused: existing page plans, Markdown/Smart hydration, navigation tree,
  Framework assets, colors, utilities and native links.
- Not added: dependency, package, registry, service, database, worker, remote
  request, Framework repository write or release surface.
- Visible reader surfaces: trigger, dialog, input, status and result list.

## Automation review

- Build is deterministic and index/runtime revisions are independent hashes.
- Validation is fail-closed for origin, path, deployment base, locale, schema,
  duplicate identity, content hash and manifest membership.
- DOM rendering uses `textContent`; no result HTML is interpolated.
- Static verification checks generated artifacts independently from the builder.
- `search.enabled=false` produces no runtime, index or UI.
- `search.indexed=false` excludes a page without pretending to restrict its
  public HTML; the documentation states this boundary.

## Tester and UX evidence

First candidate `1d9bfed...`:

- exact tester: `444 tests, 2193 assertions`, two deterministic production
  builds and static verification passed;
- exact UX/browser: desktop/mobile/themes/keyboard/malicious payload matrix
  passed except invalid index-revision status;
- exact HCS/source review: four findings, therefore no PASS.

The first candidate's green checks are historical evidence only. A corrected
candidate must be archived and re-run independently before this verdict changes.

## Closed findings in the correction tree

1. Effective locale is now consistently `locale ?? default_locale` in builder
   and verifier, with `canonical starter build -> verifier PASS` coverage.
2. Search trigger uses exact `sf-button-text-container`; the native result
   `<ul>` no longer carries the unprojected ghost `sf-list` class.
3. Browser preflight failures for revision/origin/path now enter visible
   `error` state before rejecting.
4. Direct search planning rejects document URLs outside normalized `base_url`
   and has an explicit negative regression.

## Residual complexity and risks

- The explicit Tab loop overlaps native modal-dialog focus containment. It is
  accepted for the current five-control surface but must be reviewed if controls
  are added.
- Ranking is intentionally small and deterministic, not a linguistic search
  engine; this is adequate for the current documentation corpus.
- The index is public output. It is not an authorization boundary.
- This bounded review does not accept later Docara batches or production/release
  readiness.

## Blocking findings

No implementation finding is intentionally left open in the correction tree.
The blocking item is procedural and real: exact HCS, tester and UX/browser
reacceptance for the new immutable candidate is still required.
