# Docara pre-release legacy and simplicity audit

Date: 2026-07-22

Candidate: `9b1290bf547a8c87651704a9554be0acc881aebf`

Branch: `codex/docara-consolidation`

Audit verdict: **COMPLETE**

Release verdict: **CORRECTION_REQUIRED**

## Executive conclusion

The new declarative Docara is a working product slice, but the repository is
not yet a clean new Docara release. The old Blade/Jigsaw product was retained
almost intact and the new JSON/Markdown product was added beside it. The
candidate therefore ships two starters, two initialization models, two build
paths, the old translation subsystem, temporary renderer migration machinery,
and release automation that still deploys the old Node/Yarn workflow.

The correct release boundary is a breaking new major version with one product:

```text
docara init
docara build
docara serve
docara verify-static
```

The old 1.x product should remain recoverable through tags and, if necessary,
a maintenance branch. It should not remain executable inside the new major
version.

## Proven working foundation

The cleanup must preserve these accepted outcomes:

- PHP-only portable initialization from `stubs/portable`;
- Markdown content plus validated `docara.json`, `section.json` and optional
  page sidecars;
- deterministic inheritance and resolved page plans;
- declarative layout, regions, sections, blocks and view trees;
- Simai Framework lock, exact assets and the product-owned Smart components
  `docara.brand`, `docara.navigation` and `docara.toc`;
- multilingual locale registry, symmetric locale URLs and language packs;
- navigation, outline, search, reader settings, theme and accessibility;
- atomic build publication and a fail-closed static verification command;
- legal attribution and license history inherited from the earlier project.

Blade itself is not automatically legacy. The isolated registered-template
renderer may continue using `illuminate/view`; the target is to remove the old
arbitrary Jigsaw-compatible site pipeline, not to forbid a trusted template
engine.

## Evidence summary

| Surface | Result |
| --- | --- |
| Fresh portable init in an empty directory | PASS |
| Fresh portable build | PASS |
| Fresh portable static verification | PASS |
| Bundled documentation build | PASS, 2.42 s |
| Bundled documentation static verification | PASS, 271 HTML pages and 20,512 local references |
| Repeated documentation build | PASS, identical aggregate SHA-256 |
| PHP syntax, 266 PHP/Blade files | PASS |
| `composer validate --strict` | PASS |
| `git diff --check` | PASS |
| PHPUnit | **FAIL**, 623/624 tests pass; one asset-plan contract failure |
| Pint | **FAIL**, 10 files require formatting |
| Secret-pattern scan | PASS for committed product sources; only empty/example Azure values found |
| Release branch consistency | **FAIL**, local branch is ahead 29 and behind 1 |

Disposable successful scenario:
`/tmp/docara-audit.ZTFlnf`. It was intentionally retained because this audit's
safe-write boundary forbids deletion; it is not inside the repository.

## Findings

### DCR-001 — BLOCKER — Two active products remain in one runtime

Evidence:

- all 104 files from legacy `src/` still exist; current `src/` has 238 files;
- all 377 legacy test files still exist; current tests have 409 files;
- 143 of 145 legacy stub files remain;
- `docara init` is the old flow, while the new product requires
  `docara init --portable`;
- `Docara::build()` branches between portable and Jigsaw-era build paths;
- `Container` still registers legacy collections, compatibility, custom-tag,
  event, cache, bootstrap and configurator providers for the application;
- `composer.json` explicitly advertises “legacy Blade/Jigsaw compatibility”.

Impact: users, maintainers and AI agents must understand two unrelated product
models. Old providers and dependencies also remain on the new startup path.

Required correction: cut a new major line where the portable model is the only
model and `init` needs no mode flag. Preserve 1.x through Git history rather
than runtime compatibility.

### DCR-002 — BLOCKER — The package archive still contains the old starter

Evidence:

- `stubs/site`: 143 files and 1,863,658 bytes;
- `stubs/portable`: 20 files and 15,141 bytes;
- the old starter alone contains a 1.39 MB hero image, a 108 KB Yarn lock,
  Vite/Node files, Blade sources and old English documentation;
- the Git archive is 4,464,640 bytes and exports both starters;
- legacy `src/` contributes another 378,373 bytes.

Impact: more than half of meaningful archive bytes are old runtime/starter
payload, and users can still initialize it.

Required correction: remove `stubs/site` and all old-only runtime classes from
the new major package. Rename `stubs/portable` to the single canonical starter
only after command and update paths are changed atomically.

### DCR-003 — BLOCKER — The candidate is split across local and remote history

Evidence:

- local branch: ahead 29, behind 1 relative to
  `origin/codex/docara-consolidation`;
- remote-only commit:
  `a913dce60b7246d24f9afaf6e9f259a5b7c097e0 Fix Windows filesystem path boundary checks`;
- it adds `FilesystemPath` and changes eight runtime/test files;
- the remote commit is not an ancestor of the audited candidate.

Impact: UI work and a security/safety portability correction are in divergent
histories. Neither side alone is the release candidate.

Required correction: integrate the exact Windows correction into a single
candidate, resolve conflicts deliberately, then rerun the entire matrix on the
new SHA.

### DCR-004 — BLOCKER — Current quality gates are red

Evidence:

- PHPUnit: 624 tests, 5,627 assertions, one failure in
  `PortableComponentCatalogProjectorTest::smart_demo_assets_are_scoped_to_smart_details`;
- the resolved asset plan is observed as a serialized value where the test
  expects structured data, so Smart asset scoping is no longer proved;
- Pint reports violations in 10 source/test files.

Impact: the candidate cannot be tagged, even though a direct documentation
build succeeds.

Required correction: restore one canonical structured asset-plan contract,
fix formatting, rerun full tests, and add exact package/install acceptance.

### DCR-005 — BLOCKER — GitHub automation still builds and deploys legacy Docara

Evidence in `.github/workflows/deploy.yml`:

- creates the old Azure translation `.env`;
- runs legacy `init --update --force-core-files --force-core-configs`;
- installs Node.js and Yarn and builds frontend assets;
- deploys to `doc.simai.io` on every `main` push;
- updates the package without an exact new-major candidate contract;
- uses moving action tags rather than full action SHAs.

The lint workflow does not run PHPUnit and currently would fail Pint.

Impact: merging a “new Docara” candidate can still publish through the old
product route, and CI does not prove the actual release scenario.

Required correction: replace deployment with portable init/build/verify and
an explicit deploy gate; add PHP/OS compatibility tests and full PHPUnit; pin
external actions.

### DCR-006 — HIGH — Temporary renderer migration machinery became runtime

Evidence:

- `PortableHtmlRenderer` remains an 845-line fallback;
- `LegacyPortablePagePublisher` and `DeclarativePortablePagePublisher` coexist;
- `DOCARA_PORTABLE_PUBLISHER=legacy` selects the old portable renderer;
- semantic parity, shell parity and “legacy URL” preview maps run during normal
  builds;
- language packs and preview templates expose `legacy_only` and
  `open_legacy` names.

Impact: a migration safety net is being shipped as a permanent second
publisher and doubles concepts in code, diagnostics and documentation.

Required correction: after one final accepted parity snapshot, keep only the
declarative publisher. Move any comparison tooling to tests or an internal
migration utility. Rename retained published-page fields to neutral names.

### DCR-007 — HIGH — A minimal site always generates developer-only surfaces

Fresh-starter evidence:

- 7 authored Markdown files produce 66 HTML pages;
- 26 generated catalogue pages, 8 declarative previews and 18 redirects are
  added automatically;
- output is 4.0 MB, including a 2.4 MB
  `.docara/resolved-page-plans.json`.

Bundled-docs evidence:

- 66 authored Markdown files produce 271 HTML pages;
- 67 preview pages are always generated;
- public output is 40 MB;
- `.docara/resolved-page-plans.json` is 26 MB, with about 198 KB in just the
  first page record because it embeds content, render plans and asset data.

Impact: ordinary documentation is coupled to the framework component lab,
migration previews and full internal diagnostics. Publication is larger and
harder to understand than the authored site.

Required correction:

- default production build publishes authored pages and required runtime
  assets only;
- make the component catalogue an explicit site feature/preset;
- make declarative previews development/test-only;
- replace the full public resolved plan with compact hashes/receipts and keep
  verbose diagnostics outside the public destination;
- default new projects to one root redirect, not a redirect page for every old
  unprefixed route.

### DCR-008 — HIGH — The separate template mirror contradicts the one-repo model

Evidence:

- `TemplateMirror` is 445 lines;
- a large test suite, two exporter/verifier scripts and a 16 KB embedded GitHub
  workflow maintain `docara-template`;
- release documentation explains a second repository and synchronization
  protocol even though `stubs/portable` is already canonical.

Impact: the product needs a release-time distributed system merely to expose a
starter that already exists in the package.

Required correction: unless a measured GitHub Template/Codespaces outcome is
still required, archive `docara-template` and remove the mirror runtime,
scripts, workflow resources and tests. A single `docara init` is the canonical
project creation path.

### DCR-009 — HIGH — CLI and package metadata still describe the old product

Evidence:

- CLI reports hard-coded `Docara 1.0` while repository tags reach `v1.3.66`;
- `translate` is an Azure-specific legacy command with a 650-line subsystem;
- `.env.example` contains only Azure/DOCS_DIR variables;
- build help still exposes legacy cache/watch semantics;
- init help exposes old presets and `source/_core` force switches;
- README documents two modes and pins old candidate
  `0f10afde92b93dd39703823ab22a2920b450a15b` rather than the audited HEAD;
- direct Composer requirements are essentially unchanged from old main.

Impact: the official entry points teach the retired model and keep dependencies
whose only owner is legacy code.

Required correction: define the new-major command surface, derive version from
release metadata, remove Azure translation from core, then re-evaluate every
Composer dependency against the remaining runtime. Keep Blade dependencies
only if the trusted template registry still needs them.

### DCR-010 — HIGH — The installed Docara skill is stale

Evidence: the current owner skill still defines Docara as Jigsaw-based and
instructs agents to use `source/docs`, `.settings.php`, `config.php`,
`translate.config.php` and the old translation scripts.

Impact: after release, Codex and developers following the official skill will
recreate the old structure even if the repository is cleaned.

Required correction: update the Docara skill only after the new contract is
frozen; run the required Skill Sync Gate and keep migration guidance separate
from normal authoring guidance.

### DCR-011 — MEDIUM — Compatibility options leak into the normal model

Evidence:

- the default starter enables `legacy_unprefixed_redirects`;
- schemas and receipts expose `legacy_unprefixed` as a normal route kind;
- the loader detects `_section.json` and emits migration instructions;
- reader theme code carries old cookie projection paths.

Impact: a newly created project starts with migration behavior despite having
no legacy history.

Required correction: default all new projects to clean canonical behavior.
If compatibility remains necessary, isolate it in an explicit migration
command/profile with a removal date and evidence.

### DCR-012 — MEDIUM — Core orchestration and verification are monolithic

Evidence:

- 237 source classes and 31,776 PHP lines in the combined runtime;
- `PortableSiteBuilder`: 1,277 lines;
- `verify-static-build.php`: 3,199 lines;
- `PortableSiteBuilderTest`: 2,184 lines;
- `StaticBuildVerifierTest`: 1,615 lines;
- new-only implementation is 18,684 source lines plus 12,592 test lines;
- `PortableSiteBuilder` contains configuration, locale discovery, generated
  catalogue projection, preview generation, parity comparison, publication,
  diagnostics, redirects and transaction handling in one method;
- the locale alternate construction contains the same `url` key twice.

Impact: changes require broad context, failures are hard to localize, and AI
agents are encouraged to add another branch to a central method.

Required correction: retain one top-level build orchestrator but extract a
small fixed sequence of stages with typed inputs/outputs: resolve, compile,
render, publish and verify. Split the verifier by receipt contract. Do not add
an abstraction unless it removes an existing branch or owns a protective
invariant.

### DCR-013 — MEDIUM — Repository and release hygiene is inconsistent

Evidence:

- `.gitignore` ignores tracked `composer.json` and contains many one-off Codex
  backup names and retired Mix/Jigsaw paths;
- no root `CHANGELOG.md` or single version source exists;
- the Composer archive exports all `docs/` sources even though they are not
  runtime payload;
- release-only template scripts are also exported;
- 287 workflow files (about 1.9 MB) are tracked; they are correctly
  `export-ignore`, but make the maintainer surface noisy.

Impact: it is unclear what is product, release tooling, documentation source,
working evidence or local debris.

Required correction: normalize ignore/export rules, add a conventional
changelog and version policy, exclude non-runtime documentation/release tools
from Composer dist, and archive closed workflow evidence without deleting
decision history.

## Retention and deletion map

| Surface | Decision |
| --- | --- |
| `stubs/portable` | keep, then become the sole starter |
| `resources/layouts`, `sections`, `blocks`, `views` | keep; this is the desired declarative core |
| `resources/smart/docara.*` | keep; product-owned Smart components are legitimate |
| Framework lock/manifests/assets | keep with exact-revision verification |
| Locale registry and language packs | keep |
| Atomic candidate build and verifier | keep, simplify and modularize |
| `stubs/site` | remove from new major |
| old Jigsaw runtime and old tests | remove from new major |
| Azure translate command/subsystem | remove from core |
| legacy portable publisher/renderer | remove after final parity acceptance |
| declarative previews | move to development/test mode |
| full public resolved-page-plan dump | replace with compact release receipt |
| template mirror machinery | remove if `docara-template` is archived |
| migration documentation | keep as documentation, not runtime mode |
| old tags/license attribution | preserve |

## Recommended cleanup sequence

1. **Unify candidate history.** Integrate the remote Windows path correction,
   fix the failing asset-plan contract and Pint, then freeze one SHA.
2. **Create the breaking product boundary.** Preserve `v1.x`; make new Docara
   portable-only and remove `--portable` plus old init/build/translate paths.
3. **Delete old payload atomically.** Remove legacy source, starter, tests and
   dependencies only after the new CLI boots independently.
4. **Retire transition machinery.** Remove fallback publisher, old portable
   renderer and normal-build parity/preview paths after an archived acceptance
   fixture proves equivalence.
5. **Make advanced surfaces opt-in.** Separate normal site build from component
   catalogue, demonstrator and verbose diagnostics.
6. **Collapse repository topology.** Archive `docara-template` after confirming
   zero required consumers; keep starter ownership only in Docara.
7. **Replace CI/release contract.** Exact portable install/build/verify on
   Linux, macOS and Windows; full tests and formatter; pinned actions; explicit
   publication/deploy approval.
8. **Update public contract.** README, docs, changelog, CLI version, package
   description and Docara skill must all describe the same model.
9. **Run independent release acceptance.** Test source checkout and Composer
   dist separately, including init, update preservation, build determinism,
   multilingual routes, Framework assets, broken-link verifier and rollback of
   the release operation.

## Release exit gate

The new major may be released only when:

- package inspection contains no old starter or Jigsaw-only source;
- `docara init` produces the JSON/Markdown project without a mode flag;
- fresh source and dist scenarios pass on Linux/macOS/Windows;
- PHPUnit, Pint, Composer validation and static verifier are green;
- the public build contains no full internal diagnostic dump;
- CI and deploy workflows execute only the new contract;
- README, documentation and the Docara skill agree;
- an independent tester accepts the exact release SHA.

## Bounded verdict

The architecture direction is sound: JSON/Markdown, declarative composition,
Simai Framework and product-owned Smart components should remain. The problem
is not that the new product is missing; it is that three temporary layers were
never removed: the original Docara, the first portable renderer, and the
developer demonstrator/diagnostic surfaces. Cleaning those boundaries will
make Docara substantially smaller and easier without discarding the work that
already functions.
