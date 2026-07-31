# Workflow: Docara component reference simplification

Date: 2026-07-28
Status: completed
Workflow ID: `2026-07-28-docara-component-reference-simplification`
Track: `docara-consolidation`
Baseline HEAD: `ecfc8b72f34a020b1f7374e11eb5b33c0838aabe`
Baseline worktree status SHA-256: `d2f417120a0d9a099cbf4bfbc4f625c75947baeccc35b1bc2c810d9ac6f86ac6`

## Goal

Turn the generated component reference into a simple reader-facing guide:
one table at `/components/`, one direct route per supported component, and one
consistent detail-page pattern with a live result, copy-ready calls, readable
parameters, variants, limitations and source facts.

## Done When

- `/components/` is a compact table rather than a card catalogue;
- generated details use `/components/<id>/` with no public `catalog` level;
- unsupported gaps do not appear as broken user examples or menu pages;
- the Alert page proves the full page pattern with parameter-specific live
  examples and matching call snippets;
- limitations and source are visible sections, not a collapsed disclosure;
- all supported component pages use reader-facing Russian and English copy;
- tests, two deterministic builds, static-link verification and browser checks
  pass for light/dark and desktop/mobile;
- the verified result is installed on the local `docara.test` playground.

## Audience And Editorial Contract

- Primary audience: a Docara author who knows Markdown but should not need to
  understand internal registries, evidence records or renderer terminology.
- Each page answers in order: what the component does, how it looks, how to
  call it, which parameters are available, which variants exist, what to keep
  in mind, and where the implementation comes from.
- Public prose uses direct Russian and ordinary documentation terms. Technical
  IDs remain only where they are copy-ready or help diagnose a build.
- Confirmed product facts come from the effective catalogue, immutable locks,
  source metadata and examples. No capabilities are invented.

## Constraints And Risks

- The worktree already contains the accepted uncommitted implementation from
  the preceding component-reference goal. Preserve all unrelated changes.
- The obsolete Docara skill is intentionally not used.
- `$docs` is unavailable in this runtime. Repository documentation contracts,
  `content` editorial gates and `publication` structure/safety review cover the
  requested documentation role.
- No merge, tag, release, public deployment or production-readiness claim.
- Route removal is intentional: backward compatibility is not required during
  this unreleased development stage.

## Batch Plan

| Batch | Goal | Verification | Status |
| --- | --- | --- | --- |
| 1 | Record baseline and route/content contract | workflow + focused inventory | completed |
| 2 | Move generated routes and navigation to `/components/` | unit route/navigation tests | completed |
| 3 | Replace cards with a compact table and remove public gap pages | index DOM assertions | completed |
| 4 | Establish the full detail-page pattern on Alert and generalize it | live/source/parameter parity tests | completed |
| 5 | Editorial pass over generated copy and authored entry points | RU/EN string and public-safety scan | completed |
| 6 | Full build, static, browser and local-site acceptance | deterministic build + browser evidence | completed |

## Human-Centered Simplicity Review

### Primary user outcome

The author opens one predictable list, selects a component and immediately sees
the result, the exact call and the parameters needed to adapt it.

### Necessity map

| Surface | Decision | Reason |
| --- | --- | --- |
| `catalog` route level | remove | creates hierarchy without a user task |
| cards on the index | replace with table | repeated descriptions consume space and hinder scanning |
| unavailable detail pages | remove from public surface | they look like broken documentation and have no usable example |
| live example | retain | proves what the call produces |
| call snippet | retain and pair with examples | lets the author copy a working form |
| parameter table | retain, simplify | required for correct calls |
| limitations | retain as visible section | protects correctness without hiding information |
| source metadata | retain with progressive detail | supports maintainers and automated provenance |

### Simplest complete alternative

One generated projector remains the source of all catalogue pages. It changes
routes and presentation without creating a second registry, database or
hand-authored page set.

### Protected complexity

Keep fail-closed registry validation, exact variant coverage, localization,
base-URL projection, static link checks and immutable metadata. These protect
correctness and portability even though the public pages become simpler.

## Progress

### Batch 1

- Status: completed
- Done: recovered the completed predecessor workflow, current dirty-worktree
  baseline and exact projector/tests that own the generated surface.
- Verification: current projector passes PHP lint before this batch.
- Next: completed in batches 2-6.

### Batches 2-5

- Status: completed
- Done: removed the public `catalog` route level, generated a single semantic
  table, limited the public surface to 28 supported components, added every
  supported component to navigation and generalized the readable detail-page
  contract with live result, exact call, parameters, parameter examples,
  variants, visible considerations and source facts.
- Verification: focused projector, builder, documentation, verifier and serve
  suites pass; active documentation and built output contain no old catalogue
  routes or unavailable-example messages.

### Batch 6

- Status: completed
- Done: produced two byte-identical builds, installed the verified output on
  the local ServBay playground with a rollback copy, and checked index and
  detail pages in the in-app browser.
- Verification: PHPUnit `333/333` with `6431` assertions; static verification
  checks `220` HTML pages and `18445` local references with `0` broken; changed
  PHP files pass Pint; `git diff --check` passes; browser checks pass in light
  and dark themes at desktop width and at `390px` without page-level horizontal
  overflow.
- Evidence: `source/workflow/evidence/2026-07-28-docara-component-reference-simplification/verification.md`.

## Final Result

- Result: completed locally. The component reference is now one compact table
  at `/components/` and one direct page per supported component.
- Verification: full automated, deterministic-build, static-link and browser
  acceptance passed; the same verified build is active on `docara.test`.
- Remaining: no required work inside this goal.
- Follow-up: merge, tag, package publication and public deployment remain a
  separate release gate.
