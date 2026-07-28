# Batch 0 baseline

- Accepted upstream tag: `v1.3.65`.
- Exact revision: `ba7724ae3d9e2b99388098637b81a35a2646e6a4`.
- Worktree: `/Users/rim/Documents/GitHub/docara-worktree-declarative`.
- Branch: `codex/declarative-site-contract`.
- Original recovery checkout remained untouched.
- Project doctor: `success` after adding only the missing local/private ignore
  patterns.
- Process transition `repository_prepared -> ready_to_code`: `success`, no
  blockers.
- Preflight action gate: `success`, low-risk reversible local work.
- Gate evidence:
  `source/output/action-gates/action-gate-report-20260717220820.json`.

Limitations: the upstream GitHub checks cover lint/deploy but do not constitute
PHPUnit acceptance. The implementation batches therefore establish a fresh
dependency baseline and executable test evidence.
