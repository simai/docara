# Workflow: Docara AI contract bootstrap correction

Date: 2026-08-30
Status: complete

## Goal

Make the Docara AI release gate honestly verify the first package release that
introduces `capabilities`, when the previous released package did not expose a
capabilities contract.

## Done When

- The gate accepts one package-owned, exact historical baseline for Docara
  `2.4.1` without pretending that release emitted `docara.capabilities.v1`.
- The bootstrap baseline is valid only for the declared first AI contract and
  cannot replace normal previous/current comparison in later releases.
- Invalid, incomplete, mismatched or reused bootstrap evidence fails closed.
- Existing exact skill and Federation binding checks remain unchanged.
- Focused release-regression tests, formatting and the local gate scenario are
  green. The complete repository suite remains a consolidated release-audit
  gate because the checkout also contains a separate, unfinished performance
  baseline.

## Boundaries

- Owner: Docara package release contract.
- Allowed: release gate implementation, its unit tests, package-owned baseline,
  release-readiness documentation and a narrow changelog entry.
- Forbidden: Federation source/runtime, canonical skill source, package tag,
  GitHub release, Composer publication, deployment and unrelated performance or
  responsive-image changes already present in the checkout.
- Current branch `main` is reused; no branch or worktree is created.

## Ideal Final Result

Maintainers provide the same `--previous=<json>` input as for ordinary
releases. For the one historical transition, that JSON explicitly states that
Docara 2.4.1 had no capabilities command and binds the first AI contract
version. No second command, release database or manually copied capability
catalogue is introduced.

## Batch

1. Freeze the exact 2.4.1 tag/revision and current 1.1.0 contract.
2. Add a narrowly validated historical absence baseline.
3. Test valid bootstrap, malformed evidence, version mismatch, forbidden reuse,
   normal changed/unchanged comparison and stale Federation binding.
4. Update release instructions and run verification.

## Evidence

- Previous release tag: `v2.4.1`.
- Previous release revision:
  `48e614ef0037715d002c9fea7e40aa05a12bd5a5`.
- `resources/ai-contract.json` is absent at `v2.4.1` and first appears in
  `118c4661e01b157a9e4a8e2121c043d5851dbfc7`.
- The release surface includes all of `resources/`, so the package-owned
  baseline is selected by the existing deterministic package builder.
- `AiContractReleaseGateTest`, `CapabilitiesServiceTest`, `McpAdapterTest`,
  `ReleasePackageTest` and `ProjectContextContractTest`: 25 tests, 702
  assertions, pass.
- Full and targeted Pint checks pass; `composer validate --strict
  --no-check-publish` passes with PHP 8.4 deprecation notices from Composer's
  own bundled dependencies.
- The public `verify-ai-contract-release.php` path passes with current
  capabilities, the exact package-owned baseline and an isolated exact-skill
  binding fixture. No Federation runtime or stable release lock was changed.
- The consolidated release matrix completed after the independent performance
  correction was stabilized: all 85 PHPUnit test files passed under PHP
  8.4.20, including the full release-gate, capabilities, package and real-site
  integration classes.
- Project Technology reports its graph digest as current but its inventory as
  stale because of the separate uncommitted view-transition graph baseline.
  This correction extends the existing seamless-upgrade feature and mapping
  without creating a parallel graph object.

## Next

Include this correction in the local release-ready commit set. Push, tag,
package publication and Federation stable-lock changes remain separate actions.
