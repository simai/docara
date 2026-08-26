# Workflow: Docara 2.2 release and local ui-doc update

Date: 2026-08-26
Status: active
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
