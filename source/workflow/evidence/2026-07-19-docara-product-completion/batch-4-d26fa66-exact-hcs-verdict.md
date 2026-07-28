# Batch 4 — successor exact HCS/source verdict

Date: 2026-07-19
Candidate: `d26fa66c6d6a5a36ec113288e6fce29f2f6b1a0e`
Tree: `aa20ad5d0d95f82149a30d189dd5fa9d78163d4a`
Parent: `4812b19eb4cf99f0a9fba739d726d77659ef6dd8`
Accepted baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Verdict: `PASS`; P0/P1/P2: 0

The independent reviewer inspected the complete 27-file baseline-to-candidate
diff from an exact deterministic archive. Both rejected-candidate findings are
fixed:

- settings-to-search exclusion has one owner in shared `openSearch()`;
- search-to-settings exclusion remains once in the settings handler;
- obsolete shell `searchTrigger` ownership is absent;
- the negative generated-runtime regression passes with 1 test and 299
  assertions;
- `ACTIVE.md` and Next Safe Batch identify successor exact acceptance and the
  still-pending physical-key Chrome gate rather than repeating completed work.

Simai Framework ownership, accessibility/source semantics, rejected-candidate
preservation, rollback, prior-verdict supersession, secrets and nonclaims also
passed. Composer, Pint, PHP/JavaScript syntax and diff-check passed. A duplicate
full PHPUnit run was intentionally stopped without observed failures because
the exact tester owns that gate; no partial result was promoted.

Raw evidence:

- `/tmp/docara-b4-d26fa66-hcs/verdict.md`, SHA-256
  `a935eb99bea1ed8a49104de04ba9c275b725e483c8bf5a9f1a4c7776c256191d`;
- `/tmp/docara-b4-d26fa66-hcs/checks.json`, SHA-256
  `88bc6bb58820426ee519588507f316dc90cfe2ce613e239631fbda37a2eae446`.

This PASS is bounded to exact source/HCS review. Browser was not run and the
physical `Cmd/Ctrl+K` gate remains mandatory. This verdict does not accept
Batch 4 or claim publication, production, ecosystem or wider-Goal readiness.
