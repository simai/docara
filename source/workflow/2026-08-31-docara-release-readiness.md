# Docara local release-readiness audit

Date: 2026-08-31
Status: release candidate verified; Federation skill binding resolved

## Outcome

Prepare the completed Docara corrections as a reviewable local release
candidate without mixing in the separate native View Transition proposal and
without performing push, tag, package publication or deployment.

## Candidate

- `9265508 fix: bootstrap the first AI contract release`
- `8543358 feat: report static-site performance inputs`
- Base: `118c466 feat: add transactional project upgrades`

The two changes are intentionally separate. The first corrects the release
gate for the one historical transition from a Docara release without an AI
contract. The second adds report-only performance evidence and correct
responsive-image geometry.

## Verification evidence

- All 85 PHPUnit test files passed under PHP 8.4.20 in four isolated test
  processes, including the complete portable-site, documentation-site and
  static-verifier integration classes.
- The focused AI release-gate matrix passed 25 tests with 702 assertions.
- The documentation production build generated 128 source pages.
- `verify-static` checked 263 HTML pages and 35,556 local references with no
  broken references.
- Desktop and mobile browser smoke verified intrinsic image sizing, declared
  aspect ratios and zero document-level horizontal overflow.
- Pint, `git diff --check` and strict Composer validation passed. Composer
  printed only deprecation notices from its own bundled PHP 8.4 dependencies.
- The canonical Docara skill contract validator, smoke suite, graph sync and
  bilingual outcome benchmark passed at
  `7c0ebc4b0982f2d2ccd75fb759d50042f5ef7b0c`.
- Federation route-check passed all 358 scenarios.
- Full verification of installed Federation 9.9.3 completed successfully: 26
  installed skills, runtime health, clean-room installation and technology
  contract checks passed with no blockers.
- The public AI release gate passed against the actual stable Federation lock,
  package-owned 2.4.1 bootstrap baseline and exact canonical skill revision.
- The action-gate report
  `source/output/action-gates/action-gate-report-20260830222205.json` has no
  blocking finding. Its remaining warnings concern Federation graph schema
  naming and do not alter the Docara package.

## Skill Sync Gate

The canonical skill repository is clean and its `main` and `origin/main` both
point to `7c0ebc4b0982f2d2ccd75fb759d50042f5ef7b0c`. That revision:

- discovers the exact project-local `vendor/bin/docara`;
- reads `capabilities --json` instead of copying the product command registry;
- supports `docara.ai_contract >=1.0.0 <2.0.0`;
- documents high-level `upgrade`, offline rollback and the retained low-level
  `update` operation;
- contains accepted continuity evidence and synchronized skill graph objects.

No additional skill source change is required for the performance receipt:
the receipt and its schema are discovered through the package capabilities
contract.

Federation 9.9.3 now pins that exact revision, is installed atomically and has
passed the full installed and clean-room verification with no blockers. Its
stable GitHub Release is public. The Docara AI release gate consequently passes
for the exact 2.4.1 bootstrap baseline, AI contract 1.1.0 and skill revision
`7c0ebc4...`; no stable lock or installed runtime was edited manually.

## Human-centered simplicity review

### Primary user outcome

An existing Docara project can be upgraded safely, while its maintainer can
verify page resources and image geometry without adding project configuration,
a second status engine or a bespoke audit script.

### Changed surface and necessity map

- AI bootstrap baseline: necessary because Docara 2.4.1 predates
  `capabilities`; narrowly valid only for the first AI-contract release.
- Performance receipt: necessary to expose already produced build facts in the
  existing receipt/verifier architecture; report-only and deterministic.
- Responsive-image rules: necessary to prevent forced upscaling and to map
  authored ratios onto existing Framework utilities.
- Exact 21:9 compatibility rule: retained because Framework 5.4.0 does not
  publish that utility and changing the authored contract would be a regression.

### Removal and reuse review

- No new public command, required setting, lock file, background process,
  status engine or network dependency was added.
- Existing application services, schema catalogue, portable-site builder,
  static verifier and `capabilities` contract were reused.
- A universal performance-budget policy was rejected: the package reports
  evidence and leaves product-specific thresholds to project owners.
- Replacing the official icon font with the historical Framework subset was
  rejected because that subset is incomplete.
- The native View Transition proposal is not required for these outcomes and
  remains outside this candidate.

### Complexity and protected boundaries

The retained complexity is one schema and one receipt model integrated into
existing build/verify paths. Hash binding, deterministic recomputation,
traversal and link protections, exact skill revision binding and offline
rollback remain protected because removing them would weaken correctness or
recovery.

Verdict: `PASS` for the Docara 2.5.0 candidate. The former Federation binding
blocker is resolved; package publication remains subject to the exact release
tests, deterministic packaging and immutable tag checks.

## Preserved unrelated work

The following untracked native View Transition proposal files remain untouched
and excluded from every candidate commit:

- `graph/specs/batches/docara-native-view-transitions.json`
- `graph/specs/goals/docara-native-view-transitions.json`
- `graph/specs/stages/docara-native-view-transitions.json`
- `source/workflow/2026-08-28-docara-native-view-transitions-plan.md`

Their presence makes the host-local Project Technology inventory stale. It is
not repaired in this workflow because synchronizing it would incorrectly mix a
separate planned feature into the release candidate.

## Release boundary

Federation 9.9.3 binding, installation and publication were completed as a
separate verified release. At this candidate checkpoint no Docara push, tag,
GitHub release, Composer publication or public deployment had yet been
performed.
