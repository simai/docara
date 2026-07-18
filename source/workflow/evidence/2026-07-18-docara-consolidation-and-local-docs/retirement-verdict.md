# `docara-mix` retirement verdict

Date: 2026-07-18
Verdict: **NOT READY**

The new Docara engine and five maintained consumers have build-passing Vite
migration candidates. That is not sufficient to archive `docara-mix`.

Blocking conditions:

1. the Docara candidate has no exact published release yet;
2. consumer Composer locks are not pinned to that release;
3. migration branches have not passed their own acceptance and reached active
   default branches;
4. legacy `ui-doc-core` remains a retirement candidate with default-branch
   references;
5. the required fresh active-default-branch zero-reference scan and
   independent retirement verdict have not run.

The repository must be archived rather than deleted only after all five
conditions close and a rollback/export record exists. No archive, deletion,
remote mutation, or readiness claim was performed in this batch.
