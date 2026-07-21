# Workflow: Docara Smart component unification

Date: 2026-07-21
Status: completed
Workflow ID: `2026-07-21-docara-smart-component-unification`
Process model: `general_delivery`
Current state: `evidence_recorded`
Target state: `evidence_recorded`
Project mode: `productization`
Track ID: `docara-consolidation`
Owner: `docara`
Companions: `larena`, `sf5`, `dev`, `ux`, `tester`, `ops`
Baseline HEAD: `d5e5721`
Publication target: `https://docara.test/`
Memory decision: `skip`
Memory reason: project-local workflow and evidence are authoritative; mutable
repository and runtime facts were reverified from the exact candidate.

## Current Goal

Unify Docara Smart components with the canonical Larena and Simai Framework
model without making Laravel a runtime dependency of standalone Docara.
Separate regions, sections and components; introduce one manifest contract and
an extensible contribution registry; migrate product component names to
`docara.brand`, `docara.navigation` and `docara.toc` with deprecated aliases;
move component markup, assets, behavior and hydration out of the generic
publisher shell; expose complete manifests, views, presets, Atlas and readiness;
document and demonstrate the result; publish and verify the exact local build.

## Final Outcome

Standalone Docara and the Larena/Simai Framework model share one portable Smart
contract. Product components own their validated manifests, views, presets,
templates, assets, events and hydration; the generic publisher only composes
typed regions and sections. The exact candidate is documented, demonstrated,
tested and served on local `docara.test` without adding Laravel as a dependency
of the shared Smart kernel or the generated static runtime.

## Done When

- region keys, section keys and Smart component keys are different typed
  namespaces and documentation explains their relationship;
- canonical product keys are `docara.brand`, `docara.navigation` and
  `docara.toc`; `docara.header` and `docara.outline` remain resolving deprecated
  aliases with explicit provenance and no duplicate implementation;
- a platform-neutral manifest validator checks both bundled `ui.*` and product
  `docara.*` manifests before rendering;
- an extensible registry accepts `DocaraSmartContribution` and Framework
  contributions instead of fixed Smart/template switch lists;
- product manifests contain validated props, events, views, presets, assets,
  Atlas metadata and the four readiness flags;
- every product Smart owns its registered templates and component-specific
  CSS/JavaScript; hydration has an explicit owner and deterministic plan;
- the publisher template contains only generic document/region/asset hosts and
  no product-component markup or behavior branches;
- current search, navigation, ToC, branding, locale, reader settings,
  previous/next and responsive behavior are preserved;
- standalone installation and build do not require Laravel;
- focused, negative and full tests pass; duplicate builds are deterministic;
- exact candidate is published only to local `docara.test` with rollback;
- desktop/mobile, light/dark, multilingual, search, navigation and ToC browser
  acceptance passes without console errors or horizontal overflow;
- documentation and demonstrator show authors and extension developers how to
  inspect, configure and add a Smart contribution;
- independent acceptance and reverse-outcome audit are recorded.

## Completion Gate

Do not complete the Goal until the requirements matrix has no unresolved row,
the generic publisher-shell audit has zero product component branches, the full
suite and deterministic build pass, the served browser matrix passes, and the
independent tester verdict is `PASS` for one exact candidate revision.

## Architecture Boundary

```text
layout region
  -> registered section
    -> registered block
      -> Smart registry alias resolution
        -> canonical manifest validation
          -> view/preset/props resolution
            -> component-owned template + assets + hydration
              -> generic publisher hosts the rendered artifact
```

The shared contract is plain PHP and JSON. It may be consumed by Laravel, but
it must not import Laravel classes or require a Laravel container in Docara.

## Compatibility Contract

| Old surface | Canonical surface | Policy |
| --- | --- | --- |
| Smart `docara.header` | `docara.brand` | deprecated alias, warning/provenance, one implementation |
| Smart `docara.outline` | `docara.toc` | deprecated alias, warning/provenance, one implementation |
| Smart `docara.navigation` | `docara.navigation` | unchanged canonical key |
| Region `header` | Region `header` | unchanged; an area, not a Smart component |
| Region `outline` | Region `outline` | unchanged layout area; may contain `docara.toc` |
| Section `docara.header` | section key retained initially | typed section namespace; calls `docara.brand` |
| Section `docara.outline` | section key retained initially | typed section namespace; calls `docara.toc` |

Compatibility aliases must not become accepted names for new authored examples.

## Stages

- [done] Contract kernel: validator, registry, contributions and alias policy.
- [done] Product migration: canonical names, complete manifests, views and presets.
- [done] Ownership migration: component templates, assets, events and hydration.
- [done] Generic publisher: remove product markup and behavior from shell.
- [done] Product surfaces: documentation, Atlas/demonstrator and compatibility guide.
- [done] Acceptance: tests, deterministic build, local publication and browser matrix.

## Batches

1. Recover current architecture, gates and compatibility boundary.
2. Add the common manifest validator, registry and contribution API.
3. Migrate canonical names, manifests, views, presets and owned assets.
4. Make the publisher shell generic and update product documentation.
5. Run full/deterministic verification, local publication and browser acceptance.
6. Record the exact-candidate tester gate, reverse audit and closure.

| # | Batch | Status | Evidence |
| ---: | --- | --- | --- |
| 0 | Recover current architecture, gates and compatibility boundary | done | this workflow and preflight report |
| 1 | Add common manifest validator, registry and contribution API | done | `manifest-registry-verification.md` |
| 2 | Migrate brand/navigation/toc names and manifests with aliases | done | `compatibility-matrix.md` |
| 3 | Add views/presets/Atlas/readiness and component-owned assets | done | `manifest-registry-verification.md` |
| 4 | Remove component-specific publisher shell markup and behavior | done | `publisher-shell-audit.md` |
| 5 | Update documentation and live demonstrator | done | `documentation-and-demonstrator.md` |
| 6 | Full/deterministic/standalone verification | done | `verification-summary.md` |
| 7 | Local publication and browser acceptance | done | `deployment.md`, `browser-acceptance.md` |
| 8 | Exact-candidate tester gate, reverse audit and closure | done | `independent-acceptance.md`, `reverse-outcome-audit.md` |

## Track Linkage

- parent track: `docara-consolidation`
- this workflow: `2026-07-21-docara-smart-component-unification`

This bounded workflow advances the Docara consolidation track by accepting the
portable Smart-component ownership model. Public integration, package release
and adoption by external consumers remain separate track work.

## Owner Map

| Surface | Owner | Reviewer / gatekeeper |
| --- | --- | --- |
| standalone Docara contract and renderer | `docara` | `dev`, `tester` |
| canonical Smart semantics and Larena parity | `larena` | `sf5` |
| Framework manifests/assets/runtime | `sf5` | `tester` |
| interaction and component variants | `ux` | `tester` |
| local publication/rollback | `ops` | `tester` |

## Allowed Changes

- Docara source, resources, schemas, tests, documentation and demonstrator in
  this worktree;
- workflow/evidence/project-memory files;
- disposable builds and the local `docara.test` publication with backup.

## Forbidden Changes

- Laravel as a standalone runtime dependency;
- mutation of external `ui*`, Larena or Bitrix owner repositories in this goal;
- public push, merge, tag, package release or production deployment;
- deletion/archive of repositories or Git-history rewrite;
- secrets, arbitrary authored PHP/templates/scripts or readiness overclaims.

## Stop Conditions

- compatibility requires duplicate implementations rather than aliases;
- the shared contract would require Laravel runtime classes;
- accepted features cannot be preserved without a product decision;
- unrelated worktree changes appear;
- local publication cannot be backed up and rolled back;
- exact browser evidence contradicts static or test evidence.

## Evidence Plan

Evidence root:
`source/workflow/evidence/2026-07-21-docara-smart-component-unification/`

- `baseline-and-contract.md`
- `manifest-registry-verification.md`
- `compatibility-matrix.md`
- `publisher-shell-audit.md`
- `verification-summary.md`
- `documentation-and-demonstrator.md`
- `deployment.md`
- `browser-acceptance.md`
- `independent-acceptance.md`
- `reverse-outcome-audit.md`

## Current Remaining

None inside this bounded Goal. Public push, release and wider ecosystem
adoption remain separate explicitly authorized work.

## Result

Candidate `46fefd88d4031a1a5bcba551fef9bdc6c04b2edf` passed the full suite,
deterministic build comparison, local rollback-safe publication, desktop/mobile
browser matrix, multilingual LTR/RTL fixture, exact-candidate tester gate and
reverse-outcome audit.

## Kaizen

The typed region/section/Smart boundary and contribution-owned
manifest/template/asset/event/hydration model are accepted project-local
patterns. Owner-skill updates still require a separate approved Skill Sync Gate.
