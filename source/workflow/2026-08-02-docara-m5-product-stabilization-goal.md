# M5 product stabilization implementation goal

Status: `in_progress`

Input revision: `900c688fbf320a8e893b4d97838c611526c2a0d8`

Branch: `codex/docara-unified-architecture`

Evidence index:
`source/workflow/evidence/2026-08-01-docara-unified-architecture/m5-product-stabilization/INDEX.md`

## Outcome

Produce one clean Docara 2 implementation candidate that an independent tester
can accept from an exact archive. The candidate must initialize a portable
consumer without Node.js, update only package-owned state through a verified
previewable atomic transaction with rollback, preserve project-owned files,
retain the accepted one-PageBuilder/typed-IR/registry/gateway contour, and pass
the declared product/security/locale/authoring matrices.

This implementation goal does not perform the independent M5 acceptance and
does not authorize merge, push, tag, release or deployment.

## Frozen M4 boundary

- exact rollback revision: `900c688fbf320a8e893b4d97838c611526c2a0d8`;
- 103 current Russian routes, all physical Markdown-owned;
- generated public owners and allowlist entries: zero;
- M4 full/single parity: 103/103;
- M4 reverse audit: `PASS_WITH_NOTES` independently and
  `PASS_WITH_NONCLAIMS` in repository evidence.

## Confirmed implementation gaps

1. `init --update` only preserves existing files and restores missing starter
   files; it has no ownership manifest, plan, transaction or rollback.
2. The package library correctly excludes `composer.lock`, but no shipped
   immutable dependency tuple verifies a consumer-owned lock.
3. Build receipts do not yet bind the exact engine/package revision and the
   complete package/template/asset dependency chain.
4. Partial-build failure preservation needs an explicit whole-output regression
   including indexes and receipts.
5. The target contract lacks a real second-LTR and RTL consumer fixture and the
   final security/accessibility author workflow evidence.
6. ROADMAP/NEXT had stale M4 wording at the accepted input revision.

## Checkpoints

### M5.1 Recovery and execution contract

- freeze input, route/product baseline, gaps, rollback and stop conditions;
- add `docara.batch.m5.stabilize` before read-only `m5.accept`;
- correct stale M4/M5 roadmap and handoff wording.

### M5.2 Ownership and immutable install contract

Status: `PASS`

- ship a versioned machine-readable ownership contract;
- classify package-owned `.docara/engine/**`, project-owned content/config/assets
  and generated output patterns;
- record an exact package fingerprint, Framework tuple and dependency tuple;
- initialize the ownership state from an empty directory without Node.js.

### M5.3 Transactional update lifecycle

Status: `PASS`

- implement `update --verify`, `--dry-run`, explicit `--apply` and
  `--rollback=<id|latest>`;
- bind apply to an unchanged plan/input hash;
- atomically swap package-owned state and preserve a validated rollback
  package, manifest and Framework lock snapshot;
- fail closed on symlinks, unknown engine files, dirty engine-owned state,
  stale plans, ownership conflicts and corrupt rollback artifacts.

### M5.4 Build/diagnostics and architecture proof

Status: `PASS`

- bind receipts to package revision, Framework tuple, source/config/template,
  manifest and asset hashes without private absolute paths;
- prove failed isolated builds preserve the complete accepted output;
- audit inline/block component consumers, registry, gateway and source inputs;
- keep typed Document IR in memory without mandatory page IR files.

### M5.5 Locale, security and accessibility fixtures

Status: `PASS`

- add a minimal second LTR and RTL portable fixture through the same engine;
- prove locale routing, no silent editorial fallback, logical layout and
  locale-safe navigation/search;
- cover traversal/symlink/include/output/raw HTML/embed/secrets policies;
- run desktop/mobile light/dark LTR/RTL browser and interaction checks.

Evidence:
`source/workflow/evidence/2026-08-01-docara-unified-architecture/m5-product-stabilization/M5.5-LOCALE-SECURITY-ACCESSIBILITY.md`.

### M5.6 Consumer author workflow and documentation

Status: `PASS`

- use a clean initialized consumer to edit one Markdown owner and rebuild one
  route;
- cover add/rename/delete route behavior and when full build is required;
- synchronize README, CLI help and public docs with the one actual contract.

Evidence:
`source/workflow/evidence/2026-08-01-docara-unified-architecture/m5-product-stabilization/M5.6-AUTHOR-WORKFLOW-AND-DOCUMENTATION.md`.

### M5.7 Candidate verification and handoff

- build an exported Composer archive and install a disposable consumer against
  the immutable dependency contract;
- run update/full/single/static/security/browser/quality matrices;
- update acceptance, graph, mappings, handoff and exact evidence;
- leave a clean candidate ready for read-only `docara.batch.m5.accept`.

## Risk map

| Risk | Guard |
| --- | --- |
| project content/config overwritten | project-owned patterns are never update targets; preservation matrix |
| partial update | package-owned state is staged and directory-swapped with immediate restore on failure |
| stale or forged plan | canonical plan hash binds current state, desired package state and target root identity |
| corrupt rollback | manifest and per-file hashes verified before any swap |
| mutable dependencies | shipped dependency tuple is checked against consumer `InstalledVersions` and consumer lock |
| second engine introduced | existing PageBuilder/registry/gateway are extended only through their contracts |
| locale fixture mistaken for translation | fixture is explicitly minimal and not public documentation coverage |

## Rollback

Every green checkpoint has a separate commit. Revert the smallest implementation
commit for a local regression. Revert to exact M4 revision `900c688` for the
whole-goal rollback. Update transactions also retain their own validated
consumer rollback package; generated builds are never rollback sources.

## Stop conditions

Stop only for a required immutable Framework producer change, an update design
without safe rollback, ambiguous ownership requiring a product decision, an
external credential/live/release action, an unsafe conflict with user changes,
or an irreplaceable second public source/renderer without a parity plan. Normal
implementation/test/browser defects are fixed within this goal.

## Nonclaims

- no independent M5 acceptance;
- no architecture-acceptance gate PASS;
- no complete translation of `ar`, `en`, `fr-CA` or `zh-Hans`;
- no merge, push, tag, release or production deployment.
