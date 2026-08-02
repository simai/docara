# R1 release-readiness implementation goal

Status: `in_progress`

Input revision: `afc5e0477e0ba9f65765e1cb1016bd996cb8fa75`

Accepted M5 archive revision:
`48751b8ca221f7185a72ce19188b1441aea93d2e`

Accepted M5 archive SHA-256:
`d12169b3c5080f219dada00cc976a758263cbc38ef845da11176ed7e34e8334a`

## Outcome

Record the independent M5 acceptance without changing its archive, then make
the repository able to produce and verify one deterministic portable release
package from an exact clean revision. Prove a real predecessor/current update
and rollback, fresh consumer and author workflows, security and browser gates,
and hand an exact local release candidate to a separate user-approved release
action.

This goal does not merge, push, tag, publish, release or deploy.

## Checkpoints

1. **R1.1 independent M5 acceptance** — bind the external tester audit, actual
   exact-clone count and browser evidence relationship; accept only the
   architecture/product candidate gate.
2. **R1.2 deterministic package** — explicit package surface, normalized ZIP,
   content manifest, external archive manifest/checksums, SBOM and policy scan.
3. **R1.3 release verification** — two clean source clones and two fresh dist
   consumers, quality/build/static/source-boundary gates and non-publishing CI.
4. **R1.4 versioned lifecycle** — predecessor/current fixture, hash-bound
   preview/apply/rollback, preservation, idempotence and fail-closed negatives.
5. **R1.5 adoption and runtime matrix** — fresh author flows, LTR/RTL and
   representative browser/accessibility/security verification bound to the
   exact release-package hash.
6. **R1.6 release-readiness packet** — release notes draft, compatibility
   table, known gaps, operator/rollback/smoke checklists and exact handoff.

## Risk and simplicity contract

| Risk | Guard / simplest complete answer |
| --- | --- |
| mutable worktree affects package | read committed blobs from one exact revision |
| archive metadata changes bytes | fixed ordering, timestamp, permissions and ZIP metadata |
| self-referential archive hash | content manifest inside ZIP; archive hash in paired external manifest |
| development files leak | positive allowlist plus forbidden-surface scan |
| update loses author data | project-owned paths never become update operations; preservation hashes |
| release is implied by PASS | product acceptance, release readiness and release action remain distinct gates |

Protected controls are exact revision binding, ownership, rollback, fail-closed
path/security checks, one PageBuilder/IR/registry/gateway and explicit release
approval. They may not be removed as simplification.

## Evidence

Index:
`source/workflow/evidence/2026-08-02-docara-r1-release-readiness/INDEX.md`

Every checkpoint records parent/candidate revisions, commands, hashes, actual
counts, discrepancies, rollback and nonclaims. Generated packages and browser
captures are evidence, never source of truth.

## Rollback

- M5 product rollback boundary: `900c688fbf320a8e893b4d97838c611526c2a0d8`.
- R1 input/recovery boundary: `afc5e0477e0ba9f65765e1cb1016bd996cb8fa75`.
- The accepted M5 archive remains immutable and is not rebuilt retroactively.
- Each R1 checkpoint is committed separately and can be reverted normally.
- All lifecycle tests use disposable consumers.

## Stop conditions

Stop only for an unresolved license/package-coordinate/compatibility decision,
a required external credential or live release action, an upstream Framework
change, inability to package the immutable input deterministically, inability
to prove safe rollback, or a security/data-loss defect without bounded
recovery. Continue all independent safe work before reporting a stop.

## Nonclaims

- no published version or tag;
- no merge, push, GitHub release, package publication or deployment;
- no complete translation of non-Russian documentation;
- no Framework producer change;
- no production approval.
