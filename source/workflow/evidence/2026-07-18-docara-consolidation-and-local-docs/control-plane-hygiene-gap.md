# Control-plane hygiene policy gap

Date: 2026-07-18
Scope: evidence-closure pre-commit check
Verdict: **PRE-EXISTING CONTROL-PLANE POLICY GAP; NO NEW PRODUCT HYGIENE FINDING**

The central `check-repo-hygiene.sh` accepts Docara's tracked `source/` tree but
contains a narrower rule that labels these two project-memory files as
Larena-only:

- `source/memory/CURRENT.yaml`;
- `source/memory/tracks/docara-consolidation/CURRENT.yaml`.

A clean local clone checked out at the already independently accepted product
candidate `4a312c1b14cf1e0ed0ad77d32e39b006b2ff9049` fails on exactly those two
paths. Therefore the failure predates this evidence closure. The current
closure modifies the existing memory records but does not introduce a new
memory path or a product/runtime file.

The same checker passes the `.gitignore`, `.env`, `.env.local`, `.env.example`,
Docara source exception, generated assets, output, and backup-pattern checks.
The closure diff contains no `.env`, dependency tree, key, certificate,
credential, or cookie path, and `git diff --check` passes.

Removing the two records would conflict with the federation requirement to
maintain project memory and would make `simai-project-memory` and its guard
incomplete. Modifying the canonical central checker is outside this repository
and requires its owner-approved change process. The safe local decision is to
retain the required memory, record this policy conflict, and make no false
claim that the current central repo-hygiene checker passed.

This gap does not change the exact Docara product candidate or the accepted
served static tree. It should be corrected separately in the central control
plane so Docara project-memory YAML receives the same narrow exception as the
project-memory workflow that requires it.
