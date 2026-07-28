# Batch 4 — rejected candidate 4812b19 HCS verdict

Date: 2026-07-19
Candidate: `4812b19eb4cf99f0a9fba739d726d77659ef6dd8`
Tree: `8b906deafcbb65fec3b46437587db324eef8976b`
Parent: `adad417a9ea6cad98bc79650710a4d4e732f8cac`
Accepted baseline: `06a993f3e0ce8df3bbe26569aa917b7bfe6de6a5`
Verdict: `CORRECTION_REQUIRED`; P0: 0, P1: 0, P2: 2

The independent reviewer inspected the complete 26-file baseline-to-candidate
diff from an exact archive. The shared `openSearch()` correction was
functionally placed correctly, but the candidate could not be accepted:

1. `F-001`: the shell controller retained its old `searchTrigger` click
   listener, so two places owned settings-to-search mutual exclusion while the
   candidate evidence claimed one ownership boundary;
2. `F-002`: `ACTIVE.md` and the workflow's Next Safe Batch still described the
   already-completed regression, implementation and candidate creation as
   future work rather than identifying the pending exact physical-key browser
   gate.

No new blockers were found in Simai Framework ownership, accessibility/source
semantics, rollback, evidence preservation or nonclaims. Browser was not run.
The full suite was stopped after the deterministic source rejection; partial
checks are not acceptance.

Raw evidence:

- `/tmp/docara-b4-4812b19-hcs/verdict.md`, SHA-256
  `78e1dd8c1ead24db55436d9e6d5abe08547106bb5476cf07876206c2af67947b`;
- `/tmp/docara-b4-4812b19-hcs/checks.json`, SHA-256
  `d16d9fb6b9bb47e43aa90083cbfe477a11ca8664425ba059f781086aafc0d4f1`.

Candidate `4812b19…` is immutable and rejected. Its two findings were accepted
into a test-first successor correction; this verdict makes no Batch 4,
publication, production or wider-Goal readiness claim.
