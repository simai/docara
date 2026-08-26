# Workflow: Docara 2.1 release and ui-doc update

Date: 2026-08-26
Status: completed
Owner: Docara
Companions: Development, Tester, Operations, Teamlead, Mirai Graph

## Goal

Audit and publish the verified Docara 2.1 release, update `ui-doc` to the
published package, rebuild and verify the local `ui-doc.test`, and push the
separate Docara and ui-doc repository results without a public deployment.

## Explicit User Authorization

The user explicitly authorized Docara commit, push, version tag, GitHub
Release, package publication, the ui-doc dependency update, local
`ui-doc.test` replacement, and a separate ui-doc commit and push. Public
deployment is explicitly excluded.

## Done When

- the Docara diff is reconciled with public `main` and passes release audit;
- version, changelog and release notes consistently identify `2.1.0`;
- complete tests, documentation build, static verification, deterministic
  double packaging and fresh PHP 8.2 consumer checks pass;
- `origin/main`, `v2.1.0`, GitHub Release and Packagist resolve to the exact
  verified Docara revision;
- ui-doc resolves the public `2.1.0` package, builds all pages and passes static
  and browser verification on the local `ui-doc.test` host;
- local site cutover uses a temporary rollback only and removes it after green
  smoke;
- Docara and ui-doc have separate commits and pushes;
- no public hosting or production deployment is changed.

## Release Audit Stages

| Stage | Verification | Status |
| --- | --- | --- |
| Reconcile local Docara changes with public v2.0.0 main | fast-forward plus preserved working diff | completed |
| Audit product and documentation changes | formatter, complete tests, schema and context checks | completed |
| Freeze and reproduce v2.1.0 candidate | clean clones, deterministic archives, PHP 8.2 consumer | completed |
| Publish Docara | main push, immutable tag, GitHub/Packagist readback | completed |
| Update and verify ui-doc | public Composer package, build, static and browser smoke | completed |
| Commit and push ui-doc | clean allowlist and remote readback | completed |

## Completion Evidence

- Docara release commit: `0e58cc56e6da733d7a534e9e0330936034f86715`.
- Immutable tag: `v2.1.0`.
- GitHub Release: <https://github.com/simai/docara/releases/tag/v2.1.0>.
- Packagist `simai/docara` version `v2.1.0` resolves to the release commit.
- GitHub Actions run `32908920184` completed successfully.
- The release ZIP was produced twice with identical bytes; SHA-256 is
  `5dda70251c412c343a4f106c8b015c4ffe37bc84cec7d58b320196d07d9f9dd8`.
- All three public GitHub Release assets were downloaded and compared byte for
  byte with the verified local artifacts.
- ui-doc commit `45adb59ef1742f3aee99b4dbcb9ca22dca04c3df` was pushed to `main`.
- ui-doc GitHub Actions run `32911042231` completed successfully.
- The local ui-doc build contains 1,692 HTML pages; static verification checked
  359,226 local references with no broken references.
- HTTP and browser smoke passed for RU/EN, desktop/mobile, light/dark themes,
  and adaptive Example tabs. No persistent rollback directory remains.
- No public documentation hosting or production deployment was changed.

## Safety And Rollback

- No force push, tag rewrite, public deployment, or unrelated repository
  cleanup is allowed.
- The local site is never overwritten in place without an exact temporary
  rollback and HTTP/browser smoke.
- No persistent backup directory may remain under `/Users/rim/Sites` after a
  successful cutover.
- A failed test, non-deterministic package, stale remote, inaccessible release
  surface, unresolved Composer revision, or failed local-site smoke stops the
  corresponding publication stage.
