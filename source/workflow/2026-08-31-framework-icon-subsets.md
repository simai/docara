# Universal SIMAI Framework icon subsets

Date: 2026-08-31
Status: completed

## Goal

Provide one deterministic icon-subset mechanism owned by SIMAI Framework,
preserve the existing no-build Loader path, and use a package-owned offline
subset for the Docara shell without introducing a second icon registry or a
runtime network requirement.

## Done when

- `ui-builder` exposes one tested library/CLI producing deterministic WOFF2,
  CSS and `sf.icon_subset.v1` manifests from exact Material Symbols inputs;
- `ui-loader` handles configured initial manifests, late icons, mixed icon
  families and an exact local fallback without `@latest`;
- Docara scans final HTML through its existing asset plan, preloads a local
  shell subset, records and verifies the subset in existing receipts, and does
  not request `icons.simai.io` by default;
- Docara docs and `ui-doc.test` build, verify and pass focused browser checks;
- no commit, push, tag, release, service switch or public deploy occurs.

## Baselines

- `ui-builder`: `367b3423f9707b850c6bef9476ab8d1ed44039e1`, clean;
- `ui-loader`: `c94a214fb727f0468863d10a94d4388e0f111852`, clean;
- `ui`: `185ca0620df6b46b9e2c9c92231a46c9b79a786b`, unrelated
  untracked graph projection preserved and excluded;
- `docara`: `562d86c33742f79f1d50e3092a9b7ad54731d073`, unrelated
  native View Transition proposal preserved and excluded;
- `ui-doc`: `b23c3e4963032c7e4849b382a7ed3d1bcd34b954`, clean.

## Accepted architecture and reuse review

Primary user outcome: Framework projects load only the icon bytes needed for
their first frame while retaining the same markup and a safe path for icons
that appear later.

Selected option: extend the existing `ui-builder` build system, existing
`iconSubsetRuntime` and existing Docara `FrameworkAssetPlanner`. Rejected:

- a Docara-only subset engine, because every Framework consumer would need a
  duplicate implementation;
- a second icon catalogue, because icon names and axes already belong to the
  exact Material Symbols source and Framework component manifests;
- a mandatory online service, because static Docara output must remain
  portable and reproducible;
- replacing icons with a new SVG API in this batch, because it would change
  the accepted Framework markup and styling contract.

Protected complexity: exact source hashes, OpenType ligature closure, family
separation, immutable output names, offline fallback, CSP compatibility and
static receipt verification.

## Batches

1. Generator: add the exact-pinned WASM subset dependency, library, CLI,
   schema, fixtures and deterministic tests in `ui-builder`.
2. Runtime: correct `ui-loader` family-aware discovery, manifest validation,
   late cumulative loading and exact local fallback; add runtime tests.
3. Distribution: build exact Framework assets locally without publishing.
4. Docara: integrate the generated shell subset into the existing icon
   projection, asset plan and static verifier; update specs and tests.
5. Consumer acceptance: rebuild Docara docs and local `ui-doc.test`, run HTTP
   and browser checks, and record size/CLS/network evidence.

## Stop conditions

- changing the public icon markup or requiring new project configuration;
- inability to preserve ligatures or deterministic WOFF2 output;
- a required live service mutation, release, repository push or public deploy;
- overlap with the excluded View Transition work or unrelated repository
  changes.

## Result

- `ui-builder` now owns one deterministic HarfBuzz WASM generator, CLI,
  neutral `sf.icon_subset.v1` manifest and compatibility adapter for the
  existing icon-service API;
- `ui-loader` keeps the no-build discovery path, separates icon families,
  grows late subsets cumulatively and falls back once to the exact local full
  font without `@latest` or a correctness dependency on `localStorage`;
- a release-readiness correction extended the common Framework packager to
  carry fixed local OTF fallbacks for `rounded` and `sharp`; the files are
  present in the Component distribution but are not requested in the first
  frame;
- the Framework distribution contains the coherent updated Loader runtime;
- Docara ships and verifies a package-owned 67-icon subset: 244,368 bytes
  instead of the 3,964,532-byte full source font (93.84% smaller);
- Docara documentation and local `ui-doc.test` were rebuilt and passed static
  and browser verification with shell CLS `0`, no icon-service request and no
  missing-glyph text;
- the live `icons.simai.io` switch remains a separate release operation because
  its owner repository was not present locally; the shared adapter is ready for
  that migration and the Docara pilot does not depend on it.

The correction was rechecked with 121 `ui-builder` tests, 8 Loader runtime
tests, targeted ESLint, a diagnostics-clean Component product build, 60 Docara
tests with 2,782 assertions, the 128-page Docara build and the 911-page
`ui-doc` build. Static verification checked 35,556 and 370,679 local
references respectively and found no broken references.

Publication remains intentionally blocked until the source repositories are
committed in dependency order and Docara is regenerated from the resulting
exact Framework commit and tree hash. The current local projection is valid
for verification, but its historical revision is not release provenance for
the dirty generated distribution.

Evidence:
`source/workflow/evidence/2026-08-31-framework-icon-subsets/verification-summary.json`.

No commit, push, tag, release, service switch or public deploy was performed.
