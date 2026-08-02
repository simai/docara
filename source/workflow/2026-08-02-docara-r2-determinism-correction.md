# R2 determinism correction and retest

Status: `in_progress`

Input revision: `96e0c82900d7689c3045ac22d950c76129bff674`

## Goal

Make the public build byte-for-byte deterministic across independent Composer
dist consumers, issue one new unpublished `2.0.0-rc.3` exact candidate, and
repeat the complete R2 package, consumer, build, HTTP/browser, security and
disposable cutover/rollback evidence without changing the live site.

## Audit correction

Independent reverse-outcome audit reproduced a release-blocking defect in the
former `2.0.0-rc.2` candidate. Outside Git,
`PortableSiteBuilder::pageMetadata()` derived public `updated_at` from source
`filemtime`; independent Composer dist extractions therefore produced different
`_docara/page-metadata.json` and complete tree digests.

The following identities are immutable historical evidence and are now
`superseded_after_determinism_audit`:

- source `56a2abf8bad05923f689141afc0bb045aa4d6734`;
- ZIP `04c18c95f2599905b1908fae3e326a9cf1ba47f29327ddd88465c4b4b792f753`;
- recorded candidate tree `457790d4cf212174b7ef34893f8ee3cfc11f8973022c8f28c18348e46f2a3bae`;
- independently observed trees `01b4c9ae12f73699c2ae62b5a191f6b38d1fe8fec278a3605ada59cd6c5ad740`
  and `56c6b5bed1d0a2bc66af06597a370d07bab4585e438e9065fd1aa9f682321424`.

R2, local release readiness and deployment dossier are reopened. No current
deploy candidate exists until this workflow completes and is independently
retested.

## Contract decision

Public page metadata may only use immutable source inputs. Git metadata is used
when it is available for the exact page source. Outside Git, absent explicit
immutable author metadata, `updated_at`, `revision` and `author` are `null`.
Filesystem extraction/modification times are never public metadata.

## Allowed and forbidden scope

Allowed: repository runtime/tests/specification/graph/workflow/handoff, clean
disposable clones and consumers, and compact sanitized evidence.

Forbidden: writes under `/Users/rim/Sites/docara.test`, Caddy reload, merge,
push, tag, publication, release, deploy, weakening digests/verifiers, excluding
page metadata from comparison, or normalizing consumer files to hide drift.

## Batch plan

| Batch | Result | Verification | Status |
| --- | --- | --- | --- |
| C1 | Withdraw rc.2/R2 PASS and open correction | graph/docs/handoff/diff/JSON | in progress |
| C2 | Deterministic metadata contract | focused positive/negative tests | planned |
| C3 | Integrated source verification | PHPUnit/Pint/Composer/lint/JSON/YAML/graph | planned |
| C4 | Exact rc.3 package | two clean-clone byte-identical ZIP/manifest/SBOM | planned |
| C5 | Independent dist consumers | different extraction times, identical 305 outputs including metadata | planned |
| C6 | Product/release matrix | macOS 8.4/8.3, Linux 8.3, full/single/static/HTTP/browser/security | planned |
| C7 | Deployment dossier retest | live delta, accepted digest, mirror cutover/smoke/exact rollback | planned |
| C8 | Integrated governance | acceptance/roadmap/graph/handoff, clean worktree | planned |

## Done When

- two independently installed dist consumers with different filesystem mtimes
  and one immutable lock produce byte-identical complete 305-file trees;
- `_docara/page-metadata.json` is included in that equality proof;
- one new exact source and unpublished `2.0.0-rc.3` ZIP/manifest/checksum/SBOM
  identity is reproduced from two clean clones;
- the full required test, build, HTTP/browser, security and compatibility
  matrices pass on the new identity;
- disposable current -> candidate -> smoke -> rollback restores the exact live
  baseline digest;
- governance names only the new candidate, while release and production remain
  blocked on a separate user decision;
- worktree is clean.

## Rollback and stop conditions

Rollback boundary is input revision `96e0c829…` plus separate correction
checkpoint commits. Stop on any new hash drift, need for live write or
credentials, non-exact mirror rollback, an unresolved data-loss/security risk,
or a materially new metadata product decision.

Evidence index:
`source/workflow/evidence/2026-08-02-docara-r2-determinism-correction/INDEX.md`.

