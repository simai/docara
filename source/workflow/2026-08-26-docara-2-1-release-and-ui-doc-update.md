# Workflow: Docara 2.1 release and ui-doc update

Date: 2026-08-26
Status: in progress
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
| Audit product and documentation changes | formatter, complete tests, schema and context checks | in progress |
| Freeze and reproduce v2.1.0 candidate | clean clones, deterministic archives, PHP 8.2 consumer | pending |
| Publish Docara | main push, immutable tag, GitHub/Packagist readback | pending |
| Update and verify ui-doc | public Composer package, build, static and browser smoke | pending |
| Commit and push ui-doc | clean allowlist and remote readback | pending |

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
