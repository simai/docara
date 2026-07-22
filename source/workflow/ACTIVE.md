# Active workflow: Docara 2 portable-only release candidate

Date: 2026-07-22
Status: completed
Workflow ID: `2026-07-22-docara-2-portable-only-release-candidate`
Process model: `release`
Current state: `review_ready`
Target state: `review_ready`

## Result

Docara 2 now has one portable JSON/Markdown product path, one CLI, one builder
and one starter. The old Jigsaw/Mix runtime and transitional implementation
were removed. Exact candidate
`c537e17f61f890fdbf5635c83ee642109bf730a4` passed isolated source, Composer
distribution, deterministic build, documentation and desktop/mobile browser
acceptance.

## Evidence

- workflow:
  `source/workflow/2026-07-22-docara-2-portable-only-release-candidate.md`;
- final verdict:
  `source/workflow/evidence/2026-07-22-docara-2-portable-only-release-candidate/b5/acceptance.md`.

## Boundary

No public push, merge, release tag, package publication, production deployment
or production-readiness claim was performed. Those actions require a separate
release workflow and gate.
