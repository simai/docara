# Workflow: Docara 2.2 release and local ui-doc update

Date: 2026-08-26
Status: completed_with_github_release_asset_followup
Owner: Docara
Companions: Development, Tester, Operations, Mirai Graph

## Goal

Audit and publish the verified Docara 2.2.0 release, update the existing
`ui-doc` working tree to the published package, and atomically refresh and
verify the local `ui-doc.test` site without restarting Codex or deploying to a
public hosting contour.

## Explicit User Authorization

The user explicitly authorized the Docara release after the page-authoring SDK
was completed. This includes the required release metadata commit and push,
immutable `v2.2.0` tag, GitHub Release and package publication. The user also
authorized updating and rebuilding the local `ui-doc.test` site. A Codex
restart and any public site deployment are explicitly excluded.

The existing dirty `ui-doc` content tree belongs to the user. This workflow may
change only the Docara Composer requirement/lock and ignored generated build
output there; it must not normalize, stage, commit or overwrite unrelated
source changes.

## Done When

- Docara `2.2.0` metadata describes only the shipped page-authoring profiles
  and page SDK;
- formatter, complete tests, documentation build, static verification,
  project-context checks, deterministic double packaging and clean consumer
  installation pass;
- `origin/main`, immutable `v2.2.0`, GitHub Release and Packagist resolve to the
  exact verified revision;
- `ui-doc` resolves that public revision through Composer without unrelated
  dependency churn;
- a disposable candidate build passes static and HTTP/browser smoke before the
  local `ui-doc.test` cutover;
- the final local site passes desktop/mobile and light/dark smoke, and the
  temporary rollback is removed;
- Codex is not restarted and no public site is deployed.

## Batches

1. Freeze scope, metadata and release candidate.
2. Run complete release-readiness checks and deterministic package audit.
3. Commit/push Docara, create the immutable tag and public package release, and
   verify GitHub/Packagist readback.
4. Update only `ui-doc` Composer inputs, build and verify a disposable
   candidate, then perform rollback-safe local cutover.
5. Record final evidence and restore the terminal state to no active
   implementation task.

## Result

- Docara `2.2.0` was fully tested, packaged twice, consumer-tested on PHP 8.2,
  committed as `c24cb112bb3f46b82ba1d60391a0d78d5dcf5f9d`, pushed to
  `origin/main`, and tagged with the immutable `v2.2.0` tag at that revision.
- GitHub Actions run `33001226144` completed successfully. Composer/Packagist
  resolves `simai/docara` `v2.2.0` to the exact release revision.
- The GitHub Releases REST endpoint rejects the registered OPS fine-grained
  token with `403 Resource not accessible by personal access token`, despite
  repository admin/push access. Therefore the optional GitHub Release page and
  its three assets remain a bounded external publication follow-up; no browser
  workaround is part of the terminal implementation state.
- `ui-doc` now requires `simai/docara:^2.2` and its lock resolves the exact
  release revision. All pre-existing non-Composer source changes were
  preserved byte-for-byte at the Git diff/status boundary.
- A disposable build produced 939 source pages and 1692 HTML pages, checked
  359237 local references with no broken links, and passed HTTP and browser
  smoke before and after the local `ui-doc.test` cutover.
- The already-existing SIMAI Framework loader requests for nine absent utility
  CSS surfaces reproduce identically before and after the cutover and are not
  a Docara 2.2 regression.
- The one temporary rollback directory was removed after final smoke. No
  permanent backup under `/Users/rim/Sites`, no public deploy, and no Codex
  restart occurred.

## Safety And Stop Conditions

- Stop on any test, package reproducibility, consumer install, remote readback,
  build, static verification, HTTP or browser smoke failure.
- Never force-push, rewrite a tag, clean the dirty `ui-doc` tree, or deploy to
  public hosting.
- Keep the old local build only as a temporary rollback during cutover. Do not
  create a persistent backup under `/Users/rim/Sites`; remove the rollback
  after successful final smoke.
- A build candidate is not a deploy and cannot replace the served build before
  verification.
